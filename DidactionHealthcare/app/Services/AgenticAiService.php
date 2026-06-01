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
        $this->model   = (string) config('services.gemini.model',   'gemini-1.5-flash');
        $this->timeout = (int)    config('services.gemini.timeout', 60);
        $this->apiKey  = (string) config('services.gemini.key',     '');

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
Anda adalah asisten kesehatan AI proaktif yang berpengalaman dan empatik.
Tugas Anda adalah menganalisis hasil skrining kesehatan pasien dan memberikan rekomendasi tindakan perbaikan yang spesifik, actionable, dan berbasis bukti ilmiah.

## Format Respons WAJIB

Anda HARUS mengembalikan respons HANYA dalam format JSON berikut — tanpa teks tambahan, tanpa markdown, tanpa komentar:

```json
{
  "recommendations": [
    {
      "title": "Judul singkat saran (maks 8 kata)",
      "description": "Penjelasan detail 2–3 kalimat. Sertakan angka/target spesifik bila memungkinkan.",
      "priority": "Tinggi" | "Sedang" | "Rendah"
    },
    { ... },
    { ... }
  ],
  "disclaimer": "Satu kalimat disclaimer bahwa saran ini bukan pengganti konsultasi dokter."
}
```

## Aturan Konten

1. Berikan **TEPAT 3 saran** tindakan perbaikan yang paling relevan berdasarkan risiko tertinggi.
2. Sesuaikan saran dengan usia, BMI, glukosa, dan tekanan darah pasien secara **personal dan spesifik**.
3. Setiap saran harus **langsung dapat dilakukan** (bukan saran umum).
4. Gunakan **Bahasa Indonesia** yang jelas dan mudah dipahami orang awam.
5. Tetapkan `priority` berdasarkan urgensi klinis: "Tinggi" untuk faktor risiko dominan.

