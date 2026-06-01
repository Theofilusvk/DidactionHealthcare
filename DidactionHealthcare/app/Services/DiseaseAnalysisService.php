<?php

namespace App\Services;

use App\Models\HealthRecord;
use Illuminate\Support\Facades\Log;

/**
 * DiseaseAnalysisService
 *
 * Menganalisis kombinasi gejala pasien dan memprediksi kemungkinan penyakit
 * berdasarkan pola data historis dari health_records.
 */
class DiseaseAnalysisService
{
    /**
     * Fitur yang dianalisis dari dataset.
     * Format: ['nama_fitur' => 'kolom_database']
     */
    private array $features = [
        'age' => 'age',
        'gender' => 'gender',
        'glucose' => 'glucose',
        'blood_pressure' => 'blood_pressure',
        'bmi' => 'bmi',
    ];

    /**
     * Mapping penyakit dengan kolom risk di database.
     */
    private array $diseases = [
        'diabetes' => 'diabetes_risk',
        'hipertensi' => 'hypertension_risk',
        'penyakit_jantung' => 'heart_disease_risk',
        'stroke' => 'stroke_risk',
        'penyakit_ginjal_kronis' => 'ckd_risk',
    ];

    /**
     * Label penyakit yang user-friendly.
     */
    private array $diseaseLabels = [
        'diabetes' => 'Diabetes Mellitus',
        'hipertensi' => 'Hipertensi (Tekanan Darah Tinggi)',
        'penyakit_jantung' => 'Penyakit Jantung Koroner',
        'stroke' => 'Stroke',
        'penyakit_ginjal_kronis' => 'Penyakit Ginjal Kronis (CKD)',
    ];

