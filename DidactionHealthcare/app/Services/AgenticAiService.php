<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AgenticAiService — Transform Predictions into Personalized Health Advice
 *
 * This service takes disease predictions from the machine learning model and patient data,
 * then sends them to Google Gemini AI to generate personalized health recommendations.
 *
 * The complete flow works like this:
 *   1. Patient Health Data Input (age, BMI, glucose, blood pressure, etc)
 *   2. ML Service Prediction (get disease probabilities: heart disease, stroke, etc)
 *   3. Format Data into Structured Prompts
 *   4. Send to Google Gemini AI with system instructions
 *   5. Receive 3 actionable health recommendations in JSON format
 *   6. Return structured recommendations with priority levels to patient
 */
class AgenticAiService
{
    /** Complete API endpoint URL for Google Gemini (includesmodel and API key) */
    private string $geminiEndpoint;

    /** Secret API key from Google Cloud Console - used to authenticate with Gemini API */
    private string $apiKey;

    /** Which Gemini model to use (e.g., 'gemini-1.5-flash' for fast, cost-effective responses) */
    private string $model;

    /** How long to wait for Gemini to respond before timing out (in seconds) */
    private int $timeout;

    public function __construct()
    {
        $this->model   = (string) config('services.gemini.model',   'gemini-pro');
        $this->timeout = (int)    config('services.gemini.timeout', 60);
        $this->apiKey  = (string) config('services.gemini.key',     '');

        // Standard Google Cloud API endpoint (works with Google Cloud Console keys)
        $this->geminiEndpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    //  PUBLIC METHODS — These are the main ways to use this service
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Main Entry Point: Get personalized health recommendations from Google Gemini AI
     *
     * Takes patient health data and machine learning disease predictions, then asks
     * Google Gemini to generate 3 specific, actionable health recommendations.
     * This is the primary method to use when you have both patient data and ML predictions.
     *
     * @param  array $userData       Data pasien: { age, gender, bmi, glucose, blood_pressure, ... }
     * @param  array $mlPredictions  Output dari MlPredictionService::predict():
     *                               { success, model_mode, predictions, highest_risk }
     *
     * @return array{
     *     success: bool,
     *     recommendations: array<int, array{ title: string, description: string, priority: string }>,
     *     advice: string,          // teks mentah dari Gemini (fallback / kompatibilitas)
     *     model: string,
     *     prompt_token_count?: int,
     *     candidates_token_count?: int,
     *     error?: string
     * }
     */
    public function getHealthRecommendations(array $userData, array $mlPredictions): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt   = $this->buildUserPrompt($mlPredictions, $userData);

        Log::info('[AgenticAI] Mengirim request ke Gemini', [
            'model'        => $this->model,
            'highest_risk' => $mlPredictions['highest_risk'] ?? '-',
            'user_age'     => $userData['age'] ?? 'N/A',
        ]);

        return $this->callLlmApi($systemPrompt, $userPrompt);
    }

    /**
     * Backward Compatibility: Old method name that still works
     * 
     * If you have old code using generateAdvice(), it still works!
     * But please migrate to getHealthRecommendations() for clearer naming.
     *
     * @param  array $mlResult  Disease predictions from MlPredictionService::predict()
     * @param  array $userData  Patient health data (age, BMI, glucose, blood pressure, etc)
     * @return array Health recommendations from Gemini
     */
    public function generateAdvice(array $mlResult, array $userData): array
    {
        return $this->getHealthRecommendations($userData, $mlResult);
    }

    /**
     * Alternative Method Name: Also calls the main getHealthRecommendations()
     * 
     * This is another name for the same functionality. Use whichever makes
     * most sense in your code context. Both do exactly the same thing.
     *
     * @param  array $healthData Patient vitals and metrics (age, gender, BMI, glucose, blood pressure)
     * @param  array $mlResult Disease predictions from the ML model
     * @return array Structured health recommendations with priorities
     */
    public function adviseFromHealthData(array $healthData, array $mlResult): array
    {
        return $this->getHealthRecommendations($healthData, $mlResult);
    }

