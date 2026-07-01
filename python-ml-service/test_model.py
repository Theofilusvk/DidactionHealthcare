"""
===========================================================
  DHC -- Test Skenario Prediksi XGBoost
===========================================================
Mengirim 6 skenario pasien ke FastAPI dan menampilkan hasil
evaluasi model secara terstruktur.

Jalankan:
  python test_model.py
===========================================================
"""

import json
import urllib.request

BASE_URL = "http://127.0.0.1:8001"

# ===========================================================
#  SKENARIO PASIEN
# ===========================================================
SCENARIOS = [
    {
        "nama"      : "Pasien A -- Remaja Sehat",
        "deskripsi" : "Laki-laki 22 tahun, aktif olahraga, BMI normal, semua normal",
        "payload"   : {
            "age": 22, "gender": 1, "bmi": 21.5,
            "glucose": 85.0, "blood_pressure": 110,
            "cholesterol": 170.0, "heart_rate": 68
        }
    },
    {
        "nama"      : "Pasien B -- Pradiabetes",
        "deskripsi" : "Perempuan 45 tahun, glukosa tinggi, BMI overweight",
        "payload"   : {
            "age": 45, "gender": 0, "bmi": 28.3,
            "glucose": 142.0, "blood_pressure": 125,
            "cholesterol": 215.0, "heart_rate": 80
        }
    },
    {
        "nama"      : "Pasien C -- Hipertensi Berat",
        "deskripsi" : "Laki-laki 58 tahun, tekanan darah sangat tinggi, obesitas",
        "payload"   : {
            "age": 58, "gender": 1, "bmi": 33.1,
            "glucose": 118.0, "blood_pressure": 178,
            "cholesterol": 240.0, "heart_rate": 92
        }
    },
    {
        "nama"      : "Pasien D -- Risiko Jantung & Stroke",
        "deskripsi" : "Laki-laki 65 tahun, usia lanjut, kolesterol tinggi, tekanan darah tinggi",
        "payload"   : {
            "age": 65, "gender": 1, "bmi": 30.5,
            "glucose": 130.0, "blood_pressure": 160,
            "cholesterol": 280.0, "heart_rate": 88
        }
    },
    {
        "nama"      : "Pasien E -- Risiko CKD Tinggi",
        "deskripsi" : "Perempuan 60 tahun, diabetes tidak terkontrol, tekanan darah tinggi",
        "payload"   : {
            "age": 60, "gender": 0, "bmi": 27.8,
            "glucose": 195.0, "blood_pressure": 155,
            "cholesterol": 225.0, "heart_rate": 85
        }
    },
    {
        "nama"      : "Pasien F -- Multi-Risiko Tinggi",
        "deskripsi" : "Laki-laki 70 tahun, semua parameter buruk (worst case)",
        "payload"   : {
            "age": 70, "gender": 1, "bmi": 36.0,
            "glucose": 230.0, "blood_pressure": 185,
            "cholesterol": 295.0, "heart_rate": 98
        }
    },
]


# ===========================================================
#  HELPERS
# ===========================================================

def post_json(url: str, data: dict) -> dict:
    body = json.dumps(data).encode("utf-8")
    req  = urllib.request.Request(
        url,
        data    = body,
        headers = {"Content-Type": "application/json"},
        method  = "POST",
    )
    with urllib.request.urlopen(req, timeout=10) as resp:
        return json.loads(resp.read())


def check_health() -> dict:
    with urllib.request.urlopen(f"{BASE_URL}/health", timeout=5) as resp:
        return json.loads(resp.read())


def print_separator(char="=", width=62):
    print(char * width)


# ===========================================================
#  MAIN
# ===========================================================

def main():
    print_separator()
    print("  DHC -- Evaluasi Model XGBoost | Testing Skenario Pasien")
    print_separator()

    # -- 1. Health check
    print("\n[STEP 1] Cek Status FastAPI...")
    try:
        health = check_health()
        mode   = health.get("model_mode", "unknown")
        loaded = health.get("model_loaded", False)

        print(f"  Status     : online")
        print(f"  Model Mode : {mode.upper()}")
        print(f"  Loaded     : {loaded}")
        print(f"  Diseases   : {', '.join(health.get('diseases', []))}")
        print(f"  Features   : {', '.join(health.get('features', []))}")

        if mode != "xgboost":
            print(f"\n  WARNING: Model aktif bukan XGBoost! Mode: {mode}")
    except Exception as e:
        print(f"  ERROR: FastAPI tidak dapat dihubungi: {e}")
        print(f"  Pastikan server berjalan: uvicorn main:app --reload --port 8001")
        return

    # -- 2. Jalankan semua skenario
    print(f"\n[STEP 2] Menjalankan {len(SCENARIOS)} Skenario Prediksi...")

    results_summary = []

    for i, scenario in enumerate(SCENARIOS, 1):
        print_separator("-")
        print(f"\n  Skenario {i}: {scenario['nama']}")
        print(f"  {scenario['deskripsi']}")
        print(f"\n  Input:")
        p = scenario["payload"]
        print(f"    Usia={p['age']} th  |  Gender={'L' if p['gender']==1 else 'P'}  |  BMI={p['bmi']}")
        print(f"    Glukosa={p['glucose']} mg/dL  |  TD={p['blood_pressure']} mmHg")
        print(f"    Kolesterol={p['cholesterol']} mg/dL  |  Nadi={p['heart_rate']} bpm")

        try:
            result     = post_json(f"{BASE_URL}/predict", scenario["payload"])
            model_mode = result.get("model_mode", "unknown")

            print(f"\n  Hasil Prediksi [{model_mode.upper()}]:")
            print(f"  {'Penyakit':<30} {'Prob':>8}  {'Bar':^22}  Risk")
            print(f"  {'-'*30} {'-'*8}  {'-'*22}  {'-'*10}")

            for pred in result.get("predictions", []):
                bar_len = int(pred["probability"] * 20)
                bar     = "#" * bar_len + "." * (20 - bar_len)
                print(f"  {pred['label']:<30} {pred['percentage']:>8}  [{bar}]  {pred['risk_level']}")

            print(f"\n  >> Risiko Tertinggi: {result.get('highest_risk', '-')}")

            results_summary.append({
                "skenario": scenario["nama"],
                "mode"    : model_mode,
                "highest" : result.get("highest_risk", "-"),
                "ok"      : True
            })

        except Exception as e:
            print(f"\n  ERROR: {e}")
            results_summary.append({
                "skenario": scenario["nama"],
                "mode"    : "error",
                "highest" : str(e),
                "ok"      : False
            })

    # -- 3. Ringkasan
    print_separator()
    print("\n[STEP 3] RINGKASAN HASIL TESTING\n")
    print(f"  {'No':<4} {'Skenario':<35} {'Mode':<10} {'Risiko Tertinggi'}")
    print(f"  {'-'*4} {'-'*35} {'-'*10} {'-'*25}")

    all_xgboost = all(r["mode"] == "xgboost" for r in results_summary if r["ok"])
    for i, r in enumerate(results_summary, 1):
        status = "OK " if r["ok"] else "ERR"
        print(f"  [{status}] {i}. {r['skenario']:<35} {r['mode']:<10} {r['highest']}")

    print()
    if all_xgboost:
        print("  SEMUA PREDIKSI MENGGUNAKAN MODEL XGBOOST.")
    else:
        print("  PERINGATAN: Ada skenario yang tidak menggunakan XGBoost.")

    print_separator()


if __name__ == "__main__":
    main()
