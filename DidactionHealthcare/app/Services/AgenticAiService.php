<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AgenticAiService
 *
 * Menyusun prompt terstruktur dari hasil prediksi ML dan data pasien,
 * lalu mengirimkannya ke LLM (OpenAI-compatible API) untuk mendapatkan
 * 3 saran kesehatan yang actionable dan spesifik.
 *
 * Flow:
 *   MlPredictionService::predict() → $mlResult
 *                                         ↓
 *   AgenticAiService::generateAdvice($mlResult, $userData)
 *       → buildSystemPrompt()  → instruksi peran LLM
 *       → buildUserPrompt()    → data pasien + risiko penyakit
 *       → callLlmApi()         → POST ke OpenAI /chat/completions
 *                                         ↓
 *                            string saran (teks / Markdown)
 */
class AgenticAiService
{
    /** Endpoint dasar LLM (OpenAI atau compatible, e.g. Groq, OpenRouter, Ollama) */
    private string $baseUrl;

    /** API Key untuk autentikasi */
    private string $apiKey;

    /** Nama model yang akan dipanggil */
    private string $model;

    /** Timeout request dalam detik */
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->apiKey  = (string) config('services.openai.api_key', '');
        $this->model   = (string) config('services.openai.model', 'gpt-4o-mini');
        $this->timeout = (int)    config('services.openai.timeout', 60);
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    /**
     * Generate 3 saran kesehatan actionable berdasarkan hasil prediksi ML.
     *
     * @param  array $mlResult  Output dari MlPredictionService::predict()
     *                          Struktur: { success, model_mode, predictions, highest_risk }
     * @param  array $userData  Data tambahan pasien:
     *                          { age, gender, bmi, glucose, blood_pressure, name? }
     *
     * @return array{
     *     success: bool,
     *     advice: string,
     *     model: string,
     *     prompt_tokens?: int,
     *     completion_tokens?: int,
     *     error?: string
     * }
     */
    public function generateAdvice(array $mlResult, array $userData): array
    {
        // Susun prompt
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt   = $this->buildUserPrompt($mlResult, $userData);

        Log::info('[AgenticAI] Mengirim prompt ke LLM', [
            'model'          => $this->model,
            'highest_risk'   => $mlResult['highest_risk'] ?? '-',
            'prompt_preview' => substr($userPrompt, 0, 200) . '...',
        ]);

        return $this->callLlmApi($systemPrompt, $userPrompt);
    }

    /**
     * Versi sederhana — hanya terima HealthRecord-like array.
     * Cocok untuk pemanggilan langsung dari controller tanpa perlu
     * memformat ulang data.
     *
     * @param  array $healthData  { age, gender, bmi, glucose, blood_pressure, ... }
     * @param  array $mlResult    Output dari MlPredictionService::predict()
     * @return array
     */
    public function adviseFromHealthData(array $healthData, array $mlResult): array
    {
        return $this->generateAdvice($mlResult, $healthData);
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
Anda adalah seorang konsultan kesehatan AI yang berpengalaman dan empatik.
Tugas Anda adalah menganalisis hasil skrining kesehatan pasien dan memberikan saran yang spesifik, actionable, dan berbasis bukti ilmiah.

## Aturan Respons

1. **Berikan TEPAT 3 saran** yang paling relevan dan actionable berdasarkan penyakit dengan risiko tertinggi.
2. **Format wajib** — gunakan Markdown numbered list:
   ```
   ### Saran 1: [Judul Singkat]
   [Penjelasan detail 2–3 kalimat. Sertakan angka/target spesifik bila memungkinkan.]
   
   ### Saran 2: [Judul Singkat]
   [Penjelasan...]
   
   ### Saran 3: [Judul Singkat]
   [Penjelasan...]
   ```
3. **Spesifik dan personal** — sesuaikan saran dengan usia, BMI, kadar glukosa, dan tekanan darah pasien yang diberikan.
4. **Actionable** — setiap saran harus bisa langsung dilakukan (bukan saran umum seperti "hidup sehat").
5. **Bahasa Indonesia** yang jelas dan mudah dipahami oleh orang awam.
6. **Disclaimer** — tambahkan satu kalimat disclaimer di akhir bahwa saran ini bukan pengganti konsultasi dokter.

## Hal yang DILARANG
- Mendiagnosis penyakit secara definitif
- Merekomendasikan obat-obatan spesifik dengan dosis
- Menggunakan bahasa yang menakut-nakuti pasien
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

    // ─── LLM API Call ──────────────────────────────────────────────────────────

    /**
     * Kirim prompt ke OpenAI Chat Completions API.
     *
     * Endpoint: POST {baseUrl}/chat/completions
     * Format:   OpenAI-compatible (bekerja juga dengan Groq, OpenRouter, Ollama, dll.)
     *
     * @param  string $systemPrompt  Instruksi peran LLM
     * @param  string $userPrompt    Data pasien + hasil prediksi ML
     * @return array
     */
    private function callLlmApi(string $systemPrompt, string $userPrompt): array
    {
        $endpoint = "{$this->baseUrl}/chat/completions";

        $payload = [
            'model'       => $this->model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role'    => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'temperature'     => 0.7,   // Cukup kreatif tapi tetap konsisten
            'max_tokens'      => 1024,  // ~600–700 kata saran
            'top_p'           => 0.9,
            'frequency_penalty' => 0.3, // Kurangi pengulangan kata
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($endpoint, $payload);

            // ── HTTP Error ────────────────────────────────────────────────────
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody  = $response->json('error.message', $response->body());

                Log::error('[AgenticAI] LLM API error', [
                    'status'   => $statusCode,
                    'endpoint' => $endpoint,
                    'error'    => $errorBody,
                ]);

                // Berikan saran fallback statis jika API gagal
                return $this->staticFallbackAdvice($statusCode, (string) $errorBody);
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (empty($content)) {
                Log::warning('[AgenticAI] LLM merespons kosong', ['response' => $data]);
                return $this->staticFallbackAdvice(200, 'Empty response from LLM');
            }

            Log::info('[AgenticAI] Saran berhasil digenerate', [
                'model'             => $data['model'] ?? $this->model,
                'prompt_tokens'     => $data['usage']['prompt_tokens']     ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            ]);

            return [
                'success'           => true,
                'advice'            => $content,
                'model'             => $data['model'] ?? $this->model,
                'prompt_tokens'     => $data['usage']['prompt_tokens']     ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            ];

        } catch (ConnectionException $e) {
            Log::error('[AgenticAI] Tidak dapat terhubung ke LLM API', [
                'endpoint' => $endpoint,
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
