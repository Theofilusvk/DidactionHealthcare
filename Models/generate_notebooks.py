import json
import os

def create_notebook(filename, title, disease_name, dataset_path, features, prep_code, label_col):
    cells = []
    
    def add_markdown(text):
        cells.append({
            "cell_type": "markdown",
            "metadata": {},
            "source": [line + "\n" for line in text.strip().split("\n")]
        })
        
    def add_code(text):
        cells.append({
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [line + "\n" for line in text.strip().split("\n")]
        })

    # 1. Title and Explanation
    add_markdown(f"""
# {title}

Notebook ini dikhususkan untuk memprediksi **{disease_name}** menggunakan model Machine Learning (XGBoost).
Penjelasan tahapan:
1. **Load Data**: Membaca dataset `{dataset_path}`.
2. **Preprocessing**: Memilih fitur (gejala/metrik) yang relevan dan membersihkan data kosong.
3. **Training**: Menerapkan SMOTE jika data tidak seimbang, lalu melatih XGBoost.
4. **Evaluasi**: Mengukur akurasi, sensitivitas, dan melihat Confusion Matrix.
5. **Inference**: Cara memprediksi data pasien baru.
    """)

    # 2. Imports
    add_markdown("## 1. Imports & Setup\nMengimpor library utama yang dibutuhkan untuk manipulasi data dan pembuatan model.")
    add_code("""
import pandas as pd
import numpy as np
import warnings
warnings.filterwarnings('ignore')

from scipy.io import arff
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, roc_auc_score, confusion_matrix, ConfusionMatrixDisplay
from imblearn.over_sampling import SMOTE
import xgboost as xgb
import matplotlib.pyplot as plt
    """)

    # 3. Load Data
    add_markdown(f"## 2. Load Dataset\nMembaca dataset `{dataset_path}` ke dalam Pandas DataFrame.")
    add_code(f"""
dataset_path = '{dataset_path}'
if dataset_path.endswith('.arff'):
    data, meta = arff.loadarff(dataset_path)
    df = pd.DataFrame(data)
    # Decode byte strings to normal strings
    for col in df.select_dtypes([object]):
        df[col] = df[col].str.decode('utf-8')
else:
    df = pd.read_csv(dataset_path)

df.head()
    """)

    # 4. Preprocessing
    add_markdown("## 3. Preprocessing\nMenyesuaikan format kolom ke format standar (`core_*`) agar model dapat mengenali fitur dengan benar.")
    add_code(prep_code)

    # 5. Training
    add_markdown("## 4. Model Training\nMemilih fitur yang relevan, menangani missing values (median imputation), membagi data menjadi Train & Test, dan menyeimbangkan kelas dengan SMOTE sebelum melatih model XGBoost.")
    feats_str = ", ".join([f"'{f}'" for f in features])
    add_code(f"""
features = [{feats_str}]
target = '{label_col}'

# Ambil fitur yang tersedia di dataframe
available_features = [f for f in features if f in df.columns]

X = df[available_features].copy()
y = df[target].copy()

# Handle missing values (Isi dengan nilai median)
X = X.fillna(X.median())

# Train-test split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

# Cek rasio kelas, terapkan SMOTE jika minoritas terlalu kecil (< 40%)
minority_ratio = y_train.value_counts(normalize=True).min()
if minority_ratio < 0.4:
    print(f"Data tidak seimbang (minority: {{minority_ratio:.1%}}), mengaplikasikan SMOTE...")
    smote = SMOTE(random_state=42)
    X_train, y_train = smote.fit_resample(X_train, y_train)
else:
    print("Data cukup seimbang, tidak perlu SMOTE.")

# Latih XGBoost
model = xgb.XGBClassifier(
    n_estimators=100, 
    max_depth=5, 
    learning_rate=0.1, 
    random_state=42,
    eval_metric='logloss'
)
model.fit(X_train, y_train)
print("Model berhasil dilatih!")
    """)

    # 6. Evaluasi
    add_markdown("## 5. Evaluasi Performa\nMelihat seberapa baik model ini membedakan antara pasien sehat dan yang berisiko.")
    add_code("""
y_pred = model.predict(X_test)
y_proba = model.predict_proba(X_test)[:, 1]

print("=== CLASSIFICATION REPORT ===")
print(classification_report(y_test, y_pred))

auc = roc_auc_score(y_test, y_proba)
print(f"ROC-AUC Score: {auc:.4f}")

fig, ax = plt.subplots(figsize=(5,4))
cm = confusion_matrix(y_test, y_pred)
disp = ConfusionMatrixDisplay(confusion_matrix=cm, display_labels=["Sehat", "Risiko Tinggi"])
disp.plot(cmap='Blues', ax=ax)
plt.title('Confusion Matrix')
plt.show()
    """)

    # 7. Feature Importance
    add_markdown("## 6. Feature Importance\nFitur/metrik tubuh mana yang paling berperan besar dalam prediksi model?")
    add_code("""
fi = pd.Series(model.feature_importances_, index=available_features).sort_values(ascending=True)
plt.figure(figsize=(8, 5))
fi.plot(kind='barh', color='teal')
plt.title(f"Feature Importance - {disease_name}")
plt.xlabel("Importance Score")
plt.ylabel("Features")
plt.show()
    """)

    # 8. Inference
    add_markdown("## 7. Inference (Prediksi Data Baru)\nContoh penggunaan model untuk memprediksi probabilitas risiko pada pasien baru.")
    add_code("""
# Contoh input dari pasien
user_input = {
    'core_age': 55,
    'core_systolic_bp': 140,
    'core_cholesterol': 220,
    'core_heart_rate': 85,
    'core_bmi': 28.5,
    'core_glucose': 150
}

# Siapkan input DataFrame sesuai dengan urutan fitur model
input_df = pd.DataFrame([user_input])
for col in available_features:
    if col not in input_df.columns:
        input_df[col] = np.nan # Isi dengan NaN jika tidak ada
        
# Urutkan kolom sesuai training
input_df = input_df[available_features]
# Imputasi jika perlu (untuk demo ini kita asumsikan pakai training median, di prod pakai scaler/imputer dari train)
input_df = input_df.fillna(X.median())

# Prediksi Probabilitas
prob = model.predict_proba(input_df)[0][1]
print(f"Probabilitas Pasien Terkena {disease_name}: {prob:.1%}")
    """)

    notebook = {
        "cells": cells,
        "metadata": {
            "kernelspec": {
                "display_name": "Python 3",
                "language": "python",
                "name": "python3"
            },
            "language_info": {
                "name": "python",
                "version": "3.9.0"
            }
        },
        "nbformat": 4,
        "nbformat_minor": 4
    }

    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(notebook, f, indent=2)
    print(f"Generated {filename}")

