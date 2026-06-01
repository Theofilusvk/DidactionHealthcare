<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HealthRecordDatasetSeeder
 *
 * Mengimpor data dari file CSV dataset ke tabel health_records.
 * Data ini akan digunakan sebagai training data untuk model XGBoost.
 *
 * Dataset yang diimpor:
 *   - heart.csv           → label: heart_disease_risk
 *   - diabetes.csv        → label: diabetes_risk
 *   - healthcare-dataset-stroke-data.csv → label: stroke_risk
 *   - hypertension_dataset.csv           → label: hypertension_risk
 *   - CKD: digenerate dari threshold klinis (tidak ada dataset terpisah)
 *
 * Jalankan dengan: php artisan db:seed --class=HealthRecordDatasetSeeder
 */
class HealthRecordDatasetSeeder extends Seeder
{
    /** Direktori Databases/ relatif dari root project */
    private string $dataDir;

    /** Batas baris per file agar tidak terlalu lama */
    private int $limitPerFile = 5000;

    public function __construct()
    {
        // ROOT_PROJECT/../Databases/
        $this->dataDir = base_path('../Databases');
    }

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║   DHC Dataset Seeder — CSV → health_records      ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');

        // Truncate dulu agar tidak duplikat
        DB::table('health_records')->truncate();
        $this->command->line('  → Tabel health_records dikosongkan');

        $total = 0;

        $total += $this->seedHeart();
        $total += $this->seedDiabetes();
        $total += $this->seedStroke();
        $total += $this->seedHypertension();

