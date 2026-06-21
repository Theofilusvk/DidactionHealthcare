"""
===========================================================
  DHC — Multi-Disease Prediction  |  FastAPI ML Service
===========================================================

Arsitektur:
  • Model utama  : XGBoost (stacked per penyakit), disimpan di /models/multidisease_model.pth
  • Referensi NN : Kelas MultiDiseaseNN (nn.Module) tersedia sebagai alternatif / pengganti
                   bila file .pth berisi bobot PyTorch

Endpoint:
  POST /predict  — Terima fitur kesehatan, kembalikan probabilitas 5 penyakit
  GET  /health   — Health-check & info model

Penyakit yang diprediksi:
  1. Heart Disease
  2. Stroke
  3. Diabetes
  4. Hypertension
  5. Chronic Kidney Disease (CKD)

Jalankan:
  uvicorn main:app --reload --port 8000
"""

# ─── Standard Library ────────────────────────────────────────────────────────
import os
import json
import logging
from pathlib import Path
from contextlib import asynccontextmanager

# ─── Third-party ─────────────────────────────────────────────────────────────
import numpy as np
import torch
import torch.nn as nn
import torch.nn.functional as F
import httpx
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

# ─── Logging Setup ───────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s  [%(levelname)s]  %(name)s — %(message)s",
)
log = logging.getLogger("dhc.ml-service")

# ─── Path Constants ───────────────────────────────────────────────────────────
BASE_DIR   = Path(__file__).resolve().parent.parent   # root DHC/
MODEL_PATH = BASE_DIR / "models" / "multidisease_model.pth"

def load_laravel_env():
    """Membaca file .env dari folder Laravel untuk dimuat ke os.environ."""
    env_path = BASE_DIR / "DidactionHealthcare" / ".env"
    if env_path.exists():
        log.info(f"Membaca file .env Laravel dari: {env_path}")
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                parts = line.split("=", 1)
                if len(parts) == 2:
                    key = parts[0].strip().strip('"').strip("'")
                    val = parts[1].strip().strip('"').strip("'")
                    if key not in os.environ:
                        os.environ[key] = val
    else:
        log.warning(f"File .env Laravel tidak ditemukan di: {env_path}")

# Panggil fungsi untuk memuat variabel lingkungan
load_laravel_env()


# Daftar penyakit yang diprediksi (urutan harus cocok dengan output model)
DISEASE_LABELS = [
    "heart_disease",
    "stroke",
    "diabetes",
    "hypertension",
    "ckd",
]

DISEASE_DISPLAY = {
    "heart_disease": "Heart Disease",
    "stroke":        "Stroke",
    "diabetes":      "Diabetes",
    "hypertension":  "Hypertension",
    "ckd":           "Chronic Kidney Disease",
}

# Fitur input yang digunakan model (7 shared core features)
INPUT_FEATURES = [
    "age",
    "gender",       # 0 = Female, 1 = Male
    "bmi",
    "glucose",
    "blood_pressure",
    "cholesterol",
    "heart_rate",
]

# ─── Jumlah fitur input ───────────────────────────────────────────────────────
NUM_FEATURES  = len(INPUT_FEATURES)   # 7
NUM_DISEASES  = len(DISEASE_LABELS)   # 5


# ═══════════════════════════════════════════════════════════════════════════════
# 1.  ARSITEKTUR JARINGAN SARAF — Referensi nn.Module
# ═══════════════════════════════════════════════════════════════════════════════

class MultiDiseaseNN(nn.Module):
    """
    Jaringan saraf multi-output untuk prediksi 5 penyakit sekaligus.

    Arsitektur:
    ┌─────────────────────────────────────────────────────┐
    │  Input Layer   : 7 fitur kesehatan                  │
    │  Hidden Layer 1: 128 neuron  +  BatchNorm + ReLU    │
    │  Hidden Layer 2:  64 neuron  +  BatchNorm + ReLU    │
    │  Hidden Layer 3:  32 neuron  +  ReLU + Dropout(0.3) │
    │  Output Layer  :   5 neuron  (sigmoid per penyakit) │
    └─────────────────────────────────────────────────────┘

    Output: probabilitas independen [0,1] untuk setiap penyakit.
    Loss  : BCEWithLogitsLoss per kepala (multi-label, bukan multi-class).
    """

    def __init__(
        self,
        num_features: int = NUM_FEATURES,
        num_diseases: int = NUM_DISEASES,
        hidden_dims: list[int] | None = None,
        dropout_rate: float = 0.3,
    ) -> None:
        super().__init__()

        if hidden_dims is None:
            hidden_dims = [128, 64, 32]

        # ── Shared Feature Extractor ──────────────────────────────────────────
        layers: list[nn.Module] = []
        in_dim = num_features

        for i, out_dim in enumerate(hidden_dims):
            layers.append(nn.Linear(in_dim, out_dim))
            layers.append(nn.BatchNorm1d(out_dim))
            layers.append(nn.ReLU())
            if i == len(hidden_dims) - 1:
                # Dropout hanya di layer terakhir shared block
                layers.append(nn.Dropout(dropout_rate))
            in_dim = out_dim

        self.shared = nn.Sequential(*layers)

        # ── Output Head (satu per penyakit) ──────────────────────────────────
        # Logit mentah — sigmoid diterapkan saat inference
        self.output_head = nn.Linear(hidden_dims[-1], num_diseases)

        # ── Weight Initialization ─────────────────────────────────────────────
        self._init_weights()

    def _init_weights(self) -> None:
        """Kaiming uniform untuk Linear, constant untuk BatchNorm."""
        for module in self.modules():
            if isinstance(module, nn.Linear):
                nn.init.kaiming_uniform_(module.weight, nonlinearity="relu")
                if module.bias is not None:
                    nn.init.zeros_(module.bias)
            elif isinstance(module, nn.BatchNorm1d):
                nn.init.ones_(module.weight)
                nn.init.zeros_(module.bias)

    def forward(self, x: torch.Tensor) -> torch.Tensor:
        """
        Args:
            x: Tensor shape (batch_size, num_features) — fitur yang sudah dinormalisasi
        Returns:
            logits: Tensor shape (batch_size, num_diseases)
        """
        features = self.shared(x)
        logits   = self.output_head(features)
        return logits

    def predict_proba(self, x: torch.Tensor) -> torch.Tensor:
        """
        Konversi logits → probabilitas dengan sigmoid.
        Returns:
            probs: Tensor shape (batch_size, num_diseases), nilai [0, 1]
        """
        with torch.no_grad():
            logits = self.forward(x)
            probs  = torch.sigmoid(logits)
        return probs


