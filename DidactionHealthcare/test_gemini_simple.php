#!/usr/bin/env php
<?php

/**
 * SIMPLE GEMINI API TEST - No Laravel dependencies
 * 
 * Usage: php test_gemini_simple.php
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "  GEMINI API CONNECTION TEST (Simple cURL)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Load .env manually
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ Error: .env file not found at $envFile\n";
    exit(1);
}

// Parse .env file (better than parse_ini_file)
$env = [];
$lines = file($envFile);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $env[$key] = $value;
}

$apiKey = $env['GEMINI_API_KEY'] ?? null;
$model = $env['GEMINI_MODEL'] ?? 'gemini-1.5-flash';

if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY is empty in .env\n";
    exit(1);
}

echo "✅ Found API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "✅ Model: $model\n\n";

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// ─── Test 1: Simple API Test ────────────────────────────────────────
echo "1️⃣  SIMPLE API TEST\n";
echo "─────────────────────────────────────────────────────────────\n";

$payload = [
    'system_instruction' => [
        'parts' => [['text' => 'You are a helpful assistant. Respond in JSON format.']]
    ],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => 'Test message. Respond with {"status": "ok"}']]]
    ],
    'generationConfig' => [
        'temperature'      => 0.7,
        'maxOutputTokens'  => 256,
        'topP'             => 0.95,
        'responseMimeType' => 'application/json'
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

echo "Sending request to Gemini API...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($error) {
    echo "❌ cURL Error: $error\n\n";
} elseif ($httpCode === 200) {
    echo "✅ SUCCESS! Gemini API is responding\n\n";
    
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Response is valid JSON\n\n";
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "📝 Gemini Response:\n";
            echo "   " . substr($text, 0, 150) . "\n\n";
        }
        
        if (isset($data['usageMetadata'])) {
            echo "📊 Token Usage:\n";
            echo "   Input: " . ($data['usageMetadata']['promptTokenCount'] ?? 0) . " tokens\n";
            echo "   Output: " . ($data['usageMetadata']['candidatesTokenCount'] ?? 0) . " tokens\n\n";
        }
    } else {
        echo "❌ Response is not valid JSON\n";
        echo "Response: " . substr($response, 0, 200) . "\n\n";
    }
} else {
    echo "❌ API Error (HTTP $httpCode)\n\n";
    
    $data = json_decode($response, true);
    
    if (isset($data['error'])) {
        echo "Error Details:\n";
        echo "  Code: " . ($data['error']['code'] ?? 'Unknown') . "\n";
        echo "  Message: " . ($data['error']['message'] ?? 'Unknown') . "\n\n";
        
        $msg = $data['error']['message'] ?? '';
        
        if (strpos($msg, 'API key') !== false || strpos($msg, 'not valid') !== false) {
            echo "💡 FIX: Invalid or disabled API key\n";
            echo "   1. Go to https://console.cloud.google.com\n";
            echo "   2. Check if API key is valid\n";
            echo "   3. Enable 'Generative AI API' in project\n";
            echo "   4. Check API key has no restrictions\n";
            echo "   5. Regenerate if needed\n\n";
        } elseif (strpos($msg, 'permission') !== false || strpos($msg, 'not enabled') !== false) {
            echo "💡 FIX: API not enabled in project\n";
            echo "   1. Go to https://console.cloud.google.com\n";
            echo "   2. Search for 'Generative AI API'\n";
            echo "   3. Click 'Enable'\n";
            echo "   4. Wait 1-2 minutes\n";
            echo "   5. Try again\n\n";
        } else {
            echo "💡 Raw response:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "Raw response:\n";
        echo substr($response, 0, 500) . "\n\n";
    }
}

// ─── Test 2: Health Data Test ──────────────────────────────────────
echo "2️⃣  HEALTH DATA TEST\n";
echo "─────────────────────────────────────────────────────────────\n";

$healthPayload = [
    'system_instruction' => [
        'parts' => [['text' => 'Anda adalah konsultan kesehatan. Berikan 1 rekomendasi dalam JSON.']]
    ],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => 'Pasien 45 tahun, BMI 28.5, Glukosa 145. Berikan rekomendasi dalam JSON format.']]]
    ],
    'generationConfig' => [
        'temperature'      => 0.7,
        'maxOutputTokens'  => 512,
        'topP'             => 0.95,
        'responseMimeType' => 'application/json'
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($healthPayload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Health data test passed\n\n";
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "Response:\n";
        echo json_encode(json_decode($data['candidates'][0]['content']['parts'][0]['text']), JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Health data test failed (HTTP $httpCode)\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($httpCode === 200) {
    echo "✅ GEMINI API IS WORKING!\n";
    echo "   The issue is likely in the Laravel code.\n";
    echo "   Check: DidactionHealthcare/app/Services/AgenticAiService.php\n\n";
} else {
    echo "❌ GEMINI API IS NOT RESPONDING\n";
    echo "   Fix the API key or project settings first.\n\n";
}
