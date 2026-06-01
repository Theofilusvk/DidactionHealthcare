@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Cek Kesehatan Anda Sekarang
            </h1>
            <p class="text-lg text-gray-600">
                Masukkan data kesehatan Anda dan dapatkan analisis prediksi penyakit beserta saran kesehatan personal
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Form Input -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Data Kesehatan Anda</h2>
                
                <form id="predictionForm" class="space-y-6">
                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                            Usia (tahun)
                        </label>
                        <input 
                            type="number" 
                            id="age" 
                            name="age" 
                            min="0" 
                            max="120" 
                            placeholder="45"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            required
                        >
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                            Jenis Kelamin
                        </label>
                        <select 
                            id="gender" 
                            name="gender"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            required
                        >
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="0">Perempuan</option>
                            <option value="1">Laki-laki</option>
                        </select>
                    </div>

                    <!-- Glucose -->
                    <div>
                        <label for="glucose" class="block text-sm font-medium text-gray-700 mb-2">
                            Kadar Glukosa Darah (mg/dL)
                        </label>
                        <input 
                            type="number" 
                            id="glucose" 
                            name="glucose" 
                            min="0" 
                            max="500" 
                            placeholder="150"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Normal: 70-100 mg/dL (puasa)</p>
                    </div>

                    <!-- Blood Pressure -->
                    <div>
                        <label for="blood_pressure" class="block text-sm font-medium text-gray-700 mb-2">
                            Tekanan Darah Sistolik (mmHg)
                        </label>
                        <input 
                            type="number" 
                            id="blood_pressure" 
                            name="blood_pressure" 
                            min="0" 
                            max="300" 
                            placeholder="140"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Normal: <120 mmHg</p>
                    </div>

                    <!-- BMI -->
                    <div>
                        <label for="bmi" class="block text-sm font-medium text-gray-700 mb-2">
                            BMI (Berat/Tinggi²)
                        </label>
                        <input 
                            type="number" 
                            id="bmi" 
                            name="bmi" 
                            min="5" 
                            max="100" 
                            step="0.1"
                            placeholder="28.5"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Normal: 18.5-24.9 | Overweight: 25-29.9</p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        id="submitBtn"
                        class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-lg transition duration-200"
                    >
                        Analisis Kesehatan Saya
                    </button>
                </form>

                <!-- Example Data -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3">Coba dengan contoh data:</p>
                    <button
                        type="button"
                        onclick="fillDiabetesExample()"
                        class="w-full mb-2 px-4 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 text-sm font-medium"
                    >
                        Contoh: Diabetes (52 tahun)
                    </button>
                    <button
                        type="button"
                        onclick="fillHeartExample()"
                        class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium"
                    >
                        Contoh: Penyakit Jantung (58 tahun)
                    </button>
                </div>
            </div>

            <!-- Results Display -->
            <div id="resultsContainer" class="hidden lg:block bg-white rounded-lg shadow-lg p-8 sticky top-8">
                <div class="flex items-center justify-center h-96">
                    <div class="text-center">
                        <div class="text-5xl mb-4">📋</div>
                        <p class="text-gray-500">Hasil analisis akan muncul di sini</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results (Mobile/Full Width) -->
        <div id="resultsFullWidth" class="mt-8 lg:hidden"></div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600 mx-auto mb-4"></div>
        <p class="text-gray-700 font-medium">Menganalisis data kesehatan Anda...</p>
    </div>
</div>

<script>
function fillDiabetesExample() {
    document.getElementById('age').value = 52;
    document.getElementById('gender').value = 1;
    document.getElementById('glucose').value = 180;
    document.getElementById('blood_pressure').value = 135;
    document.getElementById('bmi').value = 27.5;
}

function fillHeartExample() {
    document.getElementById('age').value = 58;
    document.getElementById('gender').value = 0;
    document.getElementById('glucose').value = 145;
    document.getElementById('blood_pressure').value = 145;
    document.getElementById('bmi').value = 26.6;
}

document.getElementById('predictionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        age: parseInt(document.getElementById('age').value),
        gender: parseInt(document.getElementById('gender').value),
        glucose: parseFloat(document.getElementById('glucose').value),
        blood_pressure: parseInt(document.getElementById('blood_pressure').value),
        bmi: parseFloat(document.getElementById('bmi').value),
    };

    // Show loading
    document.getElementById('loadingModal').classList.remove('hidden');

    try {
        const response = await fetch('/api/predict', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        document.getElementById('loadingModal').classList.add('hidden');

        if (result.status === 'success') {
            displayResults(result.data);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        document.getElementById('loadingModal').classList.add('hidden');
        alert('Terjadi kesalahan: ' + error.message);
        console.error(error);
    }
});