# ═══════════════════════════════════════════════════════════════════════════════
# 2.  MODEL LOADER
# ═══════════════════════════════════════════════════════════════════════════════

class ModelBundle:
    """
    Wrapper yang memegang model yang sudah di-load dan menyediakan
    method predict() yang unified.

    Mendukung format:
      (A) PyTorch .pth (multi-disease model)
      (B) XGBoost .pkl (individual models per disease)
    """

    def __init__(self) -> None:
        self.model:        MultiDiseaseNN | None = None
        self.scaler_mean:  np.ndarray | None     = None
        self.scaler_std:   np.ndarray | None     = None
        self.device:       torch.device          = torch.device("cpu")
        self.loaded:       bool                  = False
        self.mode:         str                   = "none"  # "pytorch" | "xgboost" | "fallback"
        self.xgb_models:   dict                  = {}

    # ── Loader ────────────────────────────────────────────────────────────────
    def load(self, model_path: Path) -> None:
        # Coba muat model PyTorch utama
        if model_path.exists():
            log.info(f"Memuat model PyTorch dari: {model_path}")
            try:
                checkpoint = torch.load(model_path, map_location=self.device, weights_only=False)

                self.model = MultiDiseaseNN(
                    num_features=NUM_FEATURES,
                    num_diseases=NUM_DISEASES,
                )

                # ── Format A: state_dict langsung ────────────────────────────────────
                if isinstance(checkpoint, dict) and "model_state" in checkpoint:
                    self.model.load_state_dict(checkpoint["model_state"])
                    # Scaler opsional — jika tersimpan di checkpoint
                    self.scaler_mean = np.array(
                        checkpoint.get("scaler_mean", [0.0] * NUM_FEATURES), dtype=np.float32
                    )
                    self.scaler_std = np.array(
                        checkpoint.get("scaler_std", [1.0] * NUM_FEATURES), dtype=np.float32
                    )
                    log.info("Checkpoint format: full-bundle (state + scaler)")

                # ── Format B: state_dict murni ────────────────────────────────────────
                elif isinstance(checkpoint, dict):
                    self.model.load_state_dict(checkpoint)
                    self.scaler_mean = np.zeros(NUM_FEATURES, dtype=np.float32)
                    self.scaler_std  = np.ones(NUM_FEATURES,  dtype=np.float32)
                    log.info("Checkpoint format: state-dict murni")
                else:
                    raise TypeError(
                        f"Format checkpoint tidak dikenal: {type(checkpoint)}. "
                        "Harap simpan sebagai state_dict atau bundle dict."
                    )

                self.model.to(self.device)
                self.model.eval()
                self.loaded = True
                self.mode   = "pytorch"
                log.info(f"Model PyTorch berhasil dimuat. Mode: {self.mode}")
                return
            except Exception as e:
                log.error(f"Gagal memuat model PyTorch: {e}. Mencoba memuat model XGBoost...")

        # Jika PyTorch model tidak ada, coba muat model-model XGBoost dari python-ml-service/models
        xgb_dir = Path(__file__).resolve().parent / "models"
        xgb_files = {
            "heart_disease": xgb_dir / "heart_disease_model.pkl",
            "stroke":        xgb_dir / "stroke_model.pkl",
            "diabetes":      xgb_dir / "diabetes_model.pkl",
            "hypertension":  xgb_dir / "hypertension_model.pkl",
            "ckd":           xgb_dir / "ckd_model.pkl",
        }

        all_xgb_exist = all(p.exists() for p in xgb_files.values())
        if all_xgb_exist:
            log.info("Memuat model-model XGBoost (.pkl)...")
            try:
                import pickle
                for disease, path in xgb_files.items():
                    with open(path, "rb") as f:
                        self.xgb_models[disease] = pickle.load(f)
                self.loaded = True
                self.mode   = "xgboost"
                log.info(f"Semua model XGBoost berhasil dimuat. Mode: {self.mode}")
                return
            except Exception as e:
                log.error(f"Gagal memuat model-model XGBoost: {e}")

        # Jika tidak ada model yang bisa dimuat
        log.warning(
            f"File model tidak ditemukan di: {model_path} maupun model XGBoost (.pkl) di {xgb_dir}\n"
            "  → Menggunakan mode FALLBACK (probabilitas dummy)."
        )
        self.loaded = False
        self.mode   = "fallback"

    # ── Inference ─────────────────────────────────────────────────────────────
    def predict(self, feature_vector: list[float]) -> dict[str, float]:
        """
        Args:
            feature_vector: list[float] dengan urutan sesuai INPUT_FEATURES
        Returns:
            dict {disease_key: probability}
        """
        if self.mode == "pytorch" and self.loaded and self.model is not None:
            return self._predict_pytorch(feature_vector)
        elif self.mode == "xgboost" and self.loaded and self.xgb_models:
            return self._predict_xgboost(feature_vector)
        else:
            return self._predict_fallback(feature_vector)

    def _predict_pytorch(self, feature_vector: list[float]) -> dict[str, float]:
        """Inference dengan model PyTorch."""
        arr = np.array(feature_vector, dtype=np.float32)

        # Normalisasi dengan scaler yang tersimpan
        arr = (arr - self.scaler_mean) / (self.scaler_std + 1e-8)

        tensor = torch.tensor(arr, dtype=torch.float32).unsqueeze(0).to(self.device)  # (1, 7)

        with torch.no_grad():
            probs = self.model.predict_proba(tensor)   # (1, 5)

        probs_np = probs.squeeze(0).cpu().numpy()      # (5,)
        return {label: float(probs_np[i]) for i, label in enumerate(DISEASE_LABELS)}

    def _predict_xgboost(self, feature_vector: list[float]) -> dict[str, float]:
        """Inference dengan model-model XGBoost (.pkl)."""
        import numpy as np
        
        x_input = np.array([feature_vector], dtype=np.float32)

        results = {}

        for disease_key in DISEASE_LABELS:
            if disease_key in self.xgb_models:
                model = self.xgb_models[disease_key]
                results[disease_key] = float(model.predict_proba(x_input)[0][1])

        return results

    def _predict_fallback(self, feature_vector: list[float]) -> dict[str, float]:
        """
        Mode fallback — estimasi heuristik sederhana berbasis fitur input.
        TIDAK untuk produksi. Hanya agar endpoint tetap berfungsi saat model belum ada.
        """
        age = feature_vector[0]          # 0–100
        bmi = feature_vector[2]          # 10–60
        glc = feature_vector[3]          # 50–300
        bp  = feature_vector[4]          # 60–200

        # Skor risiko mentah sederhana (linear, tidak terkalibrasi)
        def clamp(v: float) -> float:
            return max(0.0, min(1.0, v))

        heart_risk = clamp((age / 120) * 0.4 + (bp / 200) * 0.4 + (bmi / 50) * 0.2)
        stroke_risk = clamp((age / 120) * 0.5 + (bp / 200) * 0.3 + (glc / 300) * 0.2)
        diab_risk   = clamp((glc / 300) * 0.6 + (bmi / 50) * 0.3 + (age / 120) * 0.1)
        hyper_risk  = clamp((bp / 200) * 0.6  + (bmi / 50) * 0.2 + (age / 120) * 0.2)
        ckd_risk    = clamp((age / 120) * 0.3 + (bp / 200) * 0.3 + (glc / 300) * 0.4)

        return {
            "heart_disease": heart_risk,
            "stroke":        stroke_risk,
            "diabetes":      diab_risk,
            "hypertension":  hyper_risk,
            "ckd":           ckd_risk,
        }


