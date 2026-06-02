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
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        document.getElementById('loadingModal').classList.add('hidden');

        if (result.status === 'success') {
            displayResults(result.data, data);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        document.getElementById('loadingModal').classList.add('hidden');
        alert('Terjadi kesalahan: ' + error.message);
        console.error(error);
    }
});

function displayResults(apiData, formData) {
    const predictionsHtml = Object.values(apiData.predictions || {}).map((pred, index) => `
        <div class="mb-3 p-3 bg-gray-50 rounded-lg border-l-4 ${
            pred.risk_level === 'High' ? 'border-red-500' : 
            pred.risk_level === 'Moderate' ? 'border-yellow-500' : 
            'border-green-500'
        }">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">${pred.label}</p>
                    <p class="text-sm text-gray-500">Risiko: ${pred.risk_level}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold ${
                        pred.risk_level === 'High' ? 'text-red-600' : 
                        pred.risk_level === 'Moderate' ? 'text-yellow-600' : 
                        'text-green-600'
                    }">${pred.percentage}</p>
                    <p class="text-xs text-gray-500">probabilitas</p>
                </div>
            </div>
        </div>
    `).join('');

    let recommendationsHtml = '';
    if (apiData.recommendations && apiData.recommendations.length > 0) {
        recommendationsHtml = `
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">💡 Saran Tindakan</h3>
                <div class="space-y-4">
                    ${apiData.recommendations.map(rec => `
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 flex items-center justify-between">
                                <span>${rec.title}</span>
                                <span class="text-xs px-2 py-1 rounded bg-blue-200 text-blue-800">${rec.priority} Priority</span>
                            </h4>
                            <p class="text-sm text-gray-700 mt-2">${rec.description}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } else {
        recommendationsHtml = `
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">💡 Saran Tindakan</h3>
                <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                    <p>Sistem AI tidak dapat menghasilkan saran saat ini.</p>
                </div>
            </div>
        `;
    }

    const html = `
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📋 Hasil Analisis</h2>

            <!-- Symptoms Summary -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">Data Kesehatan Anda:</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p>Usia: <span class="font-medium">${formData.age} tahun</span></p>
                    <p>Jenis Kelamin: <span class="font-medium">${formData.gender === 1 ? 'Laki-laki' : 'Perempuan'}</span></p>
                    <p>Glukosa: <span class="font-medium">${formData.glucose} mg/dL</span></p>
                    <p>Tekanan Darah: <span class="font-medium">${formData.blood_pressure} mmHg</span></p>
                    <p>BMI: <span class="font-medium">${formData.bmi}</span></p>
                </div>
            </div>

            <!-- Predictions -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">🔮 Prediksi Risiko Penyakit</h3>
                ${predictionsHtml}
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800">
                    <p class="font-semibold">⚠️ Risiko Tertinggi: ${apiData.highest_risk}</p>
                </div>
            </div>

            <!-- Recommendations -->
            ${recommendationsHtml}

            <!-- Disclaimer -->
            ${apiData.disclaimer ? `
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                <p><strong>Disclaimer:</strong> ${apiData.disclaimer}</p>
            </div>
            ` : ''}
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
