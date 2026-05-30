<?php

namespace App\Services;

use App\Models\HealthRecord;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MlPredictionService
 *
 * Mengirim data kesehatan pengguna ke Python FastAPI ML Service
 * dan mengembalikan hasil prediksi probabilitas 5 penyakit.
 *
 * Flow:
 *   Controller → MlPredictionService::predict() → POST /predict (FastAPI)
 *                                                ↓
 *                                  array prediksi probabilitas
 *                                                ↓
 *   AgenticAiService::generateAdvice() → prompt ke LLM
 */
class MlPredictionService
{
    /**
     * Base URL service Python FastAPI.
     * Diambil dari env ML_SERVICE_URL (default: http://127.0.0.1:8001).
     */
    private string $baseUrl;

    /**
     * Timeout request dalam detik.
     */
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (int) config('services.ml.timeout', 30);
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    /**
     * Kirim data kesehatan ke ML service dan kembalikan hasil prediksi.
     *
     * @param  array{
     *     age: int,
     *     gender: int,
     *     bmi: float,
     *     glucose: float,
     *     blood_pressure: int,
     *     cholesterol?: float,
     *     heart_rate?: int
     * } $healthData Data input pasien
     *
     * @return array{
     *     success: bool,
     *     model_mode: string,
     *     predictions: array<string, array{probability: float, percentage: string, risk_level: string}>,
     *     highest_risk: string,
     *     raw: array
     * }
     *
     * @throws \RuntimeException Jika service tidak dapat dihubungi
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

        $highestKey = array_key_max($risks);
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
