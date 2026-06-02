#!/usr/bin/env python3
"""
===========================================================
  DHC — Train PyTorch Neural Network (Multi-Label)
===========================================================

Arsitektur:
1. Membaca tabel health_records dari MySQL.
2. Memproses 7 fitur (age, gender, bmi, glucose, bp, chol, hr).
3. Mengekstrak 5 label penyakit sekaligus (Multi-Label).
4. Normalisasi data dengan StandardScaler.
5. Melatih MultiDiseaseNN menggunakan BCEWithLogitsLoss.
6. Menyimpan full-bundle (state_dict + scalers) ke models/multidisease_model.pth
"""

import os
import sys
import numpy as np
import pandas as pd
import pymysql
import torch
import torch.nn as nn
import torch.optim as optim
from torch.utils.data import DataLoader, TensorDataset
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import roc_auc_score, accuracy_score
from pathlib import Path
from dotenv import load_dotenv

# Import arsitektur NN dari main.py
try:
    from main import MultiDiseaseNN, NUM_FEATURES, NUM_DISEASES, INPUT_FEATURES, DISEASE_LABELS
except ImportError as e:
    print(f"[ERROR] Gagal import dari main.py: {e}")
    sys.exit(1)

# ── Paths ─────────────────────────────────────────────────────────────────────
SCRIPT_DIR  = Path(__file__).resolve().parent
MODELS_DIR  = SCRIPT_DIR / 'models'
LARAVEL_DIR = SCRIPT_DIR.parent / 'DidactionHealthcare'
ENV_FILE    = LARAVEL_DIR / '.env'

MODELS_DIR.mkdir(parents=True, exist_ok=True)
MODEL_OUT   = MODELS_DIR / 'multidisease_model.pth'

# ── Load .env ─────────────────────────────────────────────────────────────────
if ENV_FILE.exists():
    load_dotenv(ENV_FILE)
    print(f"[INFO] .env dimuat dari: {ENV_FILE}")

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_NAME = os.getenv('DB_DATABASE', 'didaction_healthcare')
DB_USER = os.getenv('DB_USERNAME', 'root')
DB_PASS = os.getenv('DB_PASSWORD', '')

def load_data():
    print(f"\n[DB] Koneksi ke MySQL {DB_USER}@{DB_HOST}:{DB_PORT}/{DB_NAME} ...")
    try:
        conn = pymysql.connect(
            host=DB_HOST, port=DB_PORT, db=DB_NAME,
            user=DB_USER, password=DB_PASS, charset='utf8mb4'
        )
    except Exception as e:
        print(f"[ERROR] Gagal koneksi ke database: {e}")
        sys.exit(1)

    query = """
        SELECT
            age, gender, bmi, glucose, blood_pressure, cholesterol, heart_rate,
            diabetes_risk, hypertension_risk, heart_disease_risk, stroke_risk, ckd_risk
        FROM health_records
        WHERE deleted_at IS NULL
    """
    df = pd.read_sql(query, conn)
    conn.close()
    
    # Impute missing values with median for features
    for col in INPUT_FEATURES:
        if col in df.columns:
            df[col] = df[col].fillna(df[col].median())
            
    # Dummy impute if column completely missing
    if 'cholesterol' not in df.columns or df['cholesterol'].isnull().all():
        df['cholesterol'] = 200.0
    if 'heart_rate' not in df.columns or df['heart_rate'].isnull().all():
        df['heart_rate'] = 75.0

    # Binarize labels (>= 0.5 is 1, else 0)
    db_disease_cols = {
        'heart_disease': 'heart_disease_risk',
        'stroke': 'stroke_risk',
        'diabetes': 'diabetes_risk',
        'hypertension': 'hypertension_risk',
        'ckd': 'ckd_risk'
    }
    
    for disease, col in db_disease_cols.items():
        if col in df.columns:
            df[disease] = (df[col] >= 0.5).astype(float)
        else:
            df[disease] = 0.0

    # Drop rows with NaN targets just in case
    df = df.dropna(subset=DISEASE_LABELS)
    return df