        $this->command->info('');
        $this->command->info("✅ Selesai! Total {$total} baris berhasil diimpor ke health_records.");
        $this->command->info('   Jalankan: python python-ml-service/train_from_db.py untuk melatih model.');
    }

    // ─── Heart Disease (heart.csv) ─────────────────────────────────────────────

    private function seedHeart(): int
    {
        $file = $this->dataDir . '/heart.csv';
        if (!file_exists($file)) {
            $this->command->warn("  [SKIP] heart.csv tidak ditemukan di: {$file}");
            return 0;
        }

        $this->command->line('');
        $this->command->line('  📂 Memproses heart.csv...');

        $rows = $this->parseCsv($file, $this->limitPerFile);
        if (empty($rows)) return 0;

        $batch = [];
        foreach ($rows as $r) {
            // Konversi gender: heart.csv sex=1 (male) → kita: 1 (male)
            $gender = isset($r['sex']) ? (int) $r['sex'] : 1;

            // BMI tidak tersedia di heart dataset → estimasi dari usia
            $bmi = $this->estimateBmi(
                (float) ($r['age'] ?? 50),
                $gender
            );

            // Glukosa: fbs=1 berarti gula darah puasa >120 mg/dL
            $glucose = isset($r['fbs']) && (int)$r['fbs'] === 1 ? 130.0 : 95.0;

            // Kolesterol (chol) dan heart_rate (thalach) tersedia langsung
            $cholesterol = isset($r['chol']) ? max(100.0, min(600.0, (float)$r['chol'])) : 200.0;
            $heartRate   = isset($r['thalach']) ? max(40, min(250, (int)$r['thalach'])) : 75;

            // Label: target=1 → penyakit jantung ada
            $heartRisk = isset($r['target']) ? (float)$r['target'] : 0.0;

            // Estimasi risiko lain berdasarkan faktor yang tersedia
            $bp = isset($r['trestbps']) ? (float)$r['trestbps'] : 120.0;

            $batch[] = $this->buildRecord([
                'age'             => (float) ($r['age'] ?? 50),
                'gender'          => $gender,
                'bmi'             => $bmi,
                'glucose'         => $glucose,
                'blood_pressure'  => $bp,
                'cholesterol'     => $cholesterol,
                'heart_rate'      => $heartRate,
                'heart_disease_risk' => $heartRisk,
                'hypertension_risk'  => $this->hypertensionRisk($bp, $bmi, (float)($r['age'] ?? 50)),
                'diabetes_risk'      => $this->diabetesRisk($glucose, $bmi, (float)($r['age'] ?? 50)),
                'stroke_risk'        => $this->strokeRisk((float)($r['age'] ?? 50), $bp, $glucose),
                'ckd_risk'           => $this->ckdRisk($bp, $glucose, (float)($r['age'] ?? 50)),
                'highest_risk_disease' => null, // dihitung di bawah
            ]);
        }

        $count = $this->insertBatch($batch);
        $this->command->line("  ✅ {$count} baris dari heart.csv diimpor");
        return $count;
    }

    // ─── Diabetes (diabetes.csv) ───────────────────────────────────────────────

    private function seedDiabetes(): int
    {
        $file = $this->dataDir . '/diabetes.csv';
        if (!file_exists($file)) {
            $this->command->warn("  [SKIP] diabetes.csv tidak ditemukan di: {$file}");
            return 0;
        }

        $this->command->line('');
        $this->command->line('  📂 Memproses diabetes.csv (Pima Indians)...');

        $rows = $this->parseCsv($file, $this->limitPerFile);
        if (empty($rows)) return 0;

        $batch = [];
        foreach ($rows as $r) {
            $glucose = (float) ($r['Glucose'] ?? 100.0);
            $bmi     = (float) ($r['BMI']     ?? 22.0);
            $bp      = (float) ($r['BloodPressure'] ?? 80.0);  // diastolic di Pima
            $age     = (float) ($r['Age'] ?? 40.0);

            // Pima Indians: semua perempuan
            $gender = 0;

            // Sistolik ≈ diastolik + 40 (estimasi kasar)
            $systolic = min(200.0, $bp + 40.0);

            $diabetesRisk = isset($r['Outcome']) ? (float)$r['Outcome'] : 0.0;

            $batch[] = $this->buildRecord([
                'age'             => $age,
                'gender'          => $gender,
                'bmi'             => $bmi,
                'glucose'         => $glucose,
                'blood_pressure'  => $systolic,
                'cholesterol'     => 200.0,
                'heart_rate'      => 75,
                'diabetes_risk'       => $diabetesRisk,
                'hypertension_risk'   => $this->hypertensionRisk($systolic, $bmi, $age),
                'heart_disease_risk'  => $this->heartRisk($age, $systolic, (float)($r['chol'] ?? 200), $glucose),
                'stroke_risk'         => $this->strokeRisk($age, $systolic, $glucose),
                'ckd_risk'            => $this->ckdRisk($systolic, $glucose, $age),
            ]);
        }

        $count = $this->insertBatch($batch);
        $this->command->line("  ✅ {$count} baris dari diabetes.csv diimpor");
        return $count;
    }

    // ─── Stroke (healthcare-dataset-stroke-data.csv) ───────────────────────────

    private function seedStroke(): int
    {
        $file = $this->dataDir . '/healthcare-dataset-stroke-data.csv';
        if (!file_exists($file)) {
            $this->command->warn("  [SKIP] healthcare-dataset-stroke-data.csv tidak ditemukan");
            return 0;
        }

        $this->command->line('');
        $this->command->line('  📂 Memproses healthcare-dataset-stroke-data.csv...');

        $rows = $this->parseCsv($file, $this->limitPerFile);
        if (empty($rows)) return 0;

        $batch = [];
        foreach ($rows as $r) {
            $age     = (float) ($r['age'] ?? 40.0);
            $glucose = (float) ($r['avg_glucose_level'] ?? 100.0);
            $bmi     = isset($r['bmi']) && $r['bmi'] !== 'N/A' ? (float)$r['bmi'] : $this->estimateBmi($age, 1);
            $gender  = strtolower($r['gender'] ?? 'male') === 'male' ? 1 : 0;

            // Stroke dataset tidak punya blood_pressure langsung → estimasi
            $bp = $this->estimateBP($age, $glucose, $bmi);

            $strokeRisk = isset($r['stroke']) ? (float)$r['stroke'] : 0.0;

            $batch[] = $this->buildRecord([
                'age'             => $age,
                'gender'          => $gender,
                'bmi'             => $bmi,
                'glucose'         => $glucose,
                'blood_pressure'  => $bp,
                'cholesterol'     => 200.0,
                'heart_rate'      => 75,
                'stroke_risk'         => $strokeRisk,
                'diabetes_risk'       => $this->diabetesRisk($glucose, $bmi, $age),
                'hypertension_risk'   => $this->hypertensionRisk($bp, $bmi, $age),
                'heart_disease_risk'  => $this->heartRisk($age, $bp, 200.0, $glucose),
                'ckd_risk'            => $this->ckdRisk($bp, $glucose, $age),
            ]);
        }

        $count = $this->insertBatch($batch);
        $this->command->line("  ✅ {$count} baris dari stroke CSV diimpor");
        return $count;
    }

    // ─── Hypertension (hypertension_dataset.csv) ───────────────────────────────

    private function seedHypertension(): int
    {
        $file = $this->dataDir . '/hypertension_dataset.csv';
        if (!file_exists($file)) {
            $this->command->warn("  [SKIP] hypertension_dataset.csv tidak ditemukan");
            return 0;
        }

        $this->command->line('');
        $this->command->line('  📂 Memproses hypertension_dataset.csv...');

        // File ini besar, limit lebih ketat
        $rows = $this->parseCsv($file, min($this->limitPerFile, 3000));
        if (empty($rows)) return 0;

        $batch = [];
        foreach ($rows as $r) {
            $age     = (float) ($r['Age'] ?? 40.0);
            $systolic = (float) ($r['Systolic_BP']  ?? 120.0);
            $glucose  = (float) ($r['Glucose']      ?? 100.0);
            $bmi      = (float) ($r['BMI']          ?? 22.0);

            // Konversi label: kolom 'Hypertension'
            $hyperRisk = isset($r['Hypertension']) ? (float)$r['Hypertension'] : 0.0;

            // Gender tidak ada → asumsikan 50/50 berdasarkan id
            $gender = 1; // default laki-laki

            $batch[] = $this->buildRecord([
                'age'             => $age,
                'gender'          => $gender,
                'bmi'             => $bmi,
                'glucose'         => $glucose,
                'blood_pressure'  => $systolic,
                'cholesterol'     => 200.0,
                'heart_rate'      => 75,
                'hypertension_risk'   => $hyperRisk,
                'diabetes_risk'       => $this->diabetesRisk($glucose, $bmi, $age),
                'heart_disease_risk'  => $this->heartRisk($age, $systolic, 200.0, $glucose),
                'stroke_risk'         => $this->strokeRisk($age, $systolic, $glucose),
                'ckd_risk'            => $this->ckdRisk($systolic, $glucose, $age),
            ]);
        }

        $count = $this->insertBatch($batch);
        $this->command->line("  ✅ {$count} baris dari hypertension CSV diimpor");
        return $count;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /** Parse CSV dan return array of associative arrays */
    private function parseCsv(string $filePath, int $limit = PHP_INT_MAX): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->command->error("  Tidak dapat membuka: {$filePath}");
            return [];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Trim BOM dan whitespace dari header
        $headers = array_map(fn($h) => trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);

        $rows = [];
        $count = 0;
        while (($data = fgetcsv($handle)) !== false && $count < $limit) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
                $count++;
            }
        }

        fclose($handle);
        $this->command->line("    → Dibaca {$count} baris (limit: {$limit})");
        return $rows;
    }

    /** Buat array record siap insert dengan nilai default */
    private function buildRecord(array $data): array
    {
        $risks = [
            'heart_disease' => $data['heart_disease_risk'] ?? 0.0,
            'diabetes'      => $data['diabetes_risk']      ?? 0.0,
            'hypertension'  => $data['hypertension_risk']  ?? 0.0,
            'stroke'        => $data['stroke_risk']        ?? 0.0,
            'ckd'           => $data['ckd_risk']           ?? 0.0,
        ];
        arsort($risks);
        $highestKey = array_key_first($risks);

        return [
            'user_id'              => null,
            'age'                  => max(0, min(120, (int) ($data['age'] ?? 40))),
            'gender'               => (int) ($data['gender'] ?? 1),
            'bmi'                  => round(max(10.0, min(70.0, (float)($data['bmi'] ?? 22.0))), 1),
            'glucose'              => round(max(30.0, min(600.0, (float)($data['glucose'] ?? 100.0))), 1),
            'blood_pressure'       => max(40, min(250, (int)($data['blood_pressure'] ?? 120))),
            'cholesterol'          => round(max(50.0, min(600.0, (float)($data['cholesterol'] ?? 200.0))), 1),
            'heart_rate'           => max(20, min(250, (int)($data['heart_rate'] ?? 75))),
            'diabetes_risk'        => round((float)($data['diabetes_risk']      ?? 0.0), 4),
            'hypertension_risk'    => round((float)($data['hypertension_risk']  ?? 0.0), 4),
            'heart_disease_risk'   => round((float)($data['heart_disease_risk'] ?? 0.0), 4),
            'stroke_risk'          => round((float)($data['stroke_risk']        ?? 0.0), 4),
            'ckd_risk'             => round((float)($data['ckd_risk']           ?? 0.0), 4),
            'highest_risk_disease' => $highestKey,
            'model_mode'           => 'dataset_seed',
            'llm_advice'           => null,
            'llm_prompt'           => null,
            'llm_model'            => null,
            'status'               => 'processed',
            'ip_address'           => null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }

    /** Insert batch ke DB, return jumlah yang berhasil */
    private function insertBatch(array $batch, int $chunkSize = 500): int
    {
        if (empty($batch)) return 0;
        $count = 0;
        foreach (array_chunk($batch, $chunkSize) as $chunk) {
            DB::table('health_records')->insert($chunk);
            $count += count($chunk);
        }
        return $count;
    }

    // ─── Threshold-based label generators ─────────────────────────────────────

    /** Estimasi risiko diabetes dari glukosa, BMI, usia */
    private function diabetesRisk(float $glucose, float $bmi, float $age): float
    {
        // Threshold klinis ADA: gula puasa ≥126 → diabetes
        $score = 0.0;
        if ($glucose >= 126) $score += 0.55;
        elseif ($glucose >= 100) $score += 0.25;
        if ($bmi >= 30)  $score += 0.25;
        elseif ($bmi >= 25) $score += 0.10;
        if ($age >= 45)  $score += 0.15;
        elseif ($age >= 35) $score += 0.05;
        return round(min(1.0, $score), 4);
    }

    /** Estimasi risiko hipertensi dari tekanan darah, BMI, usia */
    private function hypertensionRisk(float $bp, float $bmi, float $age): float
    {
        // ACC/AHA: ≥130 sistolik = Hipertensi Tahap 1
        $score = 0.0;
        if ($bp >= 140) $score += 0.55;
        elseif ($bp >= 130) $score += 0.30;
        elseif ($bp >= 120) $score += 0.10;
        if ($bmi >= 30)  $score += 0.20;
        elseif ($bmi >= 25) $score += 0.10;
        if ($age >= 60)  $score += 0.15;
        elseif ($age >= 40) $score += 0.08;
        return round(min(1.0, $score), 4);
    }

    /** Estimasi risiko penyakit jantung */
    private function heartRisk(float $age, float $bp, float $chol, float $glucose): float
    {
        $score = 0.0;
        if ($age >= 65)  $score += 0.25;
        elseif ($age >= 50) $score += 0.12;
        if ($bp >= 140) $score += 0.25;
        elseif ($bp >= 130) $score += 0.12;
        if ($chol >= 240) $score += 0.20;
        elseif ($chol >= 200) $score += 0.10;
        if ($glucose >= 126) $score += 0.15;
        return round(min(1.0, $score), 4);
    }

    /** Estimasi risiko stroke */
    private function strokeRisk(float $age, float $bp, float $glucose): float
    {
        $score = 0.0;
        if ($age >= 65)  $score += 0.35;
        elseif ($age >= 55) $score += 0.20;
        elseif ($age >= 45) $score += 0.10;
        if ($bp >= 140) $score += 0.30;
        elseif ($bp >= 130) $score += 0.15;
        if ($glucose >= 126) $score += 0.15;
        return round(min(1.0, $score), 4);
    }

    /** Estimasi risiko CKD */
    private function ckdRisk(float $bp, float $glucose, float $age): float
    {
        $score = 0.0;
        if ($bp >= 140) $score += 0.30;
        elseif ($bp >= 130) $score += 0.15;
        if ($glucose >= 126) $score += 0.30;
        elseif ($glucose >= 100) $score += 0.10;
        if ($age >= 65)  $score += 0.25;
        elseif ($age >= 50) $score += 0.12;
        return round(min(1.0, $score), 4);
    }

    /** Estimasi BMI jika tidak tersedia di dataset */
    private function estimateBmi(float $age, int $gender): float
    {
        // Rata-rata populasi
        $base = $gender === 1 ? 26.5 : 25.8;
        $ageAdj = $age > 50 ? 1.5 : ($age > 35 ? 0.5 : 0.0);
        return round($base + $ageAdj + (mt_rand(-20, 20) / 10), 1);
    }

    /** Estimasi tekanan darah sistolik jika tidak tersedia */
    private function estimateBP(float $age, float $glucose, float $bmi): float
    {
        $base = 110.0;
        if ($age > 60) $base += 20;
        elseif ($age > 40) $base += 10;
        if ($glucose > 126) $base += 15;
        if ($bmi > 30) $base += 10;
        return round(min(200.0, $base), 0);
    }
}