# ═══════════════════════════════════════════════════════════════════════════════
# 3.  APP LIFECYCLE & GLOBAL MODEL INSTANCE
# ═══════════════════════════════════════════════════════════════════════════════

bundle = ModelBundle()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load model saat startup, cleanup saat shutdown."""
    log.info("=== DHC ML Service starting up ===")
    bundle.load(MODEL_PATH)
    yield
    log.info("=== DHC ML Service shutting down ===")


# ═══════════════════════════════════════════════════════════════════════════════
# 4.  FASTAPI APP
# ═════════════════# ─── Schemas untuk /generate-health-plan ───

class DiseaseRiskDetail(BaseModel):
    probability: float = Field(..., ge=0.0, le=1.0)
    percentage:  str
    risk_level:  str  # "Low" | "Moderate" | "High"


class GenerateHealthPlanRequest(BaseModel):
    age:             int = Field(..., ge=0, le=120, description="Usia pasien (tahun)")
    gender:          str = Field(..., description="Jenis kelamin: Laki-laki / Perempuan")
    glucose:         float = Field(..., ge=30, le=600, description="Kadar glukosa darah (mg/dL)")
    blood_pressure:  int = Field(..., ge=40, le=250, description="Tekanan darah sistolik (mmHg)")
    bmi:             float = Field(..., ge=5, le=80, description="Body Mass Index (kg/m²)")
    activity_level:  str = Field(..., description="Tingkat aktivitas")
    smoking_status:  str = Field(..., description="Status merokok")
    urine_protein:   str = Field(..., description="Protein urine")
    hba1c:           float = Field(..., ge=2.0, le=20.0, description="Kadar HbA1c (%)")
    predictions:     dict[str, DiseaseRiskDetail] = Field(..., description="Hasil prediksi per penyakit")


class MealPlan(BaseModel):
    prinsip_utama: list[str] = Field(..., description="Prinsip utama diet/nutrisi")
    menu_harian:   dict[str, str] = Field(..., description="Menu harian: sarapan, snack_pagi, makan_siang, snack_sore, makan_malam")


class ActivityPlan(BaseModel):
    jenis_olahraga:        list[str] = Field(..., description="Jenis olahraga yang disarankan")
    frekuensi_per_minggu:  int = Field(..., description="Frekuensi olahraga per minggu")
    durasi_per_sesi_menit: int = Field(..., description="Durasi olahraga per sesi (menit)")
    intensitas:            str = Field(..., description="Intensitas olahraga: ringan / sedang / tinggi")
    catatan_keamanan:      list[str] = Field(..., description="Catatan keamanan saat olahraga")


class HealthPlanResponse(BaseModel):
    meal_plan:     MealPlan
    activity_plan: ActivityPlan
    disclaimer:    str


# ═══════════════════════════════════════════════════════════════════════════════
# 6.  HELPER FUNCTIONS
# ═══════════════════════════════════════════════════════════════════════════════

def risk_level(prob: float) -> str:
    """Kategorisasi tingkat risiko."""
    if prob < 0.30:
        return "Low"
    elif prob < 0.60:
        return "Moderate"
    return "High"


def build_feature_vector(req: PredictRequest) -> list[float]:
    """
    Konversi PredictRequest → list[float] sesuai urutan INPUT_FEATURES:
      [age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate]
    """
    return [
        req.age,
        req.gender,
        req.bmi,
        req.glucose,
        req.blood_pressure,
        req.cholesterol,
        req.heart_rate,
    ]


def generate_fallback_health_plan(req: GenerateHealthPlanRequest) -> dict:
    """
    Membuat rencana kesehatan berbasis aturan jika LLM tidak tersedia atau gagal.
    Menyesuaikan dengan kondisi risiko tertinggi:
      - Hipertensi: diet DASH rendah natrium
      - Diabetes/Prediabetes: karbohidrat terkontrol, rendah GI
      - Chronic Kidney Disease (CKD): batasi protein
      - Obesitas (BMI > 30): defisit kalori sedang
      - Sedentary: aktivitas fisik intensitas ringan, naik bertahap
    """
    # 1. Cari penyakit dengan probabilitas tertinggi dari predictions
    highest_disease = None
    highest_prob = -1.0
    
    # Normalisasi kunci agar aman (lowercase dan ganti spasi dengan underscore)
    normalized_predictions = {}
    for k, v in req.predictions.items():
        norm_k = k.lower().replace(" ", "_")
        normalized_predictions[norm_k] = v
        if v.probability > highest_prob:
            highest_prob = v.probability
            highest_disease = norm_k

    # Tentukan kondisi medis utama berdasarkan tingkat risiko tertinggi (jika probabilitas >= 0.30)
    # atau fallback berdasarkan kriteria klinis numerik
    if highest_prob >= 0.30:
        main_condition = highest_disease
    else:
        if req.blood_pressure >= 130:
            main_condition = "hypertension"
        elif req.glucose >= 100.0 or req.hba1c >= 5.7:
            main_condition = "diabetes"
        elif req.urine_protein.lower() in ["trace", "positive", "1+", "2+", "3+", "4+"]:
            main_condition = "ckd"
        else:
            main_condition = "general"

    # 2. Definisikan rencana default/umum (Heart Disease / Stroke / General)
    meal_plan = {
        "prinsip_utama": [
            "Batasi asupan lemak jenuh dan lemak trans (gorengan, mentega, daging berlemak).",
            "Konsumsi makanan tinggi serat larut dan asam lemak omega-3 (ikan seperti kembung, salmon, biji chia).",
            "Perbanyak buah, sayuran, dan kacang-kacangan untuk melindungi pembuluh darah.",
            "Batasi makanan olahan dan asupan gula berlebih."
        ],
        "menu_harian": {
            "sarapan": "Smoothie pisang-bayam dengan susu kedelai tanpa gula (1 gelas) dan 1 lembar roti gandum panggang",
            "snack_pagi": "Buah pepaya iris (1 mangkok kecil sekitar 100g)",
            "makan_siang": "Nasi merah (1 kepal), ikan kembung bakar (1 ekor sedang), tumis kangkung dengan sedikit minyak zaitun",
            "snack_sore": "Segenggam kecil kacang almond panggang tanpa garam (sekitar 10-15 butir)",
            "makan_malam": "Sup ayam bening dengan dada fillet tanpa kulit (50g), wortel, kentang, dan buncis"
        }
    }
    
    activity_plan = {
        "jenis_olahraga": ["Jalan kaki santai", "Berenang", "Bersepeda santai"],
        "frekuensi_per_minggu": 4,
        "durasi_per_sesi_menit": 30,
        "intensitas": "sedang",
        "catatan_keamanan": [
            "Lakukan pemanasan dan pendinginan minimal 5-10 menit untuk meminimalkan beban jantung mendadak.",
            "Hentikan segera jika merasakan nyeri dada, sesak napas tidak biasa, atau jantung berdebar terlalu kencang.",
            "Hindari berolahraga di bawah terik matahari ekstrem dan tetap terhidrasi dengan baik."
        ]
    }

    # 3. Sesuaikan dengan kondisi medis spesifik tertinggi
    # a. Hipertensi
    if main_condition == "hypertension":
        meal_plan["prinsip_utama"] = [
            "Batasi asupan garam/natrium di bawah 5 gram (sekitar 1 sendok teh) per hari.",
            "Perbanyak konsumsi sayuran, buah-buahan, dan biji-bijian utuh (pola makan DASH).",
            "Hindari makanan olahan, makanan kaleng, dan makanan cepat saji yang tinggi natrium.",
            "Pilih sumber protein rendah lemak seperti dada ayam tanpa kulit, ikan, dan tempe/tahu."
        ]
        meal_plan["menu_harian"] = {
            "sarapan": "Oatmeal dengan pisang iris dan segelas susu rendah lemak (1 mangkok porsi sedang)",
            "snack_pagi": "1 buah apel merah segar ukuran sedang",
            "makan_siang": "Nasi merah (1 kepal), dada ayam panggang tanpa kulit (100g) dengan rempah tanpa garam berlebih, dan tumis buncis wortel",
            "snack_sore": "Yogurt tawar rendah lemak (1 cup kecil sekitar 120ml)",
            "makan_malam": "Pepes tahu kukus (2 buah) dan sayur bening bayam (1 mangkok sedang)"
        }
        activity_plan["jenis_olahraga"] = ["Jalan cepat (brisk walking)", "Bersepeda santai", "Berenang"]
        activity_plan["catatan_keamanan"] = [
            "Hindari latihan beban berat dengan menahan napas (Valsalva maneuver).",
            "Pantau tekanan darah sebelum dan sesudah berolahraga. Jangan berolahraga jika sistolik >180 mmHg.",
            "Segera hentikan olahraga jika merasa pusing, sakit kepala, dada sesak, atau napas tersengal berlebihan."
        ]

    # b. Diabetes / Prediabetes
    elif main_condition == "diabetes":
        meal_plan["prinsip_utama"] = [
            "Pilih makanan dengan indeks glikemik rendah untuk mencegah lonjakan gula darah mendadak.",
            "Batasi karbohidrat sederhana seperti gula pasir, sirup, nasi putih berlebih, dan makanan berbahan dasar tepung.",
            "Terapkan metode piring makan: setengah piring sayuran non-tepung, seperempat protein, seperempat karbohidrat kompleks.",
            "Konsumsi serat larut air dari buah-buahan seperti pir dan apel beserta sayuran hijau."
        ]
        meal_plan["menu_harian"] = {
            "sarapan": "Roti gandum utuh (2 lembar) dengan telur rebus (1 butir) dan alpukat iris (setengah buah)",
            "snack_pagi": "Segenggam kacang almond panggang tanpa garam (sekitar 10-12 butir)",
            "makan_siang": "Nasi merah atau quinoa (1 kepal), pepes ikan kembung (1 ekor sedang), dan lalapan sayur kukus (kol, brokoli)",
            "snack_sore": "Potongan pepaya segar (1 mangkok kecil sekitar 100g)",
            "makan_malam": "Tumis tahu tempe dengan sedikit minyak (1 piring sedang) dan sup bening brokoli wortel"
        }
        activity_plan["jenis_olahraga"] = ["Jalan santai/cepat", "Senam diabetes", "Bersepeda"]
        activity_plan["frekuensi_per_minggu"] = 5
        activity_plan["catatan_keamanan"] = [
            "Periksa kadar gula darah sebelum berolahraga; jika <100 mg/dL, konsumsi snack kecil dahulu untuk mencegah hipoglikemia.",
            "Selalu bawa permen atau sumber glukosa cepat serap saat berolahraga sebagai antisipasi darurat hipoglikemia.",
            "Gunakan alas kaki dan kaos kaki yang nyaman dan pas untuk menghindari luka atau lecet pada kaki."
        ]

    # c. Chronic Kidney Disease (CKD)
    elif main_condition == "ckd":
        meal_plan["prinsip_utama"] = [
            "Batasi asupan protein harian (sekitar 0.6 - 0.8 gram per kg berat badan) untuk meringankan beban kerja ginjal.",
            "Batasi makanan tinggi kalium (seperti pisang, alpukat, kentang) jika ada kecenderungan kalium darah tinggi.",
            "Batasi makanan tinggi fosfor seperti produk susu sapi, kacang-kacangan, dan minuman bersoda.",
            "Batasi asupan natrium/garam untuk mengontrol tekanan darah dan penumpukan cairan tubuh."
        ]
        meal_plan["menu_harian"] = {
            "sarapan": "Bihun goreng dengan sedikit minyak dan sedikit sayur sawi hijau (1 porsi sedang, dimasak minim garam)",
            "snack_pagi": "1 buah apel kupas ukuran sedang",
            "makan_siang": "Nasi putih (1 kepal kecil), dada ayam panggang (porsi kecil sekitar 50g), dan tumis labu siam dengan bumbu rempah bebas natrium",
            "snack_sore": "Buah pir manis (1 buah sedang)",
            "makan_malam": "Nasi putih (1 kepal kecil), tahu kukus (1 buah sedang), dan sup bening oyong/gambas"
        }
        activity_plan["jenis_olahraga"] = ["Jalan kaki santai", "Senam ringan di rumah", "Yoga peregangan"]
        activity_plan["frekuensi_per_minggu"] = 3
        activity_plan["durasi_per_sesi_menit"] = 20
        activity_plan["catatan_keamanan"] = [
            "Lakukan olahraga dengan intensitas yang nyaman dan jangan memaksakan diri melampaui batas kemampuan.",
            "Perhatikan asupan cairan selama berolahraga agar tetap terhidrasi tanpa berlebihan (sesuai instruksi dokter).",
            "Hentikan olahraga jika merasa sangat lelah, pusing, atau sesak napas."
        ]

    # 4. Intervensi Obesitas Tambahan (BMI > 30)
    if req.bmi > 30.0:
        meal_plan["prinsip_utama"].insert(0, "Terapkan defisit kalori sedang (kurangi sekitar 300-500 kkal dari kebutuhan energi harian Anda).")
        meal_plan["prinsip_utama"].append("Tingkatkan konsumsi protein dan serat untuk membantu mempertahankan massa otot dan rasa kenyang lebih lama.")
        activity_plan["jenis_olahraga"] = ["Berenang (sangat direkomendasikan untuk melindungi sendi)", "Sepeda statis", "Jalan santai/cepat (low impact)"]
        activity_plan["catatan_keamanan"].append("Pilih jenis olahraga low-impact untuk menghindari cedera atau nyeri pada persendian lutut dan pergelangan kaki akibat beban berat.")

    # 5. Intervensi Sedentary
    act_lower = req.activity_level.lower()
    is_sedentary = "sedentary" in act_lower or "kurang" in act_lower or "pasif" in act_lower or "tidak aktif" in act_lower
    if is_sedentary:
        activity_plan["intensitas"] = "ringan"
        activity_plan["frekuensi_per_minggu"] = min(3, activity_plan["frekuensi_per_minggu"])
        activity_plan["durasi_per_sesi_menit"] = min(20, activity_plan["durasi_per_sesi_menit"])
        activity_plan["catatan_keamanan"].append("Karena Anda jarang beraktivitas fisik (sedentary), mulailah olahraga secara perlahan dari intensitas ringan dan tingkatkan durasi secara bertahap.")
    else:
        activity_plan["catatan_keamanan"].append("Lakukan pemanasan minimal 5-10 menit sebelum memulai olahraga untuk mencegah cedera otot.")

    return {
        "meal_plan": meal_plan,
        "activity_plan": activity_plan,
        "disclaimer": "Rekomendasi rencana kesehatan ini dibuat berdasarkan analisis data kesehatan Anda secara otomatis menggunakan bantuan AI/aturan kesehatan umum dan hanya bertujuan sebagai panduan umum. Rekomendasi ini bukan pengganti diagnosis, saran medis, maupun rencana perawatan dari dokter atau tenaga medis profesional. Selalu berkonsultasi dengan dokter sebelum memulai program diet atau olahraga baru."
    }


def validate_and_sanitize_plan(data: dict, fallback_data: dict) -> dict:
    """
    Memastikan struktur data JSON yang dikembalikan LLM sesuai dengan format yang diinginkan.
    Jika ada kunci yang hilang atau tipe data yang tidak sesuai, disanitasi menggunakan data fallback.
    """
    try:
        sanitized = {}
        
        # 1. Validasi meal_plan
        meal_plan = data.get("meal_plan", {})
        if not isinstance(meal_plan, dict):
            meal_plan = {}
            
        prinsip = meal_plan.get("prinsip_utama")
        if not isinstance(prinsip, list):
            prinsip = fallback_data["meal_plan"]["prinsip_utama"]
        else:
            prinsip = [str(x) for x in prinsip]
            
        menu = meal_plan.get("menu_harian")
        if not isinstance(menu, dict):
            menu = {}
            
        sanitized_menu = {}
        fallback_menu = fallback_data["meal_plan"]["menu_harian"]
        for key in ["sarapan", "snack_pagi", "makan_siang", "snack_sore", "makan_malam"]:
            sanitized_menu[key] = str(menu.get(key, fallback_menu[key]))
            
        sanitized["meal_plan"] = {
            "prinsip_utama": prinsip,
            "menu_harian": sanitized_menu
        }
        
        # 2. Validasi activity_plan
        activity_plan = data.get("activity_plan", {})
        if not isinstance(activity_plan, dict):
            activity_plan = {}
            
        jenis_olahraga = activity_plan.get("jenis_olahraga")
        if not isinstance(jenis_olahraga, list):
            jenis_olahraga = fallback_data["activity_plan"]["jenis_olahraga"]
        else:
            jenis_olahraga = [str(x) for x in jenis_olahraga]
            
        try:
            frekuensi = int(activity_plan.get("frekuensi_per_minggu", fallback_data["activity_plan"]["frekuensi_per_minggu"]))
        except (ValueError, TypeError):
            frekuensi = fallback_data["activity_plan"]["frekuensi_per_minggu"]
            
        try:
            durasi = int(activity_plan.get("durasi_per_sesi_menit", fallback_data["activity_plan"]["durasi_per_sesi_menit"]))
        except (ValueError, TypeError):
            durasi = fallback_data["activity_plan"]["durasi_per_sesi_menit"]
            
        intensitas = str(activity_plan.get("intensitas", fallback_data["activity_plan"]["intensitas"]))
        if intensitas not in ["ringan", "sedang", "tinggi"]:
            if "light" in intensitas.lower():
                intensitas = "ringan"
            elif "mod" in intensitas.lower() or "medium" in intensitas.lower():
                intensitas = "sedang"
            elif "high" in intensitas.lower() or "hard" in intensitas.lower():
                intensitas = "tinggi"
            else:
                intensitas = fallback_data["activity_plan"]["intensitas"]
                
        catatan = activity_plan.get("catatan_keamanan")
        if not isinstance(catatan, list):
            catatan = fallback_data["activity_plan"]["catatan_keamanan"]
        else:
            catatan = [str(x) for x in catatan]
            
        sanitized["activity_plan"] = {
            "jenis_olahraga": jenis_olahraga,
            "frekuensi_per_minggu": frekuensi,
            "durasi_per_sesi_menit": durasi,
            "intensitas": intensitas,
            "catatan_keamanan": catatan
        }
        
        # 3. Disclaimer
        sanitized["disclaimer"] = str(data.get("disclaimer", fallback_data["disclaimer"]))
        
        return sanitized
    except Exception as e:
        log.warning(f"Error sanitizing LLM response: {e}. Menggunakan fallback penuh.")
        return fallback_data


async def generate_plan_with_llm(req: GenerateHealthPlanRequest, fallback_data: dict) -> dict:
    """
    Mencoba melakukan pemanggilan ke Gemini API (atau OpenAI API jika dikonfigurasi)
    untuk mendapatkan rekomendasi kesehatan personal yang dinamis.
    Jika gagal atau tidak dikonfigurasi, mengembalikan fallback_data.
    """
    gemini_key = os.environ.get("GEMINI_API_KEY")
    openai_key = os.environ.get("OPENAI_API_KEY")
    
    if gemini_key and ("your_api_key" in gemini_key.lower() or gemini_key.strip() == ""):
        gemini_key = None
    if openai_key and ("your_api_key" in openai_key.lower() or openai_key.strip() == ""):
        openai_key = None
        
    if not gemini_key and not openai_key:
        log.info("Tidak ada API Key LLM (Gemini/OpenAI) yang dikonfigurasi di environment. Menggunakan fallback berbasis aturan.")
        return fallback_data

    # Bikin prompt
    prompt = f"""
