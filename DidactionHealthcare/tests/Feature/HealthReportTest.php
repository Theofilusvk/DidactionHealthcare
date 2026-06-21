<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthReportTest extends TestCase
{
    /**
     * Test predicting and exporting PDF.
     */
    public function test_prediction_stores_in_session_and_exports_pdf(): void
    {
        // Disable CSRF for testing JSON API endpoint
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // 1. Test POST /api/predict validation error
        $response = $this->postJson('/api/predict', []);
        $response->assertStatus(422);

        // 2. Test POST /api/predict successful submission (calls localFallback if FastAPI offline)
        $payload = [
            'age'             => 45,
            'gender'          => 1,
            'glucose'         => 150.0,
            'blood_pressure'  => 140,
            'bmi'             => 28.5,
            'aktivitas_fisik' => 'sedentary',
            'status_merokok'  => 'never',
            'protein_urine'   => 120.0,
            'hba1c'           => 6.2,
        ];

        $response = $this->postJson('/api/predict', $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'model_mode',
                'predictions',
                'highest_risk',
                'summary',
                'action_plans',
            ]
        ]);

        // Assert that analysis_result is stored in session
        $this->assertTrue(session()->has('analysis_result'));
        $result = session('analysis_result');
        $this->assertEquals(45, $result['input_data']['age']);
        $this->assertNotEmpty($result['predictions']);
        $this->assertNotEmpty($result['meal_plan']);
        $this->assertNotEmpty($result['activity_plan']);

        // 3. Test GET /export-pdf downloads a PDF file
        $pdfResponse = $this->get('/export-pdf');
        $pdfResponse->assertStatus(200);
        
        // Assert header for file download
        $pdfResponse->assertHeader('Content-Disposition');
        $contentDisposition = $pdfResponse->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment; filename=Laporan-Kesehatan-Didaction-', $contentDisposition);
    }

    /**
     * Test that export-pdf redirects back when session is empty.
     */
    public function test_export_pdf_redirects_when_session_empty(): void
    {
        // Clear session first
        session()->forget('analysis_result');

        $response = $this->get('/export-pdf');
        $response->assertRedirect('/get-started');
        $response->assertSessionHas('error');
    }
}
