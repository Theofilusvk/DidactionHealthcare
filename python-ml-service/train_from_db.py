#!/usr/bin/env python3
"""
===========================================================
  DHC — Train XGBoost dari MySQL health_records
===========================================================

Arsitektur Opsi B:
  health_records (MySQL)   ← seeded dari CSV datasets
       ↓ query SELECT
  DataFrame (training data)
       ↓ train/test split (80/20)
  XGBoost per penyakit
       ↓ pickle.dump
  python-ml-service/models/*.pkl
       ↓ loaded by main.py (FastAPI)
  POST /predict  ← input user (test/inference)

Jalankan:
  python python-ml-service/train_from_db.py

Prerequisite:
  pip install pymysql pandas scikit-learn xgboost imbalanced-learn python-dotenv
"""

import os
import sys
import pickle
import warnings
from pathlib import Path

warnings.filterwarnings('ignore')

# ── Dependency check ──────────────────────────────────────────────────────────
REQUIRED = ['pymysql', 'pandas', 'sklearn', 'xgboost', 'imblearn', 'dotenv']
missing = []
for pkg in REQUIRED:
    try:
        __import__(pkg if pkg != 'dotenv' else 'dotenv')
    except ImportError:
        missing.append('python-dotenv' if pkg == 'dotenv' else pkg)

if missing:
    print(f"[ERROR] Package berikut belum terinstal: {', '.join(missing)}")
    print(f"  Instal dengan: pip install {' '.join(missing)}")
    sys.exit(1)

import pandas as pd
import numpy as np
from dotenv import load_dotenv
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, roc_auc_score
from xgboost import XGBClassifier

# ── Paths ─────────────────────────────────────────────────────────────────────
SCRIPT_DIR  = Path(__file__).resolve().parent
MODELS_DIR  = SCRIPT_DIR / 'models'
LARAVEL_DIR = SCRIPT_DIR.parent / 'DidactionHealthcare'
ENV_FILE    = LARAVEL_DIR / '.env'

MODELS_DIR.mkdir(parents=True, exist_ok=True)

# ── Load .env dari Laravel ────────────────────────────────────────────────────
if ENV_FILE.exists():
    load_dotenv(ENV_FILE)
    print(f"[INFO] .env dimuat dari: {ENV_FILE}")
else:
    print(f"[WARN] .env tidak ditemukan di {ENV_FILE}, menggunakan environment variable sistem")

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_NAME = os.getenv('DB_DATABASE', 'didaction_healthcare')
DB_USER = os.getenv('DB_USERNAME', 'root')
DB_PASS = os.getenv('DB_PASSWORD', '')


# ═══════════════════════════════════════════════════════════════════════════════
# 1.  AMBIL DATA DARI MySQL
# ═══════════════════════════════════════════════════════════════════════════════

def load_from_db() -> pd.DataFrame:
    """
    Query health_records dari MySQL.
    Kolom yang diambil:
      Fitur  : age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate
      Label  : diabetes_risk, hypertension_risk, heart_disease_risk, stroke_risk, ckd_risk
    """
    import pymysql

    print(f"\n[DB] Koneksi ke MySQL {DB_USER}@{DB_HOST}:{DB_PORT}/{DB_NAME} ...")
    try:
        conn = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            db=DB_NAME,
            user=DB_USER,
            password=DB_PASS,
            charset='utf8mb4',
        )
    except Exception as e:
        print(f"[ERROR] Gagal koneksi ke database: {e}")
        sys.exit(1)

    query = """
        SELECT
            age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate,
            diabetes_risk, hypertension_risk, heart_disease_risk, stroke_risk, ckd_risk
        FROM health_records
        WHERE
            deleted_at IS NULL
            AND age IS NOT NULL
            AND bmi IS NOT NULL
            AND glucose IS NOT NULL
            AND blood_pressure IS NOT NULL
            AND (
                diabetes_risk IS NOT NULL
                OR hypertension_risk IS NOT NULL
                OR heart_disease_risk IS NOT NULL
                OR stroke_risk IS NOT NULL
                OR ckd_risk IS NOT NULL
            )
        ORDER BY id
    """

    df = pd.read_sql(query, conn)
    conn.close()

    print(f"[DB] {len(df)} baris berhasil diambil dari health_records")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# 2.  PREPROCESSING