Anda adalah asisten perencana kesehatan profesional AI.
Tugas Anda adalah merancang rencana kesehatan personal (health plan) berdasarkan data kesehatan pengguna dan hasil prediksi risiko penyakit berikut.

DATA KESEHATAN PENGGUNA:
- Usia: {req.age} tahun
- Jenis Kelamin: {req.gender}
- BMI: {req.bmi} (Kategori: {'Obesitas (BMI > 30)' if req.bmi > 30 else 'Overweight' if req.bmi > 25 else 'Normal' if req.bmi >= 18.5 else 'Underweight'})
- Glukosa: {req.glucose} mg/dL
- Tekanan Darah Sistolik: {req.blood_pressure} mmHg
- Tingkat Aktivitas: {req.activity_level}
- Status Merokok: {req.smoking_status}
- Protein Urine: {req.urine_protein}
- HbA1c: {req.hba1c}%

PREDIKSI RISIKO PENYAKIT (Hasil dari Model ML):
{json.dumps({k: v.model_dump() for k, v in req.predictions.items()}, indent=2)}

Anda WAJIB mengembalikan output dalam format JSON terstruktur dengan struktur kunci dan tipe data persis seperti skema berikut (Gunakan bahasa Indonesia yang jelas, sopan, dan berikan rekomendasi menu konkret beserta estimasi porsi):

