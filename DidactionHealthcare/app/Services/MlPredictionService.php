<?php

namespace App\Services;

use App\Models\HealthRecord;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MlPredictionService — Bridge Between Web App and Machine Learning Model
 *
 * This service communicates with a Python FastAPI backend that runs XGBoost
 * or Neural Network models to predict disease probabilities.
 *
 * Takes patient health data and returns probability scores for 5 diseases:
 * Heart Disease, Stroke, Diabetes, Hypertension, and Chronic Kidney Disease.
 *
 * Complete flow:
 *   1. Web Controller collects patient health data
 *   2. MlPredictionService sends it to Python FastAPI server
 *   3. ML models return disease probabilities
 *   4. AgenticAiService uses these predictions to generate personalized advice
 */
class MlPredictionService
{
    /** URL address of the Python FastAPI server (configured via environment variables) */
    private string $baseUrl;

    /** Maximum time in seconds to wait for a response from the ML server before giving up */
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (int) config('services.ml.timeout', 30);
    }

    // =======================================================================================
    //  PUBLIC METHODS — Main ways to use this service
    // =======================================================================================

    /**
     * Main Entry Point: Send patient health data to ML model and get disease predictions
     *
     * This is the core method. Pass it patient health metrics, and it will call the
     * Python FastAPI server to get disease probabilities for 5 different diseases.
     * If the server is unreachable, it automatically falls back to simple calculations.
     *
     * @param  array $healthData Patient health metrics:
     *                          - age: Years old (int)
     *                          - gender: 0 = Female, 1 = Male (int)
     *                          - bmi: Body Mass Index kg/m² (float)
     *                          - glucose: Blood glucose mg/dL (float)
     *                          - blood_pressure: Systolic mmHg (int)
     *                          - cholesterol: Total cholesterol mg/dL (float, optional)
     *                          - heart_rate: Heartbeats per minute (int, optional)
     *
     * @return array Structure:
     *               {
     *                   'success': true/false,
     *                   'model_mode': 'xgboost' | 'neural_network' | 'local_fallback',
     *                   'predictions': {
     *                       'disease_key': {
     *                           'label': 'Display name',
     *                           'probability': 0.75 (0 to 1),
     *                           'percentage': '75.0%',
     *                           'risk_level': 'Low' | 'Moderate' | 'High'
     *                       },
     *                       ... (repeat for 5 diseases)
     *                   },
     *                   'highest_risk': 'Heart Disease (75%)',
     *                   'raw': {...} // debug info
     *               }
     */
    public function predict(array $healthData): array
    {
        $payload = $this->buildPayload($healthData);

        Log::info('[MLService] Mengirim prediksi', [
            'url'     => $this->baseUrl . '/predict',
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/predict", $payload);

            // ── Tangani HTTP error (4xx, 5xx) ─────────────────────────────────
            if ($response->failed()) {
                Log::error('[MLService] HTTP error dari ML service', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new \RuntimeException(
                    "ML service merespons dengan error {$response->status()}: {$response->body()}"
                );
            }

            $data = $response->json();

            Log::info('[MLService] Prediksi berhasil', [
                'model_mode'   => $data['model_mode'] ?? 'unknown',
                'highest_risk' => $data['highest_risk'] ?? '-',
            ]);

            return $this->formatResponse($data);

        } catch (ConnectionException $e) {
            Log::error('[MLService] Tidak dapat terhubung ke ML service', [
                'url'   => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);

            // Graceful fallback — kembalikan estimasi berbasis rules sederhana
            Log::warning('[MLService] Menggunakan fallback lokal karena service tidak tersedia');
            return $this->localFallback($healthData);

        } catch (RequestException $e) {
            Log::error('[MLService] Request exception', ['error' => $e->getMessage()]);
            return $this->localFallback($healthData);
        }
    }

    /**
     * Prediksi dari HealthRecord yang sudah tersimpan di database.
     * Shortcut untuk controller yang sudah punya model instance.
     *
     * @param  HealthRecord $record
     * @return array Hasil prediksi (format sama dengan predict())
     */
    public function predictFromRecord(HealthRecord $record): array
    {
        return $this->predict($record->toMlInput());
    }

    /**
     * Cek apakah ML service sedang berjalan (health check).
     *
     * @return bool
     */
    public function isServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    // ─── Private Helpers ───────────────────────────────────────────────────────

    /**
     * Bangun payload sesuai schema PredictRequest FastAPI.
     * Kolom opsional diisi nilai default jika tidak ada.
     */
    private function buildPayload(array $data): array
    {
        return [
            'age'            => (int)   ($data['age']            ?? 0),
            'gender'         => (int)   ($data['gender']         ?? 1),
            'bmi'            => (float) ($data['bmi']            ?? 22.0),
            'glucose'        => (float) ($data['glucose']        ?? 100.0),
            'blood_pressure' => (int)   ($data['blood_pressure'] ?? 120),
            'cholesterol'    => (float) ($data['cholesterol']    ?? 200.0),
            'heart_rate'     => (int)   ($data['heart_rate']     ?? 75),
        ];
    }

    /**
     * Format response dari FastAPI menjadi array yang lebih mudah dipakai
     * oleh controller dan AgenticAiService.
     */
    private function formatResponse(array $data): array
    {
        $predictions = [];

        foreach ($data['predictions'] ?? [] as $item) {
            $predictions[$item['disease']] = [
                'label'       => $item['label'],
                'probability' => (float) $item['probability'],
                'percentage'  => $item['percentage'],
                'risk_level'  => $item['risk_level'],
            ];
        }

        return [
            'success'      => true,
            'model_mode'   => $data['model_mode'] ?? 'unknown',
            'predictions'  => $predictions,
            'highest_risk' => $data['highest_risk'] ?? '-',
            'raw'          => $data,
        ];
    }

    /**
     * Fallback lokal jika ML service tidak tersedia.
     * Menggunakan estimasi heuristik sederhana (sama dengan mode fallback FastAPI).
     * Ditandai dengan model_mode = 'local_fallback'.
     */
    private function localFallback(array $data): array
    {
        $age = (float) ($data['age'] ?? 40);
        $bmi = (float) ($data['bmi'] ?? 22);
        $glc = (float) ($data['glucose'] ?? 100);
        $bp  = (float) ($data['blood_pressure'] ?? 120);

        $clamp = fn(float $v): float => max(0.0, min(1.0, $v));

        $risks = [
            'heart_disease' => $clamp(($age / 120) * 0.4 + ($bp / 200) * 0.4 + ($bmi / 50) * 0.2),
            'stroke'        => $clamp(($age / 120) * 0.5 + ($bp / 200) * 0.3 + ($glc / 300) * 0.2),
            'diabetes'      => $clamp(($glc / 300) * 0.6 + ($bmi / 50) * 0.3 + ($age / 120) * 0.1),
            'hypertension'  => $clamp(($bp / 200) * 0.6  + ($bmi / 50) * 0.2 + ($age / 120) * 0.2),
            'ckd'           => $clamp(($age / 120) * 0.3 + ($bp / 200) * 0.3 + ($glc / 300) * 0.4),
        ];

        $displayMap = [
            'heart_disease' => 'Heart Disease',
            'stroke'        => 'Stroke',
            'diabetes'      => 'Diabetes',
            'hypertension'  => 'Hypertension',
            'ckd'           => 'Chronic Kidney Disease',
        ];

        $levelMap = fn(float $p): string => match(true) {
            $p < 0.30  => 'Low',
            $p < 0.60  => 'Moderate',
            default    => 'High',
        };

        $predictions = [];
        foreach ($risks as $key => $prob) {
            $predictions[$key] = [
                'label'       => $displayMap[$key],
                'probability' => round($prob, 4),
                'percentage'  => number_format($prob * 100, 1) . '%',
                'risk_level'  => $levelMap($prob),
            ];
        }

        $highestKey = array_search(max($risks), $risks);
        $highestPct = number_format($risks[$highestKey] * 100, 1);

        return [
            'success'      => true,
            'model_mode'   => 'local_fallback',
            'predictions'  => $predictions,
            'highest_risk' => "{$displayMap[$highestKey]} ({$highestPct}%)",
            'raw'          => [],
        ];
    }
}