    /**
     * Prediksi penyakit berdasarkan gejala/fitur pasien.
     *
     * Input format:
     * [
     *     'age' => 45,
     *     'gender' => 1,           // 0 = Perempuan, 1 = Laki-laki
     *     'glucose' => 150,
     *     'blood_pressure' => 140,
     *     'bmi' => 28.5,
     * ]
     */
    public function predictDiseases(array $symptoms): array
    {
        try {
            // Validasi input
            if (!$this->validateInput($symptoms)) {
                return [
                    'status' => 'error',
                    'message' => 'Gejala tidak lengkap untuk prediksi akurat',
                    'predictions' => []
                ];
            }

            // Query database untuk temukan similar records
            $similarRecords = $this->findSimilarRecords($symptoms);

            if (empty($similarRecords)) {
                return [
                    'status' => 'no_match',
                    'message' => 'Gejala tidak cocok dengan pola manapun di dataset',
                    'predictions' => []
                ];
            }

            // Hitung confidence untuk setiap penyakit
            $predictions = $this->calculateConfidence($similarRecords);

            // Urutkan by confidence (descending) dan ambil top 3
            $topPredictions = array_slice($predictions, 0, 3);

            return [
                'status' => 'success',
                'message' => 'Prediksi berhasil',
                'input_symptoms' => $symptoms,
                'predictions' => $topPredictions,
                'matched_records' => count($similarRecords),
            ];
        } catch (\Exception $e) {
            Log::error('DiseaseAnalysisService prediction error', [
                'symptoms' => $symptoms,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat prediksi',
                'predictions' => []
            ];
        }
    }

    /**
     * Validasi input gejala.
     */
    private function validateInput(array $symptoms): bool
    {
        $required = ['age', 'gender', 'glucose', 'blood_pressure', 'bmi'];
        
        foreach ($required as $field) {
            if (!isset($symptoms[$field]) || $symptoms[$field] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Cari records yang mirip dengan gejala pasien.
     * Menggunakan threshold untuk tolerance range.
     */
    private function findSimilarRecords(array $symptoms): array
    {
        $tolerance = [
            'age' => 10,              // ±10 tahun
            'glucose' => 30,          // ±30 mg/dL
            'blood_pressure' => 15,   // ±15 mmHg
            'bmi' => 3,               // ±3 kg/m²
        ];

        $query = HealthRecord::query();

        // Filter by age
        $query->whereBetween('age', [
            max(0, $symptoms['age'] - $tolerance['age']),
            $symptoms['age'] + $tolerance['age']
        ]);

        // Filter by gender
        $query->where('gender', $symptoms['gender']);

        // Filter by glucose
        $query->whereBetween('glucose', [
            max(0, $symptoms['glucose'] - $tolerance['glucose']),
            $symptoms['glucose'] + $tolerance['glucose']
        ]);

        // Filter by blood pressure
        $query->whereBetween('blood_pressure', [
            max(0, $symptoms['blood_pressure'] - $tolerance['blood_pressure']),
            $symptoms['blood_pressure'] + $tolerance['blood_pressure']
        ]);

        // Filter by BMI
        $query->whereBetween('bmi', [
            max(0, $symptoms['bmi'] - $tolerance['bmi']),
            $symptoms['bmi'] + $tolerance['bmi']
        ]);

        return $query->limit(1000)->get()->toArray();
    }

    /**
     * Hitung confidence untuk setiap penyakit berdasarkan risk scores.
     */
    private function calculateConfidence(array $records): array
    {
        $diseaseScores = [];

        // Initialize scores
        foreach ($this->diseases as $key => $column) {
            $diseaseScores[$key] = [
                'name' => $this->diseaseLabels[$key],
                'risk_scores' => [],
                'count' => 0,
            ];
        }

        // Aggregate risk scores
        foreach ($records as $record) {
            foreach ($this->diseases as $key => $column) {
                if (isset($record[$column]) && $record[$column] !== null) {
                    $diseaseScores[$key]['risk_scores'][] = (float)$record[$column];
                    $diseaseScores[$key]['count']++;
                }
            }
        }

        // Hitung average dan confidence percentage
        $predictions = [];

        foreach ($diseaseScores as $key => $data) {
            if (empty($data['risk_scores'])) {
                continue;
            }

            $avgRisk = array_sum($data['risk_scores']) / count($data['risk_scores']);
            
            // Convert risk score (0-1) ke confidence percentage (0-100)
            // Risk score 0.85+ = high confidence
            // Risk score 0.5-0.85 = moderate confidence
            // Risk score < 0.5 = low confidence
            $confidence = (int)round($avgRisk * 100);

            $predictions[] = [
                'disease' => $key,
                'name' => $data['name'],
                'confidence' => $confidence,
                'avg_risk' => round($avgRisk, 4),
                'sample_size' => $data['count'],
            ];
        }

        // Sort by confidence (highest first)
        usort($predictions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $predictions;
    }

    /**
     * Get disease explanation berdasarkan symptoms.
     */
    public function getExplanation(string $disease, array $symptoms): string
    {
        $explanations = [
            'diabetes' => "Kadar glukosa darah {$symptoms['glucose']} mg/dL termasuk tinggi, yang merupakan indikator utama diabetes. Kombinasi dengan BMI {$symptoms['bmi']} dan usia {$symptoms['age']} tahun meningkatkan risiko.",
            'hipertensi' => "Tekanan darah sistolik {$symptoms['blood_pressure']} mmHg termasuk tinggi (normal: <120). Kondisi ini merupakan ciri utama hipertensi, terutama pada usia {$symptoms['age']} tahun.",
            'penyakit_jantung' => "Kombinasi tekanan darah {$symptoms['blood_pressure']}, glucose {$symptoms['glucose']}, dan BMI {$symptoms['bmi']} menunjukkan faktor risiko cardiovascular yang signifikan.",
            'stroke' => "Tekanan darah tinggi ({$symptoms['blood_pressure']} mmHg) dan glucose tinggi ({$symptoms['glucose']}) adalah faktor risiko stroke utama, terutama pada usia {$symptoms['age']} tahun.",
            'penyakit_ginjal_kronis' => "Tekanan darah tinggi ({$symptoms['blood_pressure']}) dan glucose tinggi ({$symptoms['glucose']}) dapat merusak fungsi ginjal dari waktu ke waktu.",
        ];

        return $explanations[$disease] ?? 'Kondisi memerlukan evaluasi lebih lanjut.';
    }
}