{{
  "meal_plan": {{
    "prinsip_utama": [
      "Prinsip makan 1...",
      "Prinsip makan 2..."
    ],
    "menu_harian": {{
      "sarapan": "Deskripsi menu sarapan konkret lengkap dengan estimasi porsi",
      "snack_pagi": "Deskripsi menu snack pagi konkret lengkap dengan estimasi porsi",
      "makan_siang": "Deskripsi menu makan siang konkret lengkap dengan estimasi porsi",
      "snack_sore": "Deskripsi menu snack sore konkret lengkap dengan estimasi porsi",
      "makan_malam": "Deskripsi menu makan malam konkret lengkap dengan estimasi porsi"
    }}
  }},
  "activity_plan": {{
    "jenis_olahraga": ["Nama olahraga 1", "Nama olahraga 2"],
    "frekuensi_per_minggu": 4, // harus bertipe integer
    "durasi_per_sesi_menit": 30, // harus bertipe integer
    "intensitas": "ringan/sedang/tinggi", // pilih salah satu
    "catatan_keamanan": [
      "Catatan keamanan 1...",
      "Catatan keamanan 2..."
    ]
  }},
  "disclaimer": "Peringatan medis bahwa rekomendasi ini bersifat umum dan bukan pengganti konsultasi dengan dokter."
}}

