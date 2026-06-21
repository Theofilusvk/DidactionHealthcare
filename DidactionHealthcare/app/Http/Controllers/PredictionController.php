<?php

namespace App\Http\Controllers;

use App\Services\MlPredictionService;
use App\Services\AgenticAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PredictionController
 *
 * Menerima input kesehatan dari pengguna, lalu:
 *   1. Mengirim ke MlPredictionService → FastAPI (XGBoost trained on CSV datasets)
 *   2. Mengirim hasil prediksi ke AgenticAiService → Gemini API untuk saran
 *
 * Arsitektur:
 *   Input User → [PredictionController]
 *                    ↓
 *              MlPredictionService
 *                    ↓ POST /predict
 *              FastAPI Python (XGBoost)   ← trained on: heart.csv, diabetes.csv,
 *                    ↓                       stroke.csv, hypertension_dataset.csv
 *              Probabilitas 5 Penyakit
 *                    ↓
 *              AgenticAiService
 *                    ↓ POST Gemini API
 *              3 Rekomendasi Kesehatan (JSON)
 *                    ↓
 *              Response ke Frontend
 */
class PredictionController extends Controller
{
    public function __construct(
        protected MlPredictionService $mlService,
        protected AgenticAiService    $aiService,
    ) {}

    // ─── POST /predict ─────────────────────────────────────────────────────────

    /**
     * Prediksi risiko penyakit + saran kesehatan dari Gemini.
     *
     * Request JSON:
     * {
     *     "age":            45,
     *     "gender":         0,      // 0 = Perempuan, 1 = Laki-laki
     *     "glucose":        145.0,  // mg/dL
     *     "blood_pressure": 145,    // mmHg sistolik
     *     "bmi":            26.6    // kg/m²
     * }
     *
     * Opsional (default otomatis jika tidak dikirim):
     *     "cholesterol":  200.0    // mg/dL
     *     "heart_rate":   75       // bpm
     */
    public function predict(Request $request): JsonResponse
    {
        // ── Validasi input ──────────────────────────────────────────────────────
        $validated = $request->validate([
            'age'             => 'required|numeric|min:0|max:120',
            'gender'          => 'required|integer|in:0,1',
            'glucose'         => 'required|numeric|min:30|max:600',
            'blood_pressure'  => 'required|numeric|min:40|max:250',
            'bmi'             => 'required|numeric|min:5|max:80',
            'cholesterol'     => 'nullable|numeric|min:50|max:600',
            'heart_rate'      => 'nullable|numeric|min:20|max:250',
            'aktivitas_fisik' => 'required|string|in:sedentary,light,moderate,high',
            'status_merokok'  => 'required|string|in:never,former,active',
            'protein_urine'   => 'nullable|numeric|min:0',
            'hba1c'           => 'nullable|numeric|min:0',
        ], [
            'age.required'            => 'Usia harus diisi',
            'age.numeric'             => 'Usia harus berupa angka',
            'gender.required'         => 'Jenis kelamin harus diisi',
            'gender.in'               => 'Jenis kelamin harus 0 (Perempuan) atau 1 (Laki-laki)',
            'glucose.required'        => 'Kadar glukosa darah harus diisi',
            'glucose.numeric'         => 'Kadar glukosa harus berupa angka',
            'blood_pressure.required' => 'Tekanan darah harus diisi',
            'bmi.required'            => 'BMI harus diisi',
            'aktivitas_fisik.required'=> 'Tingkat aktivitas fisik harus dipilih',
            'status_merokok.required' => 'Status merokok harus dipilih',
        ]);

        Log::info('[PredictionController] Permintaan prediksi baru', [
            'age'    => $validated['age'],
            'gender' => $validated['gender'],
            'bmi'    => $validated['bmi'],
        ]);

        // ── Step 1: ML Prediction (XGBoost via FastAPI) ─────────────────────────
        //
        // MlPredictionService akan:
        //   • POST data ke http://127.0.0.1:8001/predict
        //   • FastAPI memuat model .pkl yang sudah dilatih dari CSV dataset
        //   • Model memprediksi SEMUA input, bukan hanya yang ada di dataset
        //   • Jika FastAPI tidak jalan → localFallback() digunakan otomatis
        //
        $mlResult = $this->mlService->predict($validated);

        if (! $mlResult['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mendapatkan prediksi ML. Pastikan Python ML service berjalan.',
                'data'    => null,
            ], 500);
        }

        // ── Step 2: AI Recommendations (Gemini) ────────────────────────────────
        //
        // AgenticAiService akan:
        //   • Membangun prompt terstruktur dari $validated + $mlResult
        //   • POST ke Gemini 1.5 Flash API
        //   • Mengembalikan 3 rekomendasi tindakan perbaikan dalam JSON
        //
        $aiResult = $this->aiService->getHealthRecommendations($validated, $mlResult);

