<?php

/**
 * TEST SCRIPT: Gemini API Connection Diagnostic
 * 
 * Run: php test_gemini_connection.php
 * 
 * This script tests the connection to Google Gemini API and identifies
 * why the AI recommendations are showing as "tidak tersedia" (unavailable)
 */

require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  GEMINI API CONNECTION TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─── Check Configuration ─────────────────────────────────────────────
echo "1️⃣  CHECKING CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────\n";

$apiKey = env('GEMINI_API_KEY');
$model  = env('GEMINI_MODEL', 'gemini-1.5-flash');

if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY is not set in .env\n";
    echo "   → Add: GEMINI_API_KEY=your_actual_key\n";
    exit(1);
}

echo "✅ GEMINI_API_KEY is set\n";
echo "   Key (first 20 chars): " . substr($apiKey, 0, 20) . "...\n";
echo "✅ GEMINI_MODEL: $model\n\n";

// ─── Test API Connection ────────────────────────────────────────────
echo "2️⃣  TESTING API CONNECTION\n";
echo "─────────────────────────────────────────────────────────────\n";

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

echo "Endpoint: " . preg_replace("/key=.+$/", "key=***", $endpoint) . "\n\n";

$testPayload = [
    'system_instruction' => [
        'parts' => [
            ['text' => 'You are a helpful AI assistant. Respond in JSON format.']
        ]
    ],
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => 'Test message. Respond with {"status": "ok"}']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature'      => 0.7,
        'maxOutputTokens'  => 256,
        'topP'             => 0.95,
        'responseMimeType' => 'application/json'
    ]
];

try {
    echo "Sending test request...\n";
    
    $response = Http::timeout(30)
        ->acceptJson()
        ->asJson()
        ->post($endpoint, $testPayload);
    
    echo "Status Code: {$response->status()}\n";
    
    if ($response->successful()) {
        echo "✅ SUCCESS! API is responding\n\n";
        
        $data = $response->json();
        
        // Show response structure
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo "📝 Response from Gemini:\n";
            echo "   " . substr($data['candidates'][0]['content']['parts'][0]['text'], 0, 100) . "...\n\n";
        }
        
        // Show token usage
        if (isset($data['usageMetadata'])) {
            echo "📊 Token Usage:\n";
            echo "   Prompt tokens: " . ($data['usageMetadata']['promptTokenCount'] ?? 0) . "\n";
            echo "   Response tokens: " . ($data['usageMetadata']['candidatesTokenCount'] ?? 0) . "\n\n";
        }
    } else {
        echo "❌ API ERROR!\n";
        echo "Status: {$response->status()}\n";
        echo "Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        
        // Provide specific error advice
        $error = $response->json();
        if (isset($error['error'])) {
            echo "💡 ERROR DETAILS:\n";
            echo "   Code: " . ($error['error']['code'] ?? 'Unknown') . "\n";
            echo "   Message: " . ($error['error']['message'] ?? 'Unknown') . "\n\n";
            
            if (strpos($error['error']['message'] ?? '', 'API key') !== false) {
                echo "🔐 FIX: Your API key might be invalid. Check:\n";
                echo "   1. Is the key correct?\n";
                echo "   2. Is the key enabled on Google Cloud?\n";
                echo "   3. Is the Generative AI API enabled in your project?\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ CONNECTION ERROR!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "💡 POSSIBLE CAUSES:\n";
    echo "   • No internet connection\n";
    echo "   • Firewall blocking requests\n";
    echo "   • Invalid API key format\n";
    echo "   • API not enabled in Google Cloud\n\n";
}

// ─── Test with Real Health Data ──────────────────────────────────────
echo "3️⃣  TESTING WITH HEALTH DATA\n";
echo "─────────────────────────────────────────────────────────────\n";

$healthPayload = [
    'system_instruction' => [
        'parts' => [
            ['text' => 'Anda adalah asisten kesehatan AI. Berikan 2 rekomendasi kesehatan dalam format JSON. Respons HARUS berupa JSON valid tanpa teks tambahan.']
        ]
    ],
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => <<<PROMPT
## Data Pasien
Usia: 45 tahun
BMI: 28.5 (Overweight)
Glukosa: 145 mg/dL
Tekanan Darah: 145 mmHg

## Hasil Prediksi Risiko
- Hipertension: 65% (Tinggi)
- Heart Disease: 72% (Tinggi)
- Diabetes: 38% (Sedang)

Berikan 2 rekomendasi kesehatan actionable dalam format:
{"recommendations": [{"title": "...", "description": "...", "priority": "Tinggi|Sedang"}]}
PROMPT
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature'      => 0.7,
        'maxOutputTokens'  => 512,
        'topP'             => 0.95,
        'responseMimeType' => 'application/json'
    ]
];

try {
    echo "Sending health data test...\n";
    
    $response = Http::timeout(30)
        ->acceptJson()
        ->asJson()
        ->post($endpoint, $healthPayload);
    
    if ($response->successful()) {
        echo "✅ SUCCESS! Health test passed\n";
        $data = $response->json();
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo "📋 Recommendations:\n";
            echo json_decode($data['candidates'][0]['content']['parts'][0]['text'], true) ? 
                json_encode(json_decode($data['candidates'][0]['content']['parts'][0]['text']), JSON_PRETTY_PRINT) :
                $data['candidates'][0]['content']['parts'][0]['text'];
            echo "\n\n";
        }
    } else {
        echo "❌ Health test failed\n";
        echo "Status: {$response->status()}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ─── Final Summary ──────────────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n✅ If all tests passed:\n";
echo "   The issue is likely in the Laravel controller/service code.\n";
echo "   Check AgenticAiService.php for the callLlmApi() method.\n\n";
echo "❌ If tests failed:\n";
echo "   1. Verify GEMINI_API_KEY in .env\n";
echo "   2. Check Google Cloud Console project settings\n";
echo "   3. Ensure Generative AI API is enabled\n";
echo "   4. Verify the API key has correct permissions\n\n";