function displayResults(data) {
    const html = `
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📋 Hasil Analisis</h2>

            <!-- Symptoms Summary -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">Data Kesehatan Anda:</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p>Usia: <span class="font-medium">${data.symptoms_reported['Usia']}</span></p>
                    <p>Jenis Kelamin: <span class="font-medium">${data.symptoms_reported['Jenis Kelamin']}</span></p>
                    <p>Glukosa: <span class="font-medium">${data.symptoms_reported['Glukosa Darah']}</span></p>
                    <p>Tekanan Darah: <span class="font-medium">${data.symptoms_reported['Tekanan Darah Sistolik']}</span></p>
                    <p>BMI: <span class="font-medium">${data.symptoms_reported['BMI']}</span></p>
                </div>
            </div>

            <!-- Predictions -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">🔮 Prediksi Penyakit (Top 3)</h3>
                ${data.predictions.map(pred => `
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg border-l-4 ${
                        pred.rank === 1 ? 'border-red-500' : 
                        pred.rank === 2 ? 'border-yellow-500' : 
                        'border-green-500'
                    }">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">${pred.icon} ${pred.label}</p>
                                <p class="text-lg font-bold text-gray-900">${pred.disease}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-teal-600">${pred.confidence}</p>
                                <p class="text-xs text-gray-500">keyakinan</p>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>

            <!-- Explanation -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">💡 Penjelasan:</h3>
                <p class="text-gray-700 text-sm">${data.explanation}</p>
            </div>

            <!-- Food Recommendations -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">🍽️ Saran Pola Makan</h3>
                
                <div class="mb-4">
                    <h4 class="font-medium text-green-700 mb-2">✅ Makanan Dianjurkan:</h4>
                    <ul class="space-y-1 text-sm">
                        ${data.food_recommendations.recommended.map(item => `
                            <li class="flex gap-2">
                                <span class="text-green-600">✓</span>
                                <div>
                                    <p class="font-medium text-gray-800">${item.name}</p>
                                    <p class="text-gray-600 text-xs">${item.reason}</p>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="mb-4">
                    <h4 class="font-medium text-red-700 mb-2">❌ Makanan Dihindari:</h4>
                    <ul class="space-y-1 text-sm">
                        ${data.food_recommendations.avoid.map(item => `
                            <li class="flex gap-2">
                                <span class="text-red-600">✗</span>
                                <div>
                                    <p class="font-medium text-gray-800">${item.name}</p>
                                    <p class="text-gray-600 text-xs">${item.reason}</p>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="bg-blue-50 rounded p-3 text-sm">
                    <h4 class="font-medium text-gray-800 mb-2">📅 Pola Makan Harian:</h4>
                    <ul class="space-y-1 text-gray-700">
                        <li><span class="font-medium">Sarapan:</span> ${data.food_recommendations.daily_pattern.breakfast}</li>
                        <li><span class="font-medium">Camilan Pagi:</span> ${data.food_recommendations.daily_pattern.snack_morning}</li>
                        <li><span class="font-medium">Makan Siang:</span> ${data.food_recommendations.daily_pattern.lunch}</li>
                        <li><span class="font-medium">Camilan Sore:</span> ${data.food_recommendations.daily_pattern.snack_afternoon}</li>
                        <li><span class="font-medium">Makan Malam:</span> ${data.food_recommendations.daily_pattern.dinner}</li>
                        <li><span class="text-xs text-gray-600 mt-2">Catatan: ${data.food_recommendations.daily_pattern.note}</span></li>
                    </ul>
                </div>
            </div>

            <!-- Exercise Recommendations -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">🏃 Saran Olahraga</h3>
                
                <div class="mb-4">
                    <h4 class="font-medium text-green-700 mb-2">✅ Olahraga Dianjurkan:</h4>
                    <div class="space-y-2">
                        ${data.exercise_recommendations.recommended.map(item => `
                            <div class="bg-green-50 rounded p-3 text-sm">
                                <p class="font-medium text-gray-800">${item.type}</p>
                                <p class="text-gray-600">⏱️ ${item.duration} | 📅 ${item.frequency}</p>
                                <p class="text-green-700 text-xs">💡 ${item.benefit}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="font-medium text-red-700 mb-2">❌ Olahraga Dihindari:</h4>
                    <ul class="space-y-1 text-sm">
                        ${data.exercise_recommendations.avoid.map(item => `
                            <li class="flex gap-2">
                                <span class="text-red-600">✗</span>
                                <div>
                                    <p class="font-medium text-gray-800">${item.type}</p>
                                    <p class="text-gray-600 text-xs">${item.reason}</p>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="bg-blue-50 rounded p-3">
                    <h4 class="font-medium text-gray-800 mb-2">💡 Tips Olahraga Aman:</h4>
                    <ul class="space-y-1 text-sm text-gray-700">
                        ${data.exercise_recommendations.tips.map(tip => `
                            <li>• ${tip}</li>
                        `).join('')}
                    </ul>
                </div>
            </div>

            <!-- Medical Warning -->
            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-2">⚠️ PERINGATAN PENTING</h3>
                <p class="text-red-900 text-sm mb-3">${data.medical_warning.disclaimer}</p>
                
                <div class="mb-3">
                    <h4 class="font-medium text-red-800 text-sm mb-1">🚨 Gejala Darurat (segera ke IGD):</h4>
                    <ul class="text-sm text-red-900">
                        ${data.medical_warning.red_flags.map(flag => `
                            <li>• ${flag}</li>
                        `).join('')}
                    </ul>
                </div>

                <div class="mb-2">
                    <p class="text-sm"><span class="font-medium">🏥 Konsultasi Dokter:</span> ${data.medical_warning.when_to_see_doctor}</p>
                </div>
                <div>
                    <p class="text-sm font-bold text-red-900">🚑 Ke IGD Jika: ${data.medical_warning.when_to_er}</p>
                </div>
            </div>
        </div>
    `;

    const resultsContainer = document.getElementById('resultsContainer');
    const resultsFullWidth = document.getElementById('resultsFullWidth');
    
    resultsContainer.innerHTML = html;
    resultsContainer.classList.remove('hidden');
    
    resultsFullWidth.innerHTML = html;
    
    // Scroll to results
    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
@endsection