        // ── Step 3: Health Score & Risk Status & Health Plan ─────────────────
        $highestProb = 0;
        $riskStatus = 'Aman';
        $predictions = $mlResult['predictions'] ?? [];
        foreach ($predictions as $p) {
            if (($p['probability'] ?? 0.0) > $highestProb) {
                $highestProb = $p['probability'];
                if (($p['risk_level'] ?? '') === 'High') {
                    $riskStatus = 'Bahaya';
                } elseif (($p['risk_level'] ?? '') === 'Moderate' && $riskStatus !== 'Bahaya') {
                    $riskStatus = 'Peringatan';
                }
            }
        }
        $healthScore = max(0, round(100 - ($highestProb * 100)));

        // Call generate-health-plan via MlPredictionService
        $healthPlan = $this->mlService->generateHealthPlan($validated, $predictions);

        // Save analysis results to session
        $analysisResult = [
            'input_data'    => $validated,
            'health_score'  => $healthScore,
            'risk_status'   => $riskStatus,
            'predictions'   => $predictions,
            'highest_risk'  => $mlResult['highest_risk'] ?? '',
            'meal_plan'     => $healthPlan['meal_plan'] ?? [],
            'activity_plan' => $healthPlan['activity_plan'] ?? [],
            'disclaimer'    => $healthPlan['disclaimer'] ?? '',
            'summary'       => $aiResult['summary'] ?? '',
            'action_plans'  => $aiResult['action_plans'] ?? [],
        ];
        session(['analysis_result' => $analysisResult]);

        // ── Build response ──────────────────────────────────────────────────────
        return response()->json([
            'status'  => 'success',
            'message' => 'Prediksi berhasil',
            'data'    => [
                // Hasil prediksi ML
                'model_mode'   => $mlResult['model_mode'],
                'predictions'  => $mlResult['predictions'],
                'highest_risk' => $mlResult['highest_risk'],

                // Rekomendasi dari Gemini
                'summary'      => $aiResult['summary']      ?? '',
                'action_plans' => $aiResult['action_plans'] ?? [],
                'ai_success'   => $aiResult['success'],
            ],
            'metadata' => [
                'ml_mode'   => $mlResult['model_mode'],
                'ai_model'  => $aiResult['model']  ?? 'gemini-1.5-flash',
                'timestamp' => now()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Ekspor hasil analisis kesehatan ke PDF.
     * GET /export-pdf
     */
    public function exportPdf()
    {
        $analysisResult = session('analysis_result');

        if (empty($analysisResult)) {
            return redirect('/get-started')->with('error', 'Belum ada data analisis yang dapat diekspor. Silakan lakukan analisis terlebih dahulu.');
        }

        $dateStr = now()->format('Y-m-d');
        $fileName = "Laporan-Kesehatan-Didaction-{$dateStr}.pdf";

        $pdf = Pdf::loadView('pdf.health-report', [
            'data' => $analysisResult,
            'date' => now()->format('d F Y'),
        ]);

        return $pdf->download($fileName);
    }

    // ─── GET /predict/example ──────────────────────────────────────────────────

    /**
     * Contoh request untuk keperluan testing / dokumentasi.
     */
    public function example(): JsonResponse
    {
        return response()->json([
            'message'         => 'Contoh request untuk endpoint POST /predict',
            'example_request' => [
                'age'            => 58,
                'gender'         => 0,      // 0 = Perempuan
                'glucose'        => 145.0,
                'blood_pressure' => 145,
                'bmi'            => 26.6,
                'cholesterol'    => 210.0,  // opsional
                'heart_rate'     => 80,     // opsional
            ],
            'notes' => [
                'gender'      => '0 = Perempuan, 1 = Laki-laki',
                'glucose'     => 'mg/dL, rentang normal puasa: 70–100',
                'blood_press' => 'mmHg sistolik, normal: <120',
                'bmi'         => 'kg/m², normal: 18.5–24.9',
            ],
        ]);
    }

    // ─── GET /predict/status ───────────────────────────────────────────────────

    /**
     * Cek status ML service (FastAPI Python).
     */
    public function status(): JsonResponse
    {
        $isAvailable = $this->mlService->isServiceAvailable();

        return response()->json([
            'ml_service_available' => $isAvailable,
            'ml_service_url'       => config('services.ml.url', 'http://127.0.0.1:8001'),
            'status'               => $isAvailable ? 'online' : 'offline (menggunakan fallback lokal)',
        ]);
    }
}