# ═══════════════════════════════════════════════════════════════════════════════

FEATURE_COLS = ['age', 'gender', 'bmi', 'glucose', 'blood_pressure', 'cholesterol', 'heart_rate']

DISEASE_TARGETS = {
    'diabetes'      : 'diabetes_risk',
    'hypertension'  : 'hypertension_risk',
    'heart_disease' : 'heart_disease_risk',
    'stroke'        : 'stroke_risk',
    'ckd'           : 'ckd_risk',
}

def preprocess(df: pd.DataFrame) -> pd.DataFrame:
    """
    1. Isi nilai kosong dengan median kolom
    2. Binarisasi label: nilai ≥0.5 → 1 (sakit), <0.5 → 0 (sehat)
       karena XGBoost membutuhkan label biner, bukan probabilitas
    3. Validasi range fitur
    """
    df = df.copy()

    # Isi missing values dengan median
    for col in FEATURE_COLS:
        if col in df.columns:
            median = df[col].median()
            df[col] = df[col].fillna(median)

    # Isi default kolom opsional jika kosong
    if 'cholesterol' in df.columns:
        df['cholesterol'] = df['cholesterol'].fillna(200.0)
    if 'heart_rate' in df.columns:
        df['heart_rate'] = df['heart_rate'].fillna(75.0)

    # Binarisasi label (threshold 0.5)
    for disease, col in DISEASE_TARGETS.items():
        if col in df.columns:
            df[f'{disease}_label'] = (df[col] >= 0.5).astype(int)
        else:
            df[f'{disease}_label'] = 0

    print(f"\n[PRE] Shape setelah preprocessing: {df.shape}")
    for disease in DISEASE_TARGETS:
        label_col = f'{disease}_label'
        if label_col in df.columns:
            pos = df[label_col].sum()
            pct = pos / len(df) * 100
            print(f"  {disease:15s}: {int(pos):5d} positif / {len(df)} total ({pct:.1f}%)")

    return df


# ═══════════════════════════════════════════════════════════════════════════════
# 3.  TRAINING
# ═══════════════════════════════════════════════════════════════════════════════

XGB_PARAMS = dict(
    n_estimators  = 200,
    max_depth     = 5,
    learning_rate = 0.1,
    subsample     = 0.8,
    colsample_bytree = 0.8,
    use_label_encoder = False,
    eval_metric   = 'logloss',
    random_state  = 42,
    n_jobs        = -1,
)

def train_disease_model(
    df: pd.DataFrame,
    disease: str,
) -> XGBClassifier | None:
    """
    Melatih XGBoost untuk satu penyakit.

    - X   : 7 fitur kesehatan (FEATURE_COLS)
    - y   : label biner penyakit
    - Split: 80% train / 20% test (stratified)
    - Input user saat prediksi → data TEST (inference)
    """
    label_col = f'{disease}_label'
    if label_col not in df.columns:
        print(f"  [SKIP] Kolom label '{label_col}' tidak ada")
        return None

    X = df[FEATURE_COLS].values
    y = df[label_col].values

    # Cek distribusi kelas
    n_pos = y.sum()
    n_neg = len(y) - n_pos

    if n_pos < 10:
        print(f"  [SKIP] Tidak cukup data positif ({n_pos} baris) untuk {disease}")
        return None

    # Train / test split — input user = data baru di luar split ini
    X_train, X_test, y_train, y_test = train_test_split(
        X, y,
        test_size    = 0.2,
        random_state = 42,
        stratify     = y if n_pos > 1 else None,
    )

    print(f"\n  Train : {len(X_train)} baris | Test : {len(X_test)} baris")
    print(f"  Label : {int(n_pos)} positif / {int(n_neg)} negatif")

    # Scale pos_weight untuk class imbalance
    scale_pos_weight = n_neg / max(n_pos, 1)

    model = XGBClassifier(**XGB_PARAMS, scale_pos_weight=scale_pos_weight)
    model.fit(X_train, y_train)

    # Evaluasi pada test set (data historis yang belum dilihat saat training)
    y_pred = model.predict(X_test)
    y_prob = model.predict_proba(X_test)[:, 1]

    acc = (y_pred == y_test).mean()
    try:
        auc = roc_auc_score(y_test, y_prob)
        print(f"  Accuracy: {acc:.4f} | AUC-ROC: {auc:.4f}")
    except Exception:
        print(f"  Accuracy: {acc:.4f} | AUC-ROC: N/A (kelas tunggal di test set)")

    return model


