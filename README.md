# Didaction Healthcare (DHC)

Sistem prediksi risiko penyakit berbasis machine learning yang menganalisis data kesehatan pengguna dan menghasilkan rekomendasi kesehatan personal.

## Deskripsi Program

Didaction Healthcare adalah aplikasi web kesehatan yang memungkinkan pengguna memasukkan data kesehatan untuk mendapatkan prediksi risiko lima penyakit kronis sekaligus, yaitu Heart Disease, Stroke, Diabetes, Hypertension, dan Chronic Kidney Disease (CKD).

Sistem ini terdiri dari tiga komponen yang berjalan bersamaan:

1. **Frontend dan Backend Web (Laravel)** — Antarmuka pengguna dan logika aplikasi utama yang menerima input data kesehatan, meneruskannya ke layanan ML, dan menampilkan hasil prediksi beserta rekomendasi kesehatan.

2. **Machine Learning Service (FastAPI + XGBoost)** — Server Python yang memuat model XGBoost terlatih untuk masing-masing penyakit dan menjalankan inferensi prediksi. Setiap penyakit memiliki model XGBClassifier tersendiri yang dilatih dari dataset kesehatan.

3. **AI Health Advisor (Google Gemini)** — Layanan yang menghasilkan rekomendasi kesehatan personal berdasarkan hasil prediksi, mencakup ringkasan kondisi dan rencana tindakan prioritas.

### Fitur Utama

- Prediksi risiko 5 penyakit kronis sekaligus dengan probabilitas dan level risiko (Low / Moderate / High)
- Rekomendasi kesehatan personal dari AI (meal plan, activity plan)
- Health score dan status risiko keseluruhan
- Export hasil analisis ke PDF
- Fallback otomatis jika layanan ML atau AI tidak tersedia

### Algoritma Machine Learning

Model utama menggunakan **XGBoost (XGBClassifier)** dengan konfigurasi:
- 200 pohon keputusan (n_estimators)
- Kedalaman maksimum 5 (max_depth)
- Learning rate 0.1
- Penanganan class imbalance dengan scale_pos_weight
- Split data training/testing: 80% / 20%

Input model: 7 fitur kesehatan (usia, jenis kelamin, BMI, glukosa, tekanan darah, kolesterol, detak jantung)

---

## Prasyarat

Pastikan software berikut sudah terinstal di sistem:

| Software | Versi Minimum | Keterangan |
|----------|--------------|-----------|
| PHP | 8.2 | Dengan ekstensi: pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath |
| Composer | 2.x | Package manager PHP |
| Node.js | 18.x | Dengan npm |
| Python | 3.10 | Dengan pip |
| MySQL | 8.0 | Database utama |

---

## Instalasi

### 1. Clone Repository

```
git clone <url-repository>
cd DHC
```

### 2. Install Dependencies PHP (Laravel)

```
cd DidactionHealthcare
composer install
```

### 3. Install Dependencies Frontend (Node.js)

```
npm install
```

### 4. Konfigurasi Environment Laravel

```
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi berikut:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=didaction_healthcare
DB_USERNAME=root
DB_PASSWORD=

ML_SERVICE_URL=http://127.0.0.1:8001
ML_SERVICE_TIMEOUT=30

GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash
```

### 5. Migrasi Database dan Isi Dataset

```
php artisan migrate
php artisan db:seed --class=HealthRecordDatasetSeeder
```

### 6. Install Dependencies Python

```
cd ../python-ml-service
pip install -r requirements.txt
```

Daftar package yang akan diinstall:

- `fastapi`, `uvicorn` — web framework untuk ML service
- `httpx` — HTTP client untuk memanggil Gemini/OpenAI API
- `numpy`, `scikit-learn`, `xgboost`, `pydantic` — library ML runtime
- `pandas`, `pymysql`, `imbalanced-learn`, `python-dotenv` — library training model
- `torch` — PyTorch (digunakan sebagai model fallback)

