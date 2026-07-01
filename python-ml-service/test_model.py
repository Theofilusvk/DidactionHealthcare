"""
===========================================================
  DHC — Test Skenario Prediksi XGBoost
===========================================================
Mengirim 6 skenario pasien ke FastAPI dan menampilkan hasil
evaluasi model secara terstruktur.

Jalankan:
  python test_model.py
===========================================================
"""

import json
import urllib.request
import urllib.error

BASE_URL = "http://127.0.0.1:8001"

# ── Warna terminal ─────────────────────────────────────────
GREEN  = "\033[92m"
RED    = "\033[91m"
YELLOW = "\033[93m"
CYAN   = "\033[96m"
BOLD   = "\033[1m"
RESET  = "\033[0m"

def color_risk(level: str) -> str:
    if level == "High":
        return f"{RED}{BOLD}{level}{RESET}"
    elif level == "Moderate":
        return f"{YELLOW}{level}{RESET}"
    return f"{GREEN}{level}{RESET}"

# ══════════════════════════════════════════════════════════
#  SKENARIO PASIEN
# ══════════════════════════════════════════════════════════
SCENARIOS = [
    {
        "nama"       : "Pasien A — Remaja Sehat",
        "deskripsi"  : "Laki-laki 22 tahun, aktif olahraga, BMI normal, semua normal",
        "payload"    : {
            "age": 22, "gender": 1, "bmi": 21.5,
            "glucose": 85.0, "blood_pressure": 110,
            "cholesterol": 170.0, "heart_rate": 68
        }
    },
    {
        "nama"       : "Pasien B — Pradiabetes",
        "deskripsi"  : "Perempuan 45 tahun, glukosa tinggi, BMI overweight",
        "payload"    : {
            "age": 45, "gender": 0, "bmi": 28.3,
            "glucose": 142.0, "blood_pressure": 125,
            "cholesterol": 215.0, "heart_rate": 80
        }
    },
    {
        "nama"       : "Pasien C — Hipertensi Berat",
        "deskripsi"  : "Laki-laki 58 tahun, tekanan darah sangat tinggi, obesitas",
        "payload"    : {
            "age": 58, "gender": 1, "bmi": 33.1,
            "glucose": 118.0, "blood_pressure": 178,
            "cholesterol": 240.0, "heart_rate": 92
        }
    },
    {
        "nama"       : "Pasien D — Risiko Jantung & Stroke",
        "deskripsi"  : "Laki-laki 65 tahun, usia lanjut, kolesterol tinggi, tekanan darah tinggi",
        "payload"    : {
            "age": 65, "gender": 1, "bmi": 30.5,
            "glucose": 130.0, "blood_pressure": 160,
            "cholesterol": 280.0, "heart_rate": 88
        }
    },
    {
        "nama"       : "Pasien E — Risiko CKD Tinggi",
        "deskripsi"  : "Perempuan 60 tahun, diabetes tidak terkontrol, tekanan darah tinggi",
        "payload"    : {
            "age": 60, "gender": 0, "bmi": 27.8,
            "glucose": 195.0, "blood_pressure": 155,
            "cholesterol": 225.0, "heart_rate": 85
        }
    },
    {
        "nama"       : "Pasien F — Multi-Risiko Tinggi",
        "deskripsi"  : "Laki-laki 70 tahun, semua parameter buruk — worst case",
        "payload"    : {
            "age": 70, "gender": 1, "bmi": 36.0,
            "glucose": 230.0, "blood_pressure": 185,
            "cholesterol": 295.0, "heart_rate": 98
        }
    },
]

# ══════════════════════════════════════════════════════════
#  HELPERS
# ══════════════════════════════════════════════════════════

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
    print(f"{CYAN}{char * width}{RESET}")


# ══════════════════════════════════════════════════════════
#  MAIN
# ══════════════════════════════════════════════════════════