if __name__ == "__main__":
    
    # Common features across all models
    common_features = ['core_age', 'core_systolic_bp', 'core_cholesterol', 'core_heart_rate', 'core_bmi', 'core_glucose']

    # 1. HEART DISEASE
    create_notebook(
        filename="Model_Heart_Disease.ipynb",
        title="Prediksi Penyakit Jantung (Heart Disease)",
        disease_name="Penyakit Jantung",
        dataset_path="heart.csv",
        features=common_features,
        label_col="label_heart",
        prep_code="""
# Mapping fitur heart.csv
df['core_age'] = df['Age']
df['core_systolic_bp'] = df['RestingBP']
df['core_cholesterol'] = df['Cholesterol']
df['core_heart_rate'] = df['MaxHR']

# Buat dummy fitur yang tidak ada di dataset agar model konsisten (diisi NaN lalu median imputation)
df['core_bmi'] = np.nan
df['core_glucose'] = np.nan

# Kolom target
df['label_heart'] = df['HeartDisease']
        """
    )

    # 2. STROKE
    create_notebook(
        filename="Model_Stroke.ipynb",
        title="Prediksi Stroke",
        disease_name="Stroke",
        dataset_path="healthcare-dataset-stroke-data.csv",
        features=common_features,
        label_col="label_stroke",
        prep_code="""
# Mapping fitur stroke.csv
df['core_age'] = df['age']
df['core_glucose'] = df['avg_glucose_level']

# Handling nilai 'N/A' pada bmi (string to numeric)
df['core_bmi'] = pd.to_numeric(df['bmi'], errors='coerce')

# Dummy fitur untuk fitur yang tidak tersedia
df['core_systolic_bp'] = np.nan
df['core_cholesterol'] = np.nan
df['core_heart_rate'] = np.nan

# Kolom target
df['label_stroke'] = df['stroke']
        """
    )

    # 3. DIABETES
    create_notebook(
        filename="Model_Diabetes.ipynb",
        title="Prediksi Diabetes",
        disease_name="Diabetes",
        dataset_path="diabetes.csv",
        features=common_features,
        label_col="label_diabetes",
        prep_code="""
# Mapping fitur diabetes.csv
df['core_age'] = df['Age']
df['core_glucose'] = df['Glucose']
df['core_systolic_bp'] = df['BloodPressure']
df['core_bmi'] = df['BMI']

# Dummy fitur
df['core_cholesterol'] = np.nan
df['core_heart_rate'] = np.nan

# Kolom target
df['label_diabetes'] = df['Outcome']
        """
    )

    # 4. HYPERTENSION
    create_notebook(
        filename="Model_Hypertension.ipynb",
        title="Prediksi Hipertensi",
        disease_name="Hipertensi",
        dataset_path="hypertension_data.csv",
        features=common_features,
        label_col="label_hypertension",
        prep_code="""
# Mapping fitur hypertension_data.csv
df['core_age'] = df['age']
df['core_systolic_bp'] = df['trestbps']
df['core_cholesterol'] = df['chol']
df['core_heart_rate'] = df['thalach']

df['core_bmi'] = np.nan
df['core_glucose'] = np.nan

# Target
df['label_hypertension'] = df['target']
        """
    )

    # 5. CHRONIC KIDNEY DISEASE (CKD)
    create_notebook(
        filename="Model_CKD.ipynb",
        title="Prediksi Chronic Kidney Disease (Gagal Ginjal)",
        disease_name="Gagal Ginjal Kronis (CKD)",
        dataset_path="chronic_kidney_disease.arff",
        features=common_features,
        label_col="label_ckd",
        prep_code="""
# Ganti karakter '?' menjadi NaN agar dikenali oleh Pandas
df.replace('?', np.nan, inplace=True)

# Mapping fitur
df['core_age'] = pd.to_numeric(df['age'], errors='coerce')
df['core_systolic_bp'] = pd.to_numeric(df['bp'], errors='coerce')
df['core_glucose'] = pd.to_numeric(df['bgr'], errors='coerce')  # blood glucose random

df['core_cholesterol'] = np.nan
df['core_heart_rate'] = np.nan
df['core_bmi'] = np.nan

# Target 'class' adalah 'ckd' atau 'notckd'
df['label_ckd'] = df['class'].apply(lambda x: 1 if str(x).strip().lower() == 'ckd' else 0)
        """
    )