    /**
     * Build System Prompt: Tells Gemini its role and what rules to follow
     *
     * This is like giving Gemini "character instructions" that say:
     *  - Act as a caring, experienced health consultant
     *  - Always give exactly 3 recommendations (not more, not less)
     *  - Make recommendations specific and actionable (patient can do them today)
     *  - Always include a medical disclaimer at the end
     *  - Respond in Indonesian, using simple language anyone can understand
     *  - Never try to diagnose; only recommend prevention actions
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Kamu adalah 'HealthAgent', sebuah asisten AI kesehatan yang empatik dan analitis. 
Tugasmu adalah memberikan saran tindakan pencegahan berdasarkan hasil prediksi Machine Learning dan metrik tubuh pengguna.

Instruksi:
1. Analisis metrik pengguna dan risiko penyakit yang diberikan.
2. Jangan mendiagnosis medis, tetapi berikan 3 langkah 'Actionable Plan' yang spesifik, praktis, dan disesuaikan dengan kondisi mereka (contoh: jenis olahraga yang cocok untuk BMI tersebut, target asupan gizi).
3. Berikan jawaban HANYA dalam format JSON dengan struktur persis seperti ini: 
{
  "summary": "Kesimpulan singkat kondisi pasien...",
  "action_plans": [
    {
      "title": "Judul tindakan...", 
      "description": "Penjelasan tindakan...",
      "priority": "Tinggi"
    }
  ]
}
Catatan: Untuk 'priority' isikan dengan Tinggi, Sedang, atau Rendah.
PROMPT;
    }

    private function buildUserPrompt(array $mlResult, array $userData): string
    {
        // Extract and format patient health metrics for the prompt
        $age    = $userData['age']            ?? 'N/A';
        $gender = ($userData['gender'] ?? 1) == 1 ? 'Laki-laki' : 'Perempuan';
        $bmi    = number_format((float) ($userData['bmi'] ?? 0), 1);
        $glc    = number_format((float) ($userData['glucose'] ?? 0), 1);
        $bp     = $userData['blood_pressure'] ?? 'N/A';
        $chol   = isset($userData['cholesterol'])
            ? number_format((float) $userData['cholesterol'], 1) . ' mg/dL'
            : 'Unknown';
        $hr     = $userData['heart_rate'] ?? 'Unknown';

        // Extract probabilities
        $preds = $mlResult['predictions'] ?? [];
        $diabetesRisk = isset($preds['diabetes']) ? number_format($preds['diabetes']['probability'] * 100, 1) : 'N/A';
        $htRisk = isset($preds['hypertension']) ? number_format($preds['hypertension']['probability'] * 100, 1) : 'N/A';

        // Create a nice formatted table of disease risks for Gemini to see
        $riskTable = $this->formatRiskTable($mlResult['predictions'] ?? []);

        return <<<PROMPT
Data Pengguna:
- Usia: {$age}
- Gender: {$gender}
- BMI: {$bmi}
- Glukosa: {$glc}
- Tekanan Darah: {$bp}

Hasil Prediksi Machine Learning:
- Risiko Diabetes: {$diabetesRisk}%
- Risiko Hipertensi: {$htRisk}%

Seluruh Hasil Prediksi:
{$riskTable}

Berikan JSON sesuai instruksi sistem berdasarkan data di atas. Hasilkan raw JSON saja, tanpa dibungkus markdown ```json.
PROMPT;
    }

    /**
     * Format Disease Risk Data as Markdown Table: Makes it easy for Gemini to read
     * 
     * Converts the disease prediction data into a nice formatted table that shows:
     *  - Disease name
     *  - Probability percentage
     *  - Risk level (Low/Medium/High with emoji indicators)
     */
    private function formatRiskTable(array $predictions): string
    {
        if (empty($predictions)) {
            return '_Data prediksi tidak tersedia_';
        }

        $header = "| Penyakit                  | Probabilitas | Level Risiko |\n";
        $header .= "|---------------------------|:------------:|:------------:|\n";

        $rows = '';
        $displayMap = [
            'heart_disease' => 'Penyakit Jantung',
            'stroke'        => 'Stroke',
            'diabetes'      => 'Diabetes',
            'hypertension'  => 'Hipertensi',
            'ckd'           => 'Gagal Ginjal Kronis (CKD)',
        ];

        foreach ($predictions as $key => $item) {
            $label    = $displayMap[$key] ?? ($item['label'] ?? $key);
            $pct      = $item['percentage'] ?? number_format(($item['probability'] ?? 0) * 100, 1) . '%';
            $level    = $item['risk_level'] ?? '-';
            $levelId  = match($level) {
                'High'     => '🔴 Tinggi',
                'Moderate' => '🟡 Sedang',
                'Low'      => '🟢 Rendah',
                default    => $level,
            };

            $rows .= sprintf("| %-25s | %12s | %12s |\n", $label, $pct, $levelId);
        }

        return $header . $rows;
    }