def main():
    print_separator()
    print(f"{BOLD}{CYAN}  DHC — Evaluasi Model XGBoost | Testing Skenario Pasien{RESET}")
    print_separator()

    # ── 1. Health check ───────────────────────────────────
    print(f"\n{BOLD}[STEP 1] Cek Status FastAPI...{RESET}")
    try:
        health = check_health()
        mode   = health.get("model_mode", "unknown")
        loaded = health.get("model_loaded", False)

        mode_color = GREEN if mode == "xgboost" else (YELLOW if mode == "pytorch" else RED)
        print(f"  Status    : {GREEN}online{RESET}")
        print(f"  Model Mode: {mode_color}{BOLD}{mode.upper()}{RESET}")
        print(f"  Loaded    : {GREEN if loaded else RED}{loaded}{RESET}")
        print(f"  Diseases  : {', '.join(health.get('diseases', []))}")
        print(f"  Features  : {', '.join(health.get('features', []))}")

        if mode != "xgboost":
            print(f"\n  {YELLOW}WARNING: Model aktif bukan XGBoost! Mode: {mode}{RESET}")
    except Exception as e:
        print(f"  {RED}FastAPI tidak dapat dihubungi: {e}{RESET}")
        print(f"  Pastikan: uvicorn main:app --reload --port 8001")
        return

    # ── 2. Jalankan semua skenario ────────────────────────
    print(f"\n{BOLD}[STEP 2] Menjalankan {len(SCENARIOS)} Skenario Prediksi...{RESET}")

    results_summary = []

    for i, scenario in enumerate(SCENARIOS, 1):
        print_separator("-")
        print(f"\n{BOLD}  Skenario {i}: {scenario['nama']}{RESET}")
        print(f"  {scenario['deskripsi']}")
        print(f"\n  Input:")
        p = scenario["payload"]
        print(f"    Usia={p['age']}th  |  Gender={'L' if p['gender']==1 else 'P'}  |  BMI={p['bmi']}")
        print(f"    Glukosa={p['glucose']} mg/dL  |  TD={p['blood_pressure']} mmHg")
        print(f"    Kolesterol={p['cholesterol']} mg/dL  |  Nadi={p['heart_rate']} bpm")

        try:
            result = post_json(f"{BASE_URL}/predict", scenario["payload"])
            model_mode = result.get("model_mode", "unknown")

            print(f"\n  {BOLD}Hasil Prediksi [{model_mode.upper()}]:{RESET}")
            print(f"  {'Penyakit':<30} {'Prob':>8}  {'Bar':^22}  Risk")
            print(f"  {'-'*30} {'-'*8}  {'-'*22}  {'-'*10}")

            highest_prob = 0
            for pred in result.get("predictions", []):
                bar_len = int(pred["probability"] * 20)
                bar = "#" * bar_len + "." * (20 - bar_len)
                risk_colored = color_risk(pred["risk_level"])
                print(f"  {pred['label']:<30} {pred['percentage']:>8}  [{bar}]  {risk_colored}")

                if pred["probability"] > highest_prob:
                    highest_prob = pred["probability"]

            print(f"\n  >> Risiko Tertinggi: {result.get('highest_risk', '-')}")

            results_summary.append({
                "skenario": scenario["nama"],
                "mode": model_mode,
                "highest": result.get("highest_risk", "-"),
                "ok": True
            })

        except Exception as e:
            print(f"\n  {RED}Error: {e}{RESET}")
            results_summary.append({
                "skenario": scenario["nama"],
                "mode": "error",
                "highest": str(e),
                "ok": False
            })

    # ── 3. Ringkasan ──────────────────────────────────────
    print_separator()
    print(f"\n{BOLD}[STEP 3] RINGKASAN HASIL TESTING{RESET}\n")
    print(f"  {'No':<4} {'Skenario':<35} {'Mode':<10} {'Risiko Tertinggi'}")
    print(f"  {'-'*4} {'-'*35} {'-'*10} {'-'*25}")

    all_xgboost = all(r["mode"] == "xgboost" for r in results_summary if r["ok"])
    for i, r in enumerate(results_summary, 1):
        mode_str = r["mode"]
        status   = "OK" if r["ok"] else "ERR"
        print(f"  [{status}] {i}. {r['skenario']:<35} {r['mode']:<10} {r['highest']}")

    print()
    if all_xgboost:
        print(f"  {GREEN}{BOLD}SEMUA PREDIKSI MENGGUNAKAN MODEL XGBOOST!{RESET}")
    else:
        print(f"  {RED}{BOLD}Ada skenario yang tidak menggunakan XGBoost.{RESET}")

    print_separator()


if __name__ == "__main__":
    main()
