# DHC Python ML Service

FastAPI service untuk prediksi multi-penyakit menggunakan PyTorch / XGBoost.

## Struktur File

```
DHC/
├── python-ml-service/
│   ├── main.py            ← FastAPI app (file ini)
│   └── requirements.txt   ← Dependencies
├── models/
│   └── multidisease_model.pth  ← File model (generate dari notebook)
└── Models/
    └── DidactionModel_01.ipynb ← Notebook training
```

## Instalasi

```bash
cd python-ml-service
pip install -r requirements.txt
```

## Menjalankan Service

```bash
# Development (port 8001, auto-reload)
uvicorn main:app --reload --port 8001

# Atau langsung
python main.py
```

## Endpoint

### `GET /health`
Cek status service dan info model.

```json
{
  "status": "ok",
  "model_mode": "pytorch",
  "model_loaded": true,
  "diseases": ["heart_disease", "stroke", "diabetes", "hypertension", "ckd"]
}
```

### `POST /predict`
Prediksi risiko 5 penyakit dari data pasien.

**Request Body:**
```json
{
  "age": 45,
  "gender": 1,
  "bmi": 27.5,
  "glucose": 140.0,
  "blood_pressure": 130,
  "cholesterol": 220.0,
  "heart_rate": 88
}
```

| Field            | Tipe  | Deskripsi                          | Required |
|------------------|-------|------------------------------------|----------|
| `age`            | float | Usia (tahun), 0–120                | ✅       |
| `gender`         | float | 0=Perempuan, 1=Laki-laki           | ❌ (def: 1) |
| `bmi`            | float | Body Mass Index (kg/m²)            | ✅       |
| `glucose`        | float | Glukosa darah (mg/dL)              | ✅       |
| `blood_pressure` | float | Tekanan darah sistolik (mmHg)      | ✅       |
| `cholesterol`    | float | Total kolesterol (mg/dL)           | ❌ (def: 200) |
| `heart_rate`     | float | Detak jantung (bpm)                | ❌ (def: 75) |

**Response:**
```json
{
  "status": "success",
  "model_mode": "pytorch",
  "predictions": [
    {
      "disease": "diabetes",
      "label": "Diabetes",
      "probability": 0.6823,
      "percentage": "68.2%",
      "risk_level": "High"
    },
    ...
  ],
  "highest_risk": "Diabetes (68.2%)"
}
```

## Arsitektur `MultiDiseaseNN`

```
Input (7 fitur)
    ↓
Linear(7→128) + BatchNorm + ReLU
    ↓
Linear(128→64) + BatchNorm + ReLU
    ↓
Linear(64→32) + ReLU + Dropout(0.3)
    ↓
Linear(32→5)   ← output head
    ↓
Sigmoid()      ← probabilitas per penyakit [0,1]
```

## Export Model dari Notebook

Tambahkan kode ini di akhir notebook `DidactionModel_01.ipynb`:

```python
import torch
import joblib

# Jika menggunakan PyTorch
checkpoint = {
    "model_state": model.state_dict(),
    "scaler_mean": scaler.mean_.tolist(),
    "scaler_std":  scaler.scale_.tolist(),
}
torch.save(checkpoint, "../models/multidisease_model.pth")
print("Model berhasil disimpan!")
```

## Mode Fallback

Jika file `.pth` belum ada, service otomatis menggunakan **mode fallback** (estimasi heuristik sederhana). Endpoint tetap berfungsi, tapi akurasi tidak terjamin. Selalu generate model dari notebook sebelum deployment.

## Dokumentasi Interaktif

Setelah service berjalan, buka:
- **Swagger UI**: http://localhost:8001/docs
- **ReDoc**: http://localhost:8001/redoc
