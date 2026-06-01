<?php

namespace Database\Seeders;

use App\Models\HealthRecord;
use App\Services\RiskCalculator;
use Illuminate\Database\Seeder;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class HealthRecordSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk load data dari CSV files.
     * Data diload dari folder: database/../Databases/
     */
    public function run(): void
    {
        $basePath = base_path('../Databases');

        // ─── Load Diabetes Dataset ──────────────────────────────────────────
        $this->loadDiabetesData($basePath);

        // ─── Load Heart Disease Dataset ──────────────────────────────────────
        $this->loadHeartDiseaseData($basePath);

        // ─── Load Hypertension Dataset ───────────────────────────────────────
        $this->loadHypertensionData($basePath);

        // ─── Load Stroke Dataset ─────────────────────────────────────────────
        $this->loadStrokeData($basePath);

        $this->command->info('✅ Semua data CSV berhasil dimuat ke health_records!');
    }

    /**
     * Load diabetes.csv ke health_records
     * Outcome: 1 = Diabetes, 0 = Normal
     */
    private function loadDiabetesData(string $basePath): void
    {
        $filePath = "{$basePath}/diabetes.csv";
        if (!file_exists($filePath)) {
            $this->command->warn("⚠️ File tidak ditemukan: {$filePath}");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $count = 0;
        $batch = [];
        $batchSize = 50;

        foreach ($csv->getRecords() as $row) {
            if (empty($row['Glucose'])) continue;

            $age = (int)$row['Age'];
            $gender = 1; // default
            $glucose = (float)$row['Glucose'];
            $bp = (int)$row['BloodPressure'] ?: 120;
            $bmi = (float)$row['BMI'];

            // Calculate dynamic risk scores
            $risks = RiskCalculator::calculateAll($age, $gender, $glucose, $bp, $bmi);

            $batch[] = [
                'age' => $age,
                'gender' => $gender,
                'glucose' => $glucose,
                'blood_pressure' => $bp,
                'bmi' => $bmi,
                'diabetes_risk' => $risks['diabetes_risk'],
                'hypertension_risk' => $risks['hypertension_risk'],
                'heart_disease_risk' => $risks['heart_disease_risk'],
                'stroke_risk' => $risks['stroke_risk'],
                'ckd_risk' => $risks['ckd_risk'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                HealthRecord::insert($batch);
                $count += count($batch);
                $batch = [];
                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            HealthRecord::insert($batch);
            $count += count($batch);
        }

        $this->command->info("✅ Loaded {$count} diabetes records");
    }

    /**
     * Load heart.csv ke health_records
     * target: 1 = Heart Disease, 0 = Normal
     */
    private function loadHeartDiseaseData(string $basePath): void
    {
        $filePath = "{$basePath}/heart.csv";
        if (!file_exists($filePath)) {
            $this->command->warn("⚠️ File tidak ditemukan: {$filePath}");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = [];
        foreach ($csv->getRecords() as $row) {
            if (empty($row['age'])) continue;

            $age = (int)$row['age'];
            $gender = (int)$row['sex'];
            $glucose = (float)($row['chol'] ?? 100);
            $bp = (int)($row['trestbps'] ?? 120);
            $bmi = (float)($row['oldpeak'] ?? 22);

            // Calculate dynamic risk scores
            $risks = RiskCalculator::calculateAll($age, $gender, $glucose, $bp, $bmi);

            $records[] = [
                'age' => $age,
                'gender' => $gender,
                'glucose' => $glucose,
                'blood_pressure' => $bp,
                'bmi' => $bmi,
                'diabetes_risk' => $risks['diabetes_risk'],
                'hypertension_risk' => $risks['hypertension_risk'],
                'heart_disease_risk' => $risks['heart_disease_risk'],
                'stroke_risk' => $risks['stroke_risk'],
                'ckd_risk' => $risks['ckd_risk'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($records, 100) as $chunk) {
            HealthRecord::insert($chunk);
        }

        $this->command->info("✅ Loaded " . count($records) . " heart disease records");
    }

    /**
     * Load hypertension_dataset.csv ke health_records
     */
    private function loadHypertensionData(string $basePath): void
    {
        $filePath = "{$basePath}/hypertension_dataset.csv";
        if (!file_exists($filePath)) {
            $this->command->warn("⚠️ File tidak ditemukan: {$filePath}");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $count = 0;
        $batch = [];
        $batchSize = 50; // Kurangi batch size untuk menghemat memory

        foreach ($csv->getRecords() as $row) {
            if (empty($row['Age'])) continue;

            $age = (int)$row['Age'];
            $gender = $row['Gender'] == 'Male' ? 1 : 0;
            $glucose = (float)($row['Glucose'] ?? 100);
            $bp = (int)($row['Systolic_BP'] ?? 120);
            $bmi = (float)($row['BMI'] ?? 25);

            // Calculate dynamic risk scores
            $risks = RiskCalculator::calculateAll($age, $gender, $glucose, $bp, $bmi);

            $batch[] = [
                'age' => $age,
                'gender' => $gender,
                'glucose' => $glucose,
                'blood_pressure' => $bp,
                'bmi' => $bmi,
                'diabetes_risk' => $risks['diabetes_risk'],
                'hypertension_risk' => $risks['hypertension_risk'],
                'heart_disease_risk' => $risks['heart_disease_risk'],
                'stroke_risk' => $risks['stroke_risk'],
                'ckd_risk' => $risks['ckd_risk'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                HealthRecord::insert($batch);
                $count += count($batch);
                $batch = [];
                gc_collect_cycles(); // Force garbage collection
            }
        }

        // Insert sisa records
        if (!empty($batch)) {
            HealthRecord::insert($batch);
            $count += count($batch);
        }

        $this->command->info("✅ Loaded {$count} hypertension records");
    }

    /**
     * Load healthcare-dataset-stroke-data.csv ke health_records
     */
    private function loadStrokeData(string $basePath): void
    {
        $filePath = "{$basePath}/healthcare-dataset-stroke-data.csv";
        if (!file_exists($filePath)) {
            $this->command->warn("⚠️ File tidak ditemukan: {$filePath}");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $count = 0;
        $batch = [];
        $batchSize = 50;

        foreach ($csv->getRecords() as $row) {
            if (empty($row['age'])) continue;

            $age = (int)$row['age'];
            $gender = $row['gender'] == 'Male' ? 1 : 0;
            $glucose = (float)($row['avg_glucose_level'] ?? 100);
            $bp = (int)($row['avg_systolic_bp'] ?? 120); // Try to find systolic BP, default to 120
            $bmi = (float)($row['bmi'] ?? 25);

            // Calculate dynamic risk scores
            $risks = RiskCalculator::calculateAll($age, $gender, $glucose, $bp, $bmi);

            $batch[] = [
                'age' => $age,
                'gender' => $gender,
                'glucose' => $glucose,
                'blood_pressure' => $bp,
                'bmi' => $bmi,
                'diabetes_risk' => $risks['diabetes_risk'],
                'hypertension_risk' => $risks['hypertension_risk'],
                'heart_disease_risk' => $risks['heart_disease_risk'],
                'stroke_risk' => $risks['stroke_risk'],
                'ckd_risk' => $risks['ckd_risk'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                HealthRecord::insert($batch);
                $count += count($batch);
                $batch = [];
                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            HealthRecord::insert($batch);
            $count += count($batch);
        }

        $this->command->info("✅ Loaded {$count} stroke records");
    }
}