Catatan penting:
- Jika tingkat aktivitas pengguna adalah "sedentary" (jarang beraktivitas/kurang aktif), mulailah rencana aktivitas dari intensitas "ringan" dengan peningkatan bertahap, dan berikan catatan keamanan khusus terkait kondisi sedentary.
- Sesuaikan rencana makan dengan kondisi risiko tertinggi:
  * Jika risiko tertinggi adalah hipertensi, gunakan prinsip diet DASH rendah natrium (batasi garam di bawah 5 gram per hari, perbanyak sayur dan buah, hindari makanan olahan nat    return fallback_data

risiko tertinggi adalah diabetes/prediabetes, gunakan karbohidrat kompleks terkontrol dan indeks glikemik rendah.
  * Jika risiko tertinggi adalah chronic kidney disease (CKD), batasi asupan protein.
  * Jika pengguna memiliki obesitas (BMI > 30), sertakan rekomendasi defisit kalori sedang.
- Menu makanan harus konkret, mudah dibuat, dan relevan dengan bahan makanan di Indonesia.
- Kembalikan HANYA string JSON yang valid, tanpa teks penjelasan tambahan di luar JSON.
"""

    try:
        if gemini_key:
            log.info("Mengirim permintaan pembuatan rencana kesehatan ke Gemini API...")
            url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={gemini_key}"
            payload = {
                "contents": [{"parts": [{"text": prompt}]}],
                "generationConfig": {
                    "responseMimeType": "application/json"
                }
            }
            async with httpx.AsyncClient(timeout=30.0) as client:
                resp = await client.post(url, json=payload)
                if resp.status_code == 200:
                    result = resp.json()
                    text = result["candidates"][0]["content"]["parts"][0]["text"]
                    data = json.loads(text.strip())
                    return validate_and_sanitize_plan(data, fallback_data)
                else:
                    log.error(f"Gemini API error ({resp.status_code}): {resp.text}")
                    
        if openai_key:
            log.info("Mengirim permintaan pembuatan rencana kesehatan ke OpenAI API...")
            url = "https://api.openai.com/v1/chat/completions"
            headers = {
                "Authorization": f"Bearer {openai_key}",
                "Content-Type": "application/json"
            }
            payload = {
                "model": "gpt-4o-mini",
                "messages": [
                    {"role": "system", "content": "You are a professional medical health planner. You must return output ONLY as valid JSON matching the requested structure in Indonesian language."},
                    {"role": "user", "content": prompt}
                ],
                "response_format": {"type": "json_object"}
            }
            async with httpx.AsyncClient(timeout=30.0) as client:
                resp = await client.post(url, json=payload, headers=headers)
                if resp.status_code == 200:
                    result = resp.json()
                    text = result["choices"][0]["message"]["content"]
                    data = json.loads(text.strip())
                    return validate_and_sanitize_plan(data, fallback_data)
                else:
                    log.error(f"OpenAI API error ({resp.status_code}): {resp.text}")
                    
    except Exception as exc:
        log.error(f"Gagal memanggil atau memparsing LLM API: {exc}. Menggunakan fallback berbasis aturan.")
        
    return fallback_data




# ═══════════════════════════════════════════════════════════════════════════════
# 7.  ENDPOINTS
# ═══════════════════════════════════════════════════════════════════════════════

@app.get("/health", tags=["Utility"])
async def health_check():
    """
    Health-check endpoint.
    Kembalikan status service dan info model yang di-load.
    """
    return {
        "status":     "ok",
        "service":    "DHC Multi-Disease Prediction API",
        "version":    "1.0.0",
        "model_path": str(MODEL_PATH),
        "model_mode": bundle.mode,
        "model_loaded": bundle.loaded,
        "diseases":   DISEASE_LABELS,
        "features":   INPUT_FEATURES,
    }


@app.post("/predict", response_model=PredictResponse, tags=["Prediction"])
async def predict(req: PredictRequest):
    """
    **Prediksi Multi-Penyakit**

    Terima data kesehatan pasien, kembalikan probabilitas risiko untuk:
    - Heart Disease
    - Stroke
    - Diabetes
    - Hypertension
    - Chronic Kidney Disease (CKD)

    ### Cara kerja:
    1. Input JSON divalidasi oleh Pydantic
    2. Dikonversi ke `list[float]` sesuai urutan fitur model
    3. Dinormalisasi (StandardScaler tersimpan di checkpoint)
    4. Dikonversi ke `torch.Tensor` dan di-forward ke `MultiDiseaseNN`
    5. Output sigmoid → probabilitas per penyakit dikembalikan sebagai JSON
    """
    try:
        # ── Build feature vector ─────────────────────────────────────────────
        feature_vector = build_feature_vector(req)
        log.info(f"Prediksi diminta — fitur: {dict(zip(INPUT_FEATURES, feature_vector))}")

        # ── Inference ────────────────────────────────────────────────────────
        raw_probs: dict[str, float] = bundle.predict(feature_vector)

        # ── Format response ──────────────────────────────────────────────────
        disease_risks: list[DiseaseRisk] = []
        for key in DISEASE_LABELS:
            prob = raw_probs[key]
            disease_risks.append(
                DiseaseRisk(
                    disease     = key,
                    label       = DISEASE_DISPLAY[key],
                    probability = round(prob, 4),
                    percentage  = f"{prob * 100:.1f}%",
                    risk_level  = risk_level(prob),
                )
            )

        highest = max(disease_risks, key=lambda d: d.probability)

        log.info(
            f"Prediksi selesai — tertinggi: {highest.label} ({highest.percentage})"
        )

        return PredictResponse(
            status       = "success",
            model_mode   = bundle.mode,
            input_echo   = req.model_dump(),
            predictions  = disease_risks,
            highest_risk = f"{highest.label} ({highest.percentage})",
            disclaimer   = (
                "Hasil ini hanya untuk tujuan edukasi dan skrining awal. "
                "Bukan pengganti diagnosis medis profesional."
            ),
        )

    except Exception as exc:
        log.exception(f"Error saat prediksi: {exc}")
        raise HTTPException(status_code=500, detail=f"Prediction error: {str(exc)}")


@app.post("/generate-health-plan", response_model=HealthPlanResponse, tags=["Health Plan"])
async def generate_health_plan(req: GenerateHealthPlanRequest):
    """
    **Generate Rencana Kesehatan Personal**

    Terima data kesehatan pasien dan hasil prediksi risiko penyakit,
    lalu buat rekomendasi menu makan (*meal plan*) dan rencana aktivitas fisik (*activity plan*)
    yang disesuaikan dengan kondisi medis paling dominan / berisiko paling tinggi.

    Menggunakan LLM (Gemini/OpenAI) sebagai sumber utama, jika tidak tersedia atau gagal
    maka akan menggunakan fallback berbasis aturan kesehatan umum yang terstruktur.
    """
    try:
        log.info(f"Generate health plan diminta untuk pasien usia {req.age}, BMI {req.bmi}")
        
        # 1. Buat data fallback berbasis aturan terlebih dahulu
        fallback_data = generate_fallback_health_plan(req)
        
        # 2. Panggil LLM (akan otomatis menggunakan fallback jika LLM gagal atau tidak dikonfigurasi)
        plan = await generate_plan_with_llm(req, fallback_data)
        
        return HealthPlanResponse(**plan)
        
    except Exception as exc:
        log.exception(f"Error saat generate health plan: {exc}")
        raise HTTPException(status_code=500, detail=f"Health plan generation error: {str(exc)}")



# ─── Dev entrypoint ───────────────────────────────────────────────────────────
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8001,
        reload=True,
        log_level="info",
    )