## Hal yang DILARANG
- Mendiagnosis penyakit secara definitif
- Merekomendasikan obat-obatan spesifik dengan dosis
- Menggunakan bahasa yang menakut-nakuti pasien
- Menambahkan teks di luar objek JSON
PROMPT;
    }

    /**
     * Build User Prompt: The actual patient data and disease predictions to analyze
     *
     * This prompt includes:
     *  1. Patient Profile: Age, sex, BMI, blood glucose, blood pressure, cholesterol, heart rate
     *  2. Disease Risk Table: Probability percentage for each of 5 diseases with risk levels
     *  3. Highest Risk Disease: Which disease is the #1 concern for this patient
     *  4. Clear Instructions: Tell Gemini to focus on the highest risk but consider all factors
     */
    private function buildUserPrompt(array $mlResult, array $userData): string
    {
        // Extract and format patient health metrics for the prompt
        $age    = $userData['age']            ?? 'N/A';
        $gender = ($userData['gender'] ?? 1) == 1 ? 'Male' : 'Female';
        $bmi    = number_format((float) ($userData['bmi'] ?? 0), 1);
        $glc    = number_format((float) ($userData['glucose'] ?? 0), 1);
        $bp     = $userData['blood_pressure'] ?? 'N/A';
        $chol   = isset($userData['cholesterol'])
            ? number_format((float) $userData['cholesterol'], 1) . ' mg/dL'
            : 'Unknown';
        $hr     = $userData['heart_rate'] ?? 'Unknown';

        // Convert BMI to user-friendly category (underweight, normal, overweight, obese)
        $bmiCategory = $this->bmiCategory((float) ($userData['bmi'] ?? 0));

        // Create a nice formatted table of disease risks for Gemini to see
        $riskTable = $this->formatRiskTable($mlResult['predictions'] ?? []);

        // Find which disease has the highest risk (focus point for recommendations)
        $highestRisk  = $mlResult['highest_risk'] ?? 'Unknown';
        $modelMode    = $mlResult['model_mode']   ?? 'unknown';

        return <<<PROMPT
## Data Pasien

| Parameter          | Nilai                       |
|--------------------|------------------------------|
| Usia               | {$age} tahun                 |
| Jenis Kelamin      | {$gender}                    |
| BMI                | {$bmi} kg/m² ({$bmiCategory})|
| Glukosa Darah      | {$glc} mg/dL                 |
| Tekanan Darah      | {$bp} mmHg (sistolik)        |
| Kolesterol Total   | {$chol}                      |
| Detak Jantung      | {$hr} bpm                    |

## Hasil Prediksi Risiko Penyakit (Model: {$modelMode})

{$riskTable}

**Penyakit dengan Risiko Tertinggi: {$highestRisk}**

---

## Instruksi

Berdasarkan profil pasien dan hasil prediksi di atas, berikan **tepat 3 saran kesehatan yang actionable dan spesifik**.
Fokuskan saran pada penyakit dengan risiko tertinggi, namun pertimbangkan juga faktor risiko lain yang relevan dari profil pasien.
Gunakan format yang telah ditentukan dalam instruksi sistem.
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
        $payload = [
            // System Instruction: Tells Gemini its role and rules
            // Think of this as: "Here's how you should act when responding"
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],

            // User Message: The actual patient data and disease predictions
            // This is the question we're asking Gemini: "Given this patient, what should we recommend?"
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],

            // Generation Settings: Control how Gemini thinks and responds
            'generationConfig' => [
                // Temperature: How creative vs. factual the response should be
                // 0.7 = balanced (not too random, not too boring)
                'temperature'     => 0.7,
                // Maximum tokens: Roughly how long the response can be (~500-700 words)
                'maxOutputTokens' => 1024,
                // Top P: How diverse the word choices should be (0.9 = pretty diverse)
                'topP'            => 0.9,
                // Response Format: Ask Gemini to respond with clean JSON (not mixed text)
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

            // Parse the JSON response from Gemini
            // We ask Gemini to respond in JSON format, so we decode it here
            $parsed          = json_decode($rawText, true);
            $recommendations = $parsed['recommendations'] ?? [];
            $disclaimer      = $parsed['disclaimer'] ?? '';

            // If Gemini didn't return valid JSON or recommendations, we'll log it and use raw text
            if (empty($recommendations)) {
                Log::warning('[AgenticAI] Gagal parse JSON rekomendasi, menggunakan teks mentah', [
                    'raw_text' => substr($rawText, 0, 300),
                ]);
            }

            // Track how many tokens (words) were used
            // This helps us monitor API costs and optimize prompts
            $usageMeta = $data['usageMetadata'] ?? [];

            Log::info('[AgenticAI] Rekomendasi berhasil digenerate via Gemini', [
                'model'                  => $this->model,
                'prompt_token_count'     => $usageMeta['promptTokenCount']     ?? 0,
                'candidates_token_count' => $usageMeta['candidatesTokenCount'] ?? 0,
                'recommendations_count'  => count($recommendations),
            ]);

            return [
                'success'                => true,
                'recommendations'        => $recommendations,   // array JSON terstruktur
                'disclaimer'             => $disclaimer,
                'advice'                 => $rawText,           // teks mentah (kompatibilitas)
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
        $advice = <<<ADVICE
> ⚠️ **Catatan**: Saran AI saat ini tidak tersedia. Berikut adalah panduan umum berdasarkan standar kesehatan WHO.

### Saran 1: Pantau Kadar Glukosa dan Tekanan Darah Secara Rutin
Lakukan pengecekan gula darah dan tekanan darah minimal 1 kali per bulan. Target kadar glukosa puasa yang sehat adalah 70–100 mg/dL, dan tekanan darah ideal di bawah 120/80 mmHg. Catat hasilnya untuk dilaporkan ke dokter pada kunjungan berikutnya.

### Saran 2: Terapkan Pola Makan Seimbang dengan Prinsip Isi Piring
Isi setengah piring dengan sayuran dan buah, seperempat dengan protein tanpa lemak (ikan, tahu, tempe), dan seperempat dengan karbohidrat kompleks (nasi merah, ubi). Batasi konsumsi gula tambahan di bawah 25 gram per hari dan garam di bawah 5 gram per hari sesuai anjuran WHO.

### Saran 3: Lakukan Aktivitas Fisik Terstruktur Minimal 150 Menit per Minggu
Targetkan setidaknya 30 menit olahraga intensitas sedang (jalan cepat, bersepeda, renang) selama 5 hari per minggu. Mulai secara bertahap jika belum terbiasa berolahraga, dan konsultasikan dengan dokter sebelum memulai program olahraga jika Anda memiliki kondisi kesehatan tertentu.

---
*⚕️ Saran ini bersifat umum dan bukan pengganti konsultasi dengan tenaga medis profesional.*
ADVICE;

        return [
            'success' => false,
            'advice'  => $advice,
            'model'   => 'static_fallback',
            'error'   => "LLM API unavailable (HTTP {$statusCode}): {$errorMsg}",
        ];
    }
}