def train_all(df: pd.DataFrame) -> dict:
    """Latih semua model dan kembalikan dict {disease: model}"""
    models = {}
    for disease in DISEASE_TARGETS:
        print(f"\n" + "-" * 60)
        print(f"  [TRAIN] {disease.upper()} MODEL")
        print("-" * 60)
        model = train_disease_model(df, disease)
        if model is not None:
            models[disease] = model
    return models


# -------------------------------------------------------------------------------
# 4.  SIMPAN MODEL
# -------------------------------------------------------------------------------

MODEL_FILENAMES = {
    'heart_disease' : 'heart_disease_model.pkl',
    'diabetes'      : 'diabetes_model.pkl',
    'hypertension'  : 'hypertension_model.pkl',
    'stroke'        : 'stroke_model.pkl',
    'ckd'           : 'ckd_model.pkl',
}

def save_models(models: dict) -> None:
    print(f"\n" + "=" * 60)
    print("  MENYIMPAN MODEL")
    print("=" * 60)

    for disease, model in models.items():
        filename = MODEL_FILENAMES.get(disease)
        if not filename:
            print(f"  [WARN] Tidak ada filename untuk {disease}")
            continue
        out_path = MODELS_DIR / filename
        with open(out_path, 'wb') as f:
            pickle.dump(model, f)
        size_kb = out_path.stat().st_size / 1024
        print(f"  [OK]  {filename} ({size_kb:.1f} KB) -> {out_path}")


# -------------------------------------------------------------------------------
# 5.  MAIN
# -------------------------------------------------------------------------------

def main() -> None:
    print("==========================================================")
    print("  DHC -- Train XGBoost dari MySQL health_records          ")
    print("  Dataset = health_records | Input user = inference data  ")
    print("==========================================================")

    # 1. Ambil data dari DB
    df_raw = load_from_db()

    if len(df_raw) < 50:
        print("\n[ERROR] Terlalu sedikit data untuk training (min 50 baris).")
        print("  Jalankan dulu: php artisan db:seed --class=HealthRecordDatasetSeeder")
        sys.exit(1)

    # 2. Preprocessing
    df = preprocess(df_raw)

    # 3. Training semua model
    models = train_all(df)

    if not models:
        print("\n[ERROR] Tidak ada model yang berhasil dilatih.")
        sys.exit(1)

    # 4. Simpan .pkl
    save_models(models)

    print("\n" + "=" * 60)
    print(f"  SELESAI -- {len(models)} model berhasil dilatih dan disimpan")
    print(f"  Direktori: {MODELS_DIR}")
    print("=" * 60)
    print("\nLangkah berikutnya:")
    print("  1. Restart FastAPI: uvicorn main:app --reload --port 8001")
    print("     (dari direktori python-ml-service/)")
    print("  2. Coba prediksi dari frontend - model kini bisa memprediksi")
    print("     data APAPUN, tidak hanya yang ada di dataset.\n")


if __name__ == '__main__':
    main()
