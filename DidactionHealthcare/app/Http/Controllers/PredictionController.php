<?php

namespace App\Http\Controllers;

use App\Services\DiseaseAnalysisService;
use App\Services\HealthAdviceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PredictionController extends Controller
{
    protected DiseaseAnalysisService $analysisService;
    protected HealthAdviceService $adviceService;

    public function __construct(
        DiseaseAnalysisService $analysisService,
        HealthAdviceService $adviceService
    ) {
        $this->analysisService = $analysisService;
        $this->adviceService = $adviceService;
    }

    /**
     * Predict disease berdasarkan symptoms yang diinput.
     *
     * Request format (JSON):
     * {
     *     "age": 45,
     *     "gender": 1,
     *     "glucose": 150,
     *     "blood_pressure": 140,
     *     "bmi": 28.5
     * }
     */
    public function predict(Request $request): JsonResponse
    {
        // Validasi input
        $validated = $request->validate([
            'age' => 'required|integer|min:0|max:120',
            'gender' => 'required|integer|in:0,1',
            'glucose' => 'required|numeric|min:0|max:500',
            'blood_pressure' => 'required|integer|min:0|max:300',
            'bmi' => 'required|numeric|min:5|max:100',
        ], [
            'age.required' => 'Usia harus diisi',
            'age.integer' => 'Usia harus berupa angka',
            'glucose.required' => 'Kadar glukosa harus diisi',
            'blood_pressure.required' => 'Tekanan darah harus diisi',
            'bmi.required' => 'BMI harus diisi',
        ]);

        // Prediksi penyakit
        $predictionResult = $this->analysisService->predictDiseases($validated);

        if ($predictionResult['status'] !== 'success') {
            return response()->json([
                'status' => $predictionResult['status'],
                'message' => $predictionResult['message'],
                'data' => null,
            ], 200);
        }

        // Generate full advice response
        $fullResponse = $this->adviceService->generateFullResponse(
            $predictionResult['predictions'],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Prediksi berhasil',
            'data' => $fullResponse,
            'metadata' => [
                'matched_records' => $predictionResult['matched_records'] ?? 0,
                'timestamp' => now()->toIso8601String(),
            ]
        ], 200);
    }

    /**
     * Get example format untuk testing.
     */
    public function example(): JsonResponse
    {
        return response()->json([
            'message' => 'Contoh request untuk predict endpoint',
            'example_request' => [
                'age' => 45,
                'gender' => 1,  // 0 = Perempuan, 1 = Laki-laki
                'glucose' => 150,  // mg/dL
                'blood_pressure' => 140,  // mmHg
                'bmi' => 28.5  // kg/m²
            ],
            'endpoint' => 'POST /api/predict',
            'description' => 'Mengirim data gejala pasien untuk prediksi penyakit. Response akan berisi top 3 kemungkinan penyakit beserta saran pola makan, olahraga, dan peringatan medis.'
        ]);
    }
}