### 7. Latih Model XGBoost

Pastikan database sudah terisi data dari seeder, kemudian jalankan:

```
python train_from_db.py
```

Perintah ini akan:
1. Mengambil data dari tabel `health_records` di MySQL
2. Melakukan preprocessing dan binarisasi label
3. Melatih 5 model XGBClassifier (satu per penyakit)
4. Menyimpan model sebagai file `.pkl` di folder `python-ml-service/models/`

Tanda berhasil:
```
SELESAI -- 5 model berhasil dilatih dan disimpan
```

---

## Menjalankan Program

Buka tiga terminal secara bersamaan dan jalankan masing-masing perintah berikut.

### Terminal 1 — Python ML Service (FastAPI)

```bash
cd "path/ke/DHC/python-ml-service"
uvicorn main:app --reload --port 8001
```

Tanda berhasil:
```
Semua model XGBoost berhasil dimuat. Mode: xgboost
Application startup complete.
```

### Terminal 2 — Laravel Backend

```bash
cd "path/ke/DHC/DidactionHealthcare"
php artisan serve --port=8000
```

Tanda berhasil:
```
Server running on http://127.0.0.1:8000
```

### Terminal 3 — Vite (Frontend Assets)

```bash
cd "path/ke/DHC/DidactionHealthcare"
npm run dev
```

Tanda berhasil:
```
VITE ready in ...ms
```

---

## Akses Aplikasi

| URL | Keterangan |
|-----|-----------|
| http://127.0.0.1:8000 | Halaman utama aplikasi |
| http://127.0.0.1:8000/get-started | Form prediksi kesehatan |
| http://127.0.0.1:8001/health | Status ML service |
| http://127.0.0.1:8001/docs | Dokumentasi API FastAPI |

---

## Struktur Direktori

```
DHC/
├── DidactionHealthcare/          # Laravel (web app)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── PredictionController.php
│   │   └── Services/
│   │       ├── MlPredictionService.php   # Koneksi ke FastAPI
│   │       └── AgenticAiService.php      # Koneksi ke Gemini
│   ├── resources/views/
│   │   └── prediction-form.blade.php     # Halaman utama
│   ├── routes/
│   │   └── api.php                       # Route API
│   └── .env                              # Konfigurasi
│
└── python-ml-service/            # Python FastAPI (ML)
    ├── main.py                   # Server FastAPI + inferensi
    ├── train_from_db.py          # Script training XGBoost
    ├── test_model.py             # Script evaluasi model
    ├── requirements.txt
    └── models/
        ├── heart_disease_model.pkl
        ├── stroke_model.pkl
        ├── diabetes_model.pkl
        ├── hypertension_model.pkl
        └── ckd_model.pkl
```

---

## Pengujian Model

Untuk menguji apakah model XGBoost berjalan dengan benar, pastikan FastAPI sudah aktif lalu jalankan:

```bash
cd python-ml-service
python test_model.py
```

Script ini akan mengirim 6 skenario pasien (dari pasien sehat hingga multi-risiko tinggi) ke endpoint prediksi dan menampilkan hasil beserta konfirmasi bahwa semua prediksi menggunakan model XGBoost.

---

## Catatan Penting

- FastAPI **harus** berjalan di port `8001` karena sudah dikonfigurasi di `.env` Laravel.
- Jika FastAPI tidak aktif, sistem akan otomatis menggunakan fallback heuristik sederhana (ditandai dengan `model_mode: local_fallback` pada response).
- Jika Gemini API tidak dikonfigurasi, rekomendasi kesehatan akan menggunakan fallback berbasis aturan lokal.
- Model `.pkl` harus sudah ada di folder `python-ml-service/models/` sebelum FastAPI dijalankan. Jika belum, jalankan `train_from_db.py` terlebih dahulu.

---

## Lisensi

Proyek ini dibuat untuk keperluan akademik — Mata Kuliah Kecerdasan Mesin, Semester 4.
