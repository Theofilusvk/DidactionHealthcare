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

    /**
     * Generate personalized health plan from FastAPI.
     * If request fails, uses rule-based PHP local fallback matching FastAPI logic.
     *
     * @param  array $healthData User input metrics
     * @param  array $predictions ML disease prediction results
     * @return array Health plan containing meal_plan, activity_plan, and disclaimer
     */
    public function generateHealthPlan(array $healthData, array $predictions): array
    {
        $genderStr = ($healthData['gender'] ?? 1) == 1 ? 'Laki-laki' : 'Perempuan';
        $urineProteinStr = isset($healthData['protein_urine']) ? (string)$healthData['protein_urine'] : 'normal';
        $hba1cVal = isset($healthData['hba1c']) ? (float)$healthData['hba1c'] : 5.4;
        if ($hba1cVal < 2.0 || $hba1cVal > 20.0) {
            $hba1cVal = 5.4;
        }

        // Format predictions to match DiseaseRiskDetail schema in FastAPI
        $formattedPredictions = [];
        foreach ($predictions as $key => $p) {
            $formattedPredictions[$key] = [
                'probability' => (float) ($p['probability'] ?? 0.0),
                'percentage'  => $p['percentage'] ?? '0.0%',
                'risk_level'  => $p['risk_level'] ?? 'Low',
            ];
        }

        $payload = [
            'age'            => (int) ($healthData['age'] ?? 0),
            'gender'         => $genderStr,
            'glucose'        => (float) ($healthData['glucose'] ?? 100.0),
            'blood_pressure' => (int) ($healthData['blood_pressure'] ?? 120),
            'bmi'            => (float) ($healthData['bmi'] ?? 22.0),
            'activity_level' => $healthData['aktivitas_fisik'] ?? 'sedentary',
            'smoking_status' => $healthData['status_merokok'] ?? 'never',
            'urine_protein'  => $urineProteinStr,
            'hba1c'          => $hba1cVal,
            'predictions'    => $formattedPredictions,
        ];

        Log::info('[MLService] Mengirim permintaan generate-health-plan', [
            'url'     => $this->baseUrl . '/generate-health-plan',
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/generate-health-plan", $payload);

            if ($response->failed()) {
                Log::error('[MLService] HTTP error dari generate-health-plan', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->localFallbackHealthPlan($payload);
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('[MLService] Gagal memanggil generate-health-plan', [
                'error' => $e->getMessage(),
            ]);
            return $this->localFallbackHealthPlan($payload);
        }
    }

    /**
     * Local fallback for health plan matching python-ml-service rule-based output.
     */
    private function localFallbackHealthPlan(array $payload): array
    {
        $highestDisease = null;
        $highestProb = -1.0;

        foreach ($payload['predictions'] as $key => $v) {
            $normK = str_replace(' ', '_', strtolower($key));
            if ($v['probability'] > $highestProb) {
                $highestProb = $v['probability'];
                $highestDisease = $normK;
            }
        }

        if ($highestProb >= 0.30) {
            $mainCondition = $highestDisease;
        } else {
            if ($payload['blood_pressure'] >= 130) {
                $mainCondition = 'hypertension';
            } elseif ($payload['glucose'] >= 100.0 || $payload['hba1c'] >= 5.7) {
                $mainCondition = 'diabetes';
            } elseif (in_array(strtolower($payload['urine_protein']), ['trace', 'positive', '1+', '2+', '3+', '4+'])) {
                $mainCondition = 'ckd';
            } else {
                $mainCondition = 'general';
            }
        }

        // Default meal plan (General / Heart / Stroke)
        $mealPlan = [
            'prinsip_utama' => [
                'Batasi asupan lemak jenuh dan lemak trans (gorengan, mentega, daging berlemak).',
                'Konsumsi makanan tinggi serat larut dan asam lemak omega-3 (ikan seperti kembung, salmon, biji chia).',
                'Perbanyak buah, sayuran, dan kacang-kacangan untuk melindungi pembuluh darah.',
                'Batasi makanan olahan dan asupan gula berlebih.'
            ],
            'menu_harian' => [
                'sarapan' => 'Smoothie pisang-bayam dengan susu kedelai tanpa gula (1 gelas) dan 1 lembar roti gandum panggang',
                'snack_pagi' => 'Buah pepaya iris (1 mangkok kecil sekitar 100g)',
                'makan_siang' => 'Nasi merah (1 kepal), ikan kembung bakar (1 ekor sedang), tumis kangkung dengan sedikit minyak zaitun',
                'snack_sore' => 'Segenggam kecil kacang almond panggang tanpa garam (sekitar 10-15 butir)',
                'makan_malam' => 'Sup ayam bening dengan dada fillet tanpa kulit (50g), wortel, kentang, dan buncis'
            ]
        ];

        $activityPlan = [
            'jenis_olahraga' => ['Jalan kaki santai', 'Berenang', 'Bersepeda santai'],
            'frekuensi_per_minggu' => 4,
            'durasi_per_sesi_menit' => 30,
            'intensitas' => 'sedang',
            'catatan_keamanan' => [
                'Lakukan pemanasan dan pendinginan minimal 5-10 menit untuk meminimalkan beban jantung mendadak.',
                'Hentikan segera jika merasakan nyeri dada, sesak napas tidak biasa, atau jantung berdebar terlalu kencang.',
                'Hindari berolahraga di bawah terik matahari ekstrem dan tetap terhidrasi dengan baik.'
            ]
        ];

        if ($mainCondition === 'hypertension') {
            $mealPlan['prinsip_utama'] = [
                'Batasi asupan garam/natrium di bawah 5 gram (sekitar 1 sendok teh) per hari.',
                'Perbanyak konsumsi sayuran, buah-buahan, dan biji-bijian utuh (pola makan DASH).',
                'Hindari makanan olahan, makanan kaleng, dan makanan cepat saji yang tinggi natrium.',
                'Pilih sumber protein rendah lemak seperti dada ayam tanpa kulit, ikan, dan tempe/tahu.'
            ];
            $mealPlan['menu_harian'] = [
                'sarapan' => 'Oatmeal dengan pisang iris dan segelas susu rendah lemak (1 mangkok porsi sedang)',
                'snack_pagi' => '1 buah apel merah segar ukuran sedang',
                'makan_siang' => 'Nasi merah (1 kepal), dada ayam panggang tanpa kulit (100g) dengan rempah tanpa garam berlebih, dan tumis buncis wortel',
                'snack_sore' => 'Yogurt tawar rendah lemak (1 cup kecil sekitar 120ml)',
                'makan_malam' => 'Pepes tahu kukus (2 buah) dan sayur bening bayam (1 mangkok sedang)'
            ];
            $activityPlan['jenis_olahraga'] = ['Jalan cepat (brisk walking)', 'Bersepeda santai', 'Berenang'];
            $activityPlan['catatan_keamanan'] = [
                'Hindari latihan beban berat dengan menahan napas (Valsalva maneuver).',
                'Pantau tekanan darah sebelum dan sesudah berolahraga. Jangan berolahraga jika sistolik >180 mmHg.',
                'Segera hentikan olahraga jika merasa pusing, sakit kepala, dada sesak, atau napas tersengal berlebihan.'
            ];
        } elseif ($mainCondition === 'diabetes') {
            $mealPlan['prinsip_utama'] = [
                'Pilih makanan dengan indeks glikemik rendah untuk mencegah lonjakan gula darah mendadak.',
                'Batasi karbohidrat sederhana seperti gula pasir, sirup, nasi putih berlebih, dan makanan berbahan dasar tepung.',
                'Terapkan metode piring makan: setengah piring sayuran non-tepung, seperempat protein, seperempat karbohidrat kompleks.',
                'Konsumsi serat larut air dari buah-buahan seperti pir dan apel beserta sayuran hijau.'
            ];
            $mealPlan['menu_harian'] = [
                'sarapan' => 'Roti gandum utuh (2 lembar) dengan telur rebus (1 butir) dan alpukat iris (setengah buah)',
                'snack_pagi' => 'Segenggam kacang almond panggang tanpa garam (sekitar 10-12 butir)',
                'makan_siang' => 'Nasi merah atau quinoa (1 kepal), pepes ikan kembung (1 ekor sedang), dan lalapan sayur kukus (kol, brokoli)',
                'snack_sore' => 'Potongan pepaya segar (1 mangkok kecil sekitar 100g)',
                'makan_malam' => 'Tumis tahu tempe dengan sedikit minyak (1 piring sedang) dan sup bening brokoli wortel'
            ];
            $activityPlan['jenis_olahraga'] = ['Jalan santai/cepat', 'Senam diabetes', 'Bersepeda'];
            $activityPlan['frekuensi_per_minggu'] = 5;
            $activityPlan['catatan_keamanan'] = [
                'Periksa kadar gula darah sebelum berolahraga; jika <100 mg/dL, konsumsi snack kecil dahulu untuk mencegah hipoglikemia.',
                'Selalu bawa permen atau sumber glukosa cepat serap saat berolahraga sebagai antisipasi darurat hipoglikemia.',
                'Gunakan alas kaki dan kaos kaki yang nyaman dan pas untuk menghindari luka atau lecet pada kaki.'
            ];
        } elseif ($mainCondition === 'ckd') {
            $mealPlan['prinsip_utama'] = [
                'Batasi asupan protein harian (sekitar 0.6 - 0.8 gram per kg berat badan) untuk meringankan beban kerja ginjal.',
                'Batasi makanan tinggi kalium (seperti pisang, alpukat, kentang) jika ada kecenderungan kalium darah tinggi.',
                'Batasi makanan tinggi fosfor seperti produk susu sapi, kacang-kacangan, dan minuman bersoda.',
                'Batasi asupan natrium/garam untuk mengontrol tekanan darah dan penumpukan cairan tubuh.'
            ];
            $mealPlan['menu_harian'] = [
                'sarapan' => 'Bihun goreng dengan sedikit minyak dan sedikit sayur sawi hijau (1 porsi sedang, dimasak minim garam)',
                'snack_pagi' => '1 buah apel kupas ukuran sedang',
                'makan_siang' => 'Nasi putih (1 kepal kecil), dada ayam panggang (porsi kecil sekitar 50g), dan tumis labu siam dengan bumbu rempah bebas natrium',
                'snack_sore' => 'Buah pir manis (1 buah sedang)',
                'makan_malam' => 'Nasi putih (1 kepal kecil), tahu kukus (1 buah sedang), dan sup bening oyong/gambas'
            ];
            $activityPlan['jenis_olahraga'] = ['Jalan kaki santai', 'Senam ringan di rumah', 'Yoga peregangan'];
            $activityPlan['frekuensi_per_minggu'] = 3;
            $activityPlan['durasi_per_sesi_menit'] = 20;
            $activityPlan['catatan_keamanan'] = [
                'Lakukan olahraga dengan intensitas yang nyaman dan jangan memaksakan diri melampaui batas kemampuan.',
                'Perhatikan asupan cairan selama berolahraga agar tetap terhidrasi tanpa berlebihan (sesuai instruksi dokter).',
                'Hentikan olahraga jika merasa sangat lelah, pusing, atau sesak napas.'
            ];
        }

        if (($payload['bmi'] ?? 0.0) > 30.0) {
            array_unshift($mealPlan['prinsip_utama'], 'Terapkan defisit kalori sedang (kurangi sekitar 300-500 kkal dari kebutuhan energi harian Anda).');
            $mealPlan['prinsip_utama'][] = 'Tingkatkan konsumsi protein dan serat untuk membantu mempertahankan massa otot dan rasa kenyang lebih lama.';
            $activityPlan['jenis_olahraga'] = ['Berenang (sangat direkomendasikan untuk melindungi sendi)', 'Sepeda statis', 'Jalan santai/cepat (low impact)'];
            $activityPlan['catatan_keamanan'][] = 'Pilih jenis olahraga low-impact untuk menghindari cedera atau nyeri pada persendian lutut dan pergelangan kaki akibat beban berat.';
        }

        $actLower = strtolower($payload['activity_level'] ?? 'sedentary');
        $isSedentary = str_contains($actLower, 'sedentary') || str_contains($actLower, 'kurang') || str_contains($actLower, 'pasif') || str_contains($actLower, 'tidak aktif');
        if ($isSedentary) {
            $activityPlan['intensitas'] = 'ringan';
            $activityPlan['frekuensi_per_minggu'] = min(3, $activityPlan['frekuensi_per_minggu']);
            $activityPlan['durasi_per_sesi_menit'] = min(20, $activityPlan['durasi_per_sesi_menit']);
            $activityPlan['catatan_keamanan'][] = 'Karena Anda jarang beraktivitas fisik (sedentary), mulailah olahraga secara perlahan dari intensitas ringan dan tingkatkan durasi secara bertahap.';
        } else {
            $activityPlan['catatan_keamanan'][] = 'Lakukan pemanasan minimal 5-10 menit sebelum memulai olahraga untuk mencegah cedera otot.';
        }

        return [
            'meal_plan' => $mealPlan,
            'activity_plan' => $activityPlan,
            'disclaimer' => 'Rekomendasi rencana kesehatan ini dibuat berdasarkan analisis data kesehatan Anda secara otomatis menggunakan bantuan AI/aturan kesehatan umum dan hanya bertujuan sebagai panduan umum. Rekomendasi ini bukan pengganti diagnosis, saran medis, maupun rencana perawatan dari dokter atau tenaga medis profesional. Selalu berkonsultasi dengan dokter sebelum memulai program diet atau olahraga baru.'
        ];
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

        $highestKey = array_keys($risks, max($risks))[0];
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
