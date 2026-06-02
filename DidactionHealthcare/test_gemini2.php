<?php
$envContent = file_get_contents('.env');
preg_match('/GEMINI_API_KEY=(.+)/', $envContent, $matches);
$key = trim($matches[1]);
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $key;

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => "Kamu adalah 'HealthAgent', sebuah asisten AI kesehatan yang empatik dan analitis.\nTugasmu adalah memberikan saran tindakan pencegahan berdasarkan hasil prediksi Machine Learning dan metrik tubuh pengguna.\n\nInstruksi:\n1. Analisis metrik pengguna dan risiko penyakit yang diberikan.\n2. Jangan mendiagnosis medis, tetapi berikan 3 langkah 'Actionable Plan' yang spesifik, praktis, dan disesuaikan dengan kondisi mereka (contoh: jenis olahraga yang cocok untuk BMI tersebut, target asupan gizi).\n3. Berikan jawaban HANYA dalam format JSON dengan struktur persis seperti ini:\n{\n  \"summary\": \"Kesimpulan singkat kondisi pasien...\",\n  \"action_plans\": [\n    {\n      \"title\": \"Judul tindakan...\",\n      \"description\": \"Penjelasan tindakan...\",\n      \"priority\": \"Tinggi\"\n    }\n  ]\n}\nCatatan: Untuk 'priority' isikan dengan Tinggi, Sedang, atau Rendah.\n\nData Pengguna:\n- Usia: 52\n- Gender: Laki-laki\n- BMI: 27.5\n- Glukosa: 180.0\n- Tekanan Darah: 135\n\nHasil Prediksi Machine Learning:\n- Risiko Diabetes: 56.8%\n- Risiko Hipertensi: 60.2%\n\nSeluruh Hasil Prediksi:\n| Penyakit                  | Probabilitas | Level Risiko |\n|---------------------------|:------------:|:------------:|\n| Hipertensi                |       60.2% | 🔴 Tinggi |\n\nBerikan JSON sesuai instruksi sistem berdasarkan data di atas. Hasilkan raw JSON saja, tanpa dibungkus markdown ```json."]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 1024,
        'responseMimeType' => 'application/json'
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);
echo $response;