def train_model():
    print("==========================================================")
    print("  DHC -- Train PyTorch Multi-Label Neural Network         ")
    print("==========================================================")
    
    df = load_data()
    print(f"[PRE] Data berhasil di-load. Shape: {df.shape}")
    
    X = df[INPUT_FEATURES].values
    y = df[DISEASE_LABELS].values
    
    if len(X) < 50:
        print("[ERROR] Data terlalu sedikit (<50 baris).")
        sys.exit(1)
        
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    # ── StandardScaler ────────────────────────────────────────────────────────
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)
    
    # Convert to PyTorch tensors
    X_train_t = torch.tensor(X_train_scaled, dtype=torch.float32)
    y_train_t = torch.tensor(y_train, dtype=torch.float32)
    X_test_t = torch.tensor(X_test_scaled, dtype=torch.float32)
    y_test_t = torch.tensor(y_test, dtype=torch.float32)
    
    train_dataset = TensorDataset(X_train_t, y_train_t)
    train_loader = DataLoader(train_dataset, batch_size=32, shuffle=True)
    
    # ── Initialize Model ──────────────────────────────────────────────────────
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    print(f"[INFO] Training menggunakan device: {device}")
    
    model = MultiDiseaseNN(num_features=NUM_FEATURES, num_diseases=NUM_DISEASES).to(device)
    
    # Multi-label loss (Binary Cross Entropy with Logits)
    # Kami juga bisa menggunakan pos_weight untuk imbalance jika diperlukan
    criterion = nn.BCEWithLogitsLoss()
    optimizer = optim.AdamW(model.parameters(), lr=0.001, weight_decay=1e-4)
    
    epochs = 150
    best_loss = float('inf')
    best_state = None
    
    print("\n[TRAIN] Memulai proses training Neural Network...")
    for epoch in range(epochs):
        model.train()
        train_loss = 0.0
        for batch_X, batch_y in train_loader:
            batch_X, batch_y = batch_X.to(device), batch_y.to(device)
            
            optimizer.zero_grad()
            logits = model(batch_X)
            loss = criterion(logits, batch_y)
            loss.backward()
            optimizer.step()
            
            train_loss += loss.item() * batch_X.size(0)
            
        train_loss /= len(train_loader.dataset)
        
        # Validation
        model.eval()
        with torch.no_grad():
            X_test_dev, y_test_dev = X_test_t.to(device), y_test_t.to(device)
            val_logits = model(X_test_dev)
            val_loss = criterion(val_logits, y_test_dev).item()
            
        if val_loss < best_loss:
            best_loss = val_loss
            best_state = model.state_dict()
            
        if (epoch + 1) % 30 == 0 or epoch == 0:
            print(f"  Epoch {epoch+1:3d}/{epochs} | Train Loss: {train_loss:.4f} | Val Loss: {val_loss:.4f}")

    print("\n[INFO] Training Selesai! Memulihkan best model weights...")
    model.load_state_dict(best_state)
    
    # ── Evaluasi ─────────────────────────────────────────────────────────────
    model.eval()
    with torch.no_grad():
        X_test_dev = X_test_t.to(device)
        test_probs = torch.sigmoid(model(X_test_dev)).cpu().numpy()
        
    print("\n=== EVALUASI PER PENYAKIT ===")
    for i, disease in enumerate(DISEASE_LABELS):
        y_true_col = y_test[:, i]
        y_prob_col = test_probs[:, i]
        y_pred_col = (y_prob_col >= 0.5).astype(int)
        
        try:
            auc = roc_auc_score(y_true_col, y_prob_col)
            acc = accuracy_score(y_true_col, y_pred_col)
            print(f"  {disease:15s} | Accuracy: {acc:.4f} | AUC-ROC: {auc:.4f}")
        except Exception:
            # Terjadi jika semua true labels adalah 0 atau 1 (imbalance ekstrem di test set)
            print(f"  {disease:15s} | Accuracy: {accuracy_score(y_true_col, y_pred_col):.4f} | AUC-ROC: N/A")
            
    # ── Save Model Bundle ────────────────────────────────────────────────────
    print(f"\n[SAVE] Menyimpan model dan scalers ke {MODEL_OUT}")
    bundle = {
        "model_state": model.state_dict(),
        "scaler_mean": scaler.mean_,
        "scaler_std": scaler.scale_
    }
    torch.save(bundle, MODEL_OUT)
    
    size_kb = MODEL_OUT.stat().st_size / 1024
    print(f"  [OK] Tersimpan! ({size_kb:.1f} KB)")
    print("\n[NEXT STEPS]")
    print("  Model PyTorch telah siap. FastAPI akan otomatis memprioritaskan")
    print("  'multidisease_model.pth' ketika di-restart.")

if __name__ == "__main__":
    train_model()
