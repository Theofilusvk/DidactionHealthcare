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
import logging
from pathlib import Path
from contextlib import asynccontextmanager

# ─── Third-party ─────────────────────────────────────────────────────────────
import numpy as np
import torch
import torch.nn as nn
import torch.nn.functional as F
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
        import pandas as pd
        
        # Mapping dari INPUT_FEATURES [age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate]
        age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate = feature_vector

        results = {}

        # 1. Heart Disease
        # Fitur: ['age', 'sex', 'cp', 'trestbps', 'chol', 'fbs', 'restecg', 'thalach', 'exang', 'oldpeak', 'slope', 'ca', 'thal']
        if "heart_disease" in self.xgb_models:
            model = self.xgb_models["heart_disease"]
            fbs_val = 1.0 if glucose > 120.0 else 0.0
            heart_df = pd.DataFrame([{
                'age': age,
                'sex': gender,
                'cp': 0.0,
                'trestbps': blood_pressure,
                'chol': cholesterol,
                'fbs': fbs_val,
                'restecg': 0.0,
                'thalach': heart_rate,
                'exang': 0.0,
                'oldpeak': 0.0,
                'slope': 1.0,
                'ca': 0.0,
                'thal': 2.0
            }])
            results["heart_disease"] = float(model.predict_proba(heart_df)[0][1])

        # 2. Stroke
        # Fitur: ['age', 'avg_glucose_level', 'bmi']
        if "stroke" in self.xgb_models:
            model = self.xgb_models["stroke"]
            stroke_df = pd.DataFrame([{
                'age': age,
                'avg_glucose_level': glucose,
                'bmi': bmi
            }])
            results["stroke"] = float(model.predict_proba(stroke_df)[0][1])

        # 3. Diabetes
        # Fitur: ['Pregnancies', 'Glucose', 'BloodPressure', 'SkinThickness', 'Insulin', 'BMI', 'DiabetesPedigreeFunction', 'Age']
        if "diabetes" in self.xgb_models:
            model = self.xgb_models["diabetes"]
            diab_df = pd.DataFrame([{
                'Pregnancies': 0.0,
                'Glucose': glucose,
                'BloodPressure': blood_pressure,
                'SkinThickness': 23.0,
                'Insulin': 30.0,
                'BMI': bmi,
                'DiabetesPedigreeFunction': 0.37,
                'Age': age
            }])
            results["diabetes"] = float(model.predict_proba(diab_df)[0][1])

        # 4. Hypertension
        # Fitur: ['Age', 'Systolic_BP', 'Diastolic_BP', 'Glucose', 'BMI']
        if "hypertension" in self.xgb_models:
            model = self.xgb_models["hypertension"]
            hyper_df = pd.DataFrame([{
                'Age': age,
                'Systolic_BP': blood_pressure,
                'Diastolic_BP': 80.0,
                'Glucose': glucose,
                'BMI': bmi
            }])
            results["hypertension"] = float(model.predict_proba(hyper_df)[0][1])

        # 5. CKD
        # Fitur: ['age', 'avg_glucose_level', 'bmi']
        if "ckd" in self.xgb_models:
            model = self.xgb_models["ckd"]
            ckd_df = pd.DataFrame([{
                'age': age,
                'avg_glucose_level': glucose,
                'bmi': bmi
            }])
            results["ckd"] = float(model.predict_proba(ckd_df)[0][1])

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
# ═══════════════════════════════════════════════════════════════════════════════

app = FastAPI(
    title="DHC Multi-Disease Prediction API",
    description=(
        "Prediksi probabilitas 5 penyakit (Heart Disease, Stroke, Diabetes, "
        "Hypertension, CKD) dari fitur kesehatan pasien. "
        "Powered by PyTorch / XGBoost — Didaction Healthcare."
    ),
    version="1.0.0",
    lifespan=lifespan,
    docs_url="/docs",
    redoc_url="/redoc",
)

# CORS — izinkan request dari Laravel (port 8000/8001)
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:8000",
        "http://127.0.0.1:8000",
        "http://localhost:3000",
        "*",  # Ubah ke domain spesifik di produksi
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ═══════════════════════════════════════════════════════════════════════════════
# 5.  PYDANTIC SCHEMAS
# ═══════════════════════════════════════════════════════════════════════════════

class PredictRequest(BaseModel):
    """
    Data input pasien untuk prediksi penyakit.
    Semua field opsional — nilai yang tidak diberikan akan di-impute dengan median.
    """
    age:            float = Field(...,  ge=0,   le=120, description="Usia pasien (tahun)")
    gender:         float = Field(1.0,  ge=0,   le=1,   description="Jenis kelamin: 0=Perempuan, 1=Laki-laki")
    bmi:            float = Field(...,  ge=5,   le=80,  description="Body Mass Index (kg/m²)")
    glucose:        float = Field(...,  ge=30,  le=600, description="Kadar glukosa darah (mg/dL)")
    blood_pressure: float = Field(...,  ge=40,  le=250, description="Tekanan darah sistolik (mmHg)")
    cholesterol:    float = Field(200.0, ge=50, le=600, description="Total kolesterol (mg/dL)")
    heart_rate:     float = Field(75.0,  ge=20, le=250, description="Detak jantung (bpm)")

    model_config = {
        "json_schema_extra": {
            "example": {
                "age":            45,
                "gender":         1,
                "bmi":            27.5,
                "glucose":        140.0,
                "blood_pressure": 130,
                "cholesterol":    220.0,
                "heart_rate":     88,
            }
        }
    }


class DiseaseRisk(BaseModel):
    disease:     str
    label:       str
    probability: float = Field(..., ge=0.0, le=1.0)
    percentage:  str
    risk_level:  str   # "Low" | "Moderate" | "High"


class PredictResponse(BaseModel):
    status:       str
    model_mode:   str
    input_echo:   dict
    predictions:  list[DiseaseRisk]
    highest_risk: str
    disclaimer:   str


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
