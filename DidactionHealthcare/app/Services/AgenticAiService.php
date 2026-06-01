<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AgenticAiService
 *
 * Menyusun prompt terstruktur dari hasil prediksi ML dan data pasien,
 * lalu mengirimkannya ke Google Gemini 1.5 Flash untuk mendapatkan
 * 3 saran kesehatan yang actionable dan spesifik dalam format JSON.
 *
 * Flow:
 *   MlPredictionService::predict() → $mlPredictions
 *                                         ↓
 *   AgenticAiService::getHealthRecommendations($userData, $mlPredictions)
 *       → buildSystemPrompt()  → instruksi peran LLM (system_instruction)
 *       → buildUserPrompt()    → data pasien + risiko penyakit
 *       → callLlmApi()         → POST ke Gemini generateContent
 *                                         ↓
 *                            array JSON { success, recommendations[], model, ... }
 */
class AgenticAiService
{
    /** Base URL endpoint Gemini generateContent */
    private string $geminiEndpoint;

    /** API Key Gemini dari config('services.gemini.key') */
    private string $apiKey;

    /** Nama model Gemini yang digunakan */
    private string $model;

    /** Timeout request dalam detik */
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

    // ─── Public API ────────────────────────────────────────────────────────────

    /**
     * Dapatkan rekomendasi kesehatan terstruktur dari Gemini berdasarkan
     * data pengguna dan hasil prediksi ML.
     *
     * Ini adalah entry point utama yang baru menggantikan generateAdvice().
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
     * Alias lama — tetap tersedia agar controller yang sudah ada tidak perlu diubah.
     *
     * @param  array $mlResult  Output dari MlPredictionService::predict()
     * @param  array $userData  Data pasien
     * @return array
     */
    public function generateAdvice(array $mlResult, array $userData): array
    {
        return $this->getHealthRecommendations($userData, $mlResult);
    }

    /**
     * Alias lama — tetap tersedia agar controller yang sudah ada tidak perlu diubah.
     *
     * @param  array $healthData  { age, gender, bmi, glucose, blood_pressure, ... }
     * @param  array $mlResult    Output dari MlPredictionService::predict()
     * @return array
     */
    public function adviseFromHealthData(array $healthData, array $mlResult): array
    {
        return $this->getHealthRecommendations($healthData, $mlResult);
    }

    // ─── Prompt Builders ───────────────────────────────────────────────────────

    /**
     * Bangun System Prompt — menentukan peran dan aturan respons LLM.
     *
     * Instruksi:
     *  - Bertindak sebagai konsultan kesehatan profesional
     *  - Berikan tepat 3 saran yang actionable dan spesifik
     *  - Format terstruktur (Markdown numbered list)
     *  - Selalu tambahkan disclaimer medis di akhir
     *  - Respons dalam Bahasa Indonesia
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
     * Bangun User Prompt — berisi data pasien dan hasil prediksi ML.
     *
     * Struktur prompt:
     *  [1] Profil pasien (usia, gender, BMI, glukosa, tekanan darah)
     *  [2] Hasil prediksi probabilitas per penyakit + level risiko
     *  [3] Penyakit dengan risiko tertinggi (prioritas utama saran)
     *  [4] Instruksi eksplisit untuk format respons
     */
    private function buildUserPrompt(array $mlResult, array $userData): string
    {
        // ── Format profil pasien ──────────────────────────────────────────────
        $age    = $userData['age']            ?? 'N/A';
        $gender = ($userData['gender'] ?? 1) == 1 ? 'Laki-laki' : 'Perempuan';
        $bmi    = number_format((float) ($userData['bmi'] ?? 0), 1);
        $glc    = number_format((float) ($userData['glucose'] ?? 0), 1);
        $bp     = $userData['blood_pressure'] ?? 'N/A';
        $chol   = isset($userData['cholesterol'])
            ? number_format((float) $userData['cholesterol'], 1) . ' mg/dL'
            : 'Tidak diketahui';
        $hr     = $userData['heart_rate'] ?? 'Tidak diketahui';

        $bmiCategory = $this->bmiCategory((float) ($userData['bmi'] ?? 0));

        // ── Format tabel risiko penyakit ─────────────────────────────────────
        $riskTable = $this->formatRiskTable($mlResult['predictions'] ?? []);

        // ── Risiko tertinggi ──────────────────────────────────────────────────
        $highestRisk  = $mlResult['highest_risk'] ?? 'Tidak diketahui';
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
     * Format tabel prediksi risiko dari output MlPredictionService.
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
     * Kategorisasi BMI dalam Bahasa Indonesia.
     */
    private function bmiCategory(float $bmi): string
    {
        return match(true) {
            $bmi < 18.5 => 'Kurus',
            $bmi < 25.0 => 'Normal',
            $bmi < 30.0 => 'Kelebihan Berat Badan',
            default     => 'Obesitas',
        };
    }

    // ─── Gemini API Call ───────────────────────────────────────────────────────

    /**
     * Kirim prompt ke Google Gemini generateContent API.
     *
     * Endpoint : POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={API_KEY}
     * Dokumen  : https://ai.google.dev/api/generate-content
     *
     * Payload menggunakan format native Gemini:
     *   - system_instruction → instruksi peran LLM
     *   - contents           → pesan pengguna
     *   - generationConfig   → suhu, token, mime type JSON
     *
     * @param  string $systemPrompt  Instruksi peran LLM (system_instruction)
     * @param  string $userPrompt    Data pasien + hasil prediksi ML
     * @return array
     */
    private function callLlmApi(string $systemPrompt, string $userPrompt): array
    {
        $payload = [
            // Instruksi sistem — memberi peran dan aturan ke model
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],

            // Pesan pengguna — data pasien + prediksi ML
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],

            // Konfigurasi generasi
            'generationConfig' => [
                'temperature'     => 0.7,              // Cukup kreatif, tetap konsisten
                'maxOutputTokens' => 1024,             // ~600–700 kata
                'topP'            => 0.9,
                'responseMimeType' => 'application/json', // Minta respons JSON langsung
            ],
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->geminiEndpoint, $payload);

            // ── HTTP Error ────────────────────────────────────────────────────
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

            // ── Ekstrak teks dari struktur respons Gemini ─────────────────────
            // Struktur: candidates[0].content.parts[0].text
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (empty($rawText)) {
                Log::warning('[AgenticAI] Gemini merespons kosong', ['response' => $data]);
                return $this->staticFallbackAdvice(200, 'Empty response from Gemini');
            }

            // ── Parse JSON terstruktur dari respons Gemini ────────────────────
            $parsed          = json_decode($rawText, true);
            $recommendations = $parsed['recommendations'] ?? [];
            $disclaimer      = $parsed['disclaimer'] ?? '';

            // Jika JSON tidak valid / tidak sesuai skema, gunakan teks mentah sebagai advice
            if (empty($recommendations)) {
                Log::warning('[AgenticAI] Gagal parse JSON rekomendasi, menggunakan teks mentah', [
                    'raw_text' => substr($rawText, 0, 300),
                ]);
            }

            // ── Token usage ───────────────────────────────────────────────────
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

    // ─── Fallback ─────────────────────────────────────────────────────────────

    /**
     * Saran statis jika LLM API tidak tersedia / gagal.
     * Memastikan pengguna tetap mendapat output yang berguna.
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