    /**
     * Convert BMI number into user-friendly category
     * 
     * This helps patients understand their weight status without medical jargon:
     *  - Under 18.5: Underweight (too thin)
     *  - 18.5-25: Normal (healthy weight range)
     *  - 25-30: Overweight (above healthy range)
     *  - Over 30: Obese (significantly above healthy range)
     */
    private function bmiCategory(float $bmi): string
    {
        return match(true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25.0 => 'Normal Weight',
            $bmi < 30.0 => 'Overweight',
            default     => 'Obese',
        };
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    //  GEMINI API COMMUNICATION — Send prompts to Google Gemini and parse responses
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Send Prompts to Google Gemini AI and Get Health Recommendations
     *
     * This method makes an HTTP POST request to Google Gemini API with:
     *  - System Prompt: Instructions that define Gemini's role (what it should act like)
     *  - User Prompt: The actual patient data and disease risks for analysis
     *
     * Gemini responds with structured JSON containing 3 health recommendations.
     * If anything goes wrong, we fall back to pre-written static advice.
     *
     * API Documentation: https://ai.google.dev/api/generate-content
     *
     * @param  string $systemPrompt The role definition and rules for Gemini to follow
     * @param  string $userPrompt The patient data and disease risks to analyze
     * @return array Result with recommendations or fallback advice
     */
    private function callLlmApi(string $systemPrompt, string $userPrompt): array
    {
        // Combine system prompt and user prompt into a single message
        // Google AI Studio v1 endpoint doesn't support system_instruction, so we prepend it
        $combinedPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $payload = [
            // User Message: The actual patient data and disease predictions
            // This is the question we're asking Gemini: "Given this patient, what should we recommend?"
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $combinedPrompt],
                    ],
                ],
            ],

            // Generation Settings: Control how Gemini thinks and responds
            'generationConfig' => [
                // Temperature: How creative vs. factual the response should be
                // 0.7 = balanced (not too random, not too boring)
                'temperature'      => 0.7,
                // Maximum tokens: Gemini 2.5 uses 'thoughts' which consume tokens. Set high!
                'maxOutputTokens'  => 8192,
                // Top P: How diverse the word choices should be (0.9 = pretty diverse)
                'topP'             => 0.9,
                // Paksa Gemini untuk membalas dengan format valid JSON
                'responseMimeType' => 'application/json',
            ],
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->geminiEndpoint, $payload);

            // Check if the HTTP request failed (4xx or 5xx status code)
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody  = $response->json('error.message', $response->body());

                Log::error('[AgenticAI] Gemini API error', [
                    'status'   => $statusCode,
                    'endpoint' => $this->geminiEndpoint,
                    'error'    => $errorBody,
                ]);

                return $this->staticFallbackAdvice($statusCode, (string) $errorBody);
            }

            $data = $response->json();

            // Extract the actual text response from Gemini's response structure
            // Gemini wraps its response in: candidates[0].content.parts[0].text
            // So we navigate through this nested structure to get the recommendations
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (empty($rawText)) {
                Log::warning('[AgenticAI] Gemini merespons kosong', ['response' => $data]);
                return $this->staticFallbackAdvice(200, 'Empty response from Gemini');
            }

            // Ensure rawText is clean (remove markdown if Gemini adds it)
            $rawText = trim($rawText);
            if (str_starts_with($rawText, '```json')) {
                $rawText = str_replace(['```json', '```'], '', $rawText);
            } elseif (str_starts_with($rawText, '```')) {
                $rawText = str_replace('```', '', $rawText);
            }
            $rawText = trim($rawText);

            // Parse the JSON response from Gemini
            $parsed       = json_decode($rawText, true);
            $actionPlans  = $parsed['action_plans'] ?? [];
            $summary      = $parsed['summary'] ?? '';

            if (empty($actionPlans)) {
                Log::warning('[AgenticAI] Gagal parse JSON rekomendasi, menggunakan teks mentah', [
                    'raw_text' => substr($rawText, 0, 300),
                ]);
            }

            $usageMeta = $data['usageMetadata'] ?? [];

            Log::info('[AgenticAI] Rekomendasi berhasil digenerate via Gemini', [
                'model'                  => $this->model,
                'prompt_token_count'     => $usageMeta['promptTokenCount']     ?? 0,
                'candidates_token_count' => $usageMeta['candidatesTokenCount'] ?? 0,
                'action_plans_count'     => count($actionPlans),
            ]);

            return [
                'success'                => true,
                'action_plans'           => $actionPlans,
                'summary'                => $summary,
                'advice'                 => $rawText,
                'model'                  => $this->model,
                'prompt_token_count'     => $usageMeta['promptTokenCount']     ?? 0,
                'candidates_token_count' => $usageMeta['candidatesTokenCount'] ?? 0,
            ];

        } catch (ConnectionException $e) {
            Log::error('[AgenticAI] Tidak dapat terhubung ke Gemini API', [
                'endpoint' => $this->geminiEndpoint,
                'error'    => $e->getMessage(),
            ]);

            return $this->staticFallbackAdvice(0, 'Connection failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    //  FALLBACK ADVICE — When Gemini API is down or fails
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Fallback Static Advice: If Gemini API fails, we still help the patient
     * 
     * We pre-wrote some sensible health recommendations that are always good advice.
     * When Gemini is unavailable, we show these generic-but-helpful recommendations
     * instead of showing the patient an error message.
     *
     * @param  int    $statusCode HTTP status code dari LLM API
     * @param  string $errorMsg   Pesan error untuk logging
     * @return array
     */
    private function staticFallbackAdvice(int $statusCode, string $errorMsg): array
    {
        return [
            'success'      => false,
            'summary'      => 'Saran AI saat ini tidak tersedia. Berikut adalah panduan umum berdasarkan standar kesehatan.',
            'action_plans' => [
                [
                    'title'       => 'Pantau Kadar Glukosa',
                    'description' => 'Lakukan pengecekan gula darah dan tekanan darah rutin. Target glukosa puasa 70–100 mg/dL.',
                    'priority'    => 'Tinggi'
                ],
                [
                    'title'       => 'Pola Makan Seimbang',
                    'description' => 'Isi setengah piring dengan sayuran dan buah, batasi gula di bawah 25 gram per hari.',
                    'priority'    => 'Sedang'
                ],
                [
                    'title'       => 'Aktivitas Fisik',
                    'description' => 'Targetkan olahraga intensitas sedang minimal 30 menit per hari.',
                    'priority'    => 'Sedang'
                ]
            ],
            'advice'       => 'LLM API unavailable.',
            'model'        => 'static_fallback',
            'error'        => "LLM API unavailable (HTTP {$statusCode}): {$errorMsg}",
        ];
    }
}
