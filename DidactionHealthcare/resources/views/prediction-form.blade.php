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
            alert('Error: ' + (result.message || 'Terjadi kesalahan'));
        }
    } catch (error) {
        document.getElementById('loadingModal').classList.add('hidden');
        alert('Terjadi kesalahan: ' + error.message);
        console.error(error);
    }
});

function getRiskColor(level) {
    switch(level) {
        case 'High': return { bg: 'bg-red-50', border: 'border-red-500', text: 'text-red-700', badge: 'bg-red-100 text-red-800' };
        case 'Moderate': return { bg: 'bg-yellow-50', border: 'border-yellow-500', text: 'text-yellow-700', badge: 'bg-yellow-100 text-yellow-800' };
        default: return { bg: 'bg-green-50', border: 'border-green-500', text: 'text-green-700', badge: 'bg-green-100 text-green-800' };
    }
}

function getRiskIcon(level) {
    switch(level) {
        case 'High': return '🔴';
        case 'Moderate': return '🟡';
        default: return '🟢';
    }
}

function displayResults(data, inputData) {
    // Build predictions list sorted by probability (descending)
    const predictions = Object.entries(data.predictions || {}).map(([key, pred]) => ({
        key,
        ...pred
    })).sort((a, b) => b.probability - a.probability);

    // Build action plans HTML
    const actionPlans = data.action_plans || [];
    const recHtml = actionPlans.length > 0
        ? actionPlans.map((rec, i) => {
            const priority = rec.priority || 'Sedang';
            const prColor = priority === 'Tinggi' ? 'red' : priority === 'Sedang' ? 'yellow' : 'green';
            return `
                <div class="p-4 bg-${prColor}-50 border-l-4 border-${prColor}-400 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">${rec.title}</p>
                            <p class="text-sm text-gray-700 mt-1">${rec.description}</p>
                        </div>
                        <span class="ml-3 px-2 py-1 text-xs font-medium bg-${prColor}-100 text-${prColor}-800 rounded-full whitespace-nowrap">
                            ${priority}
                        </span>
                    </div>
                </div>
            `;
        }).join('')
        : '<p class="text-gray-500 italic">Rekomendasi AI tidak tersedia saat ini.</p>';

    const summaryHtml = data.summary 
        ? `<div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded-lg border border-blue-200 text-sm"><p><strong>Kesimpulan:</strong> ${data.summary}</p></div>` 
        : '';

    const genderLabel = inputData.gender === 0 ? 'Perempuan' : 'Laki-laki';

    const html = `
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📋 Hasil Analisis</h2>

            <!-- Data Pasien -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">Data Kesehatan Anda:</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p>Usia: <span class="font-medium">${inputData.age} tahun</span></p>
                    <p>Jenis Kelamin: <span class="font-medium">${genderLabel}</span></p>
                    <p>Glukosa: <span class="font-medium">${inputData.glucose} mg/dL</span></p>
                    <p>Tekanan Darah: <span class="font-medium">${inputData.blood_pressure} mmHg</span></p>
                    <p>BMI: <span class="font-medium">${inputData.bmi} kg/m²</span></p>
                </div>
            </div>

            <!-- Highest Risk -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-amber-800">
                    <span class="font-semibold">⚡ Risiko Tertinggi:</span> ${data.highest_risk || '-'}
                </p>
                <p class="text-xs text-amber-600 mt-1">Mode model: ${data.model_mode || '-'}</p>
            </div>

            <!-- Predictions -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">🔮 Prediksi Risiko Penyakit</h3>
                ${predictions.map((pred, i) => {
                    const colors = getRiskColor(pred.risk_level);
                    const icon = getRiskIcon(pred.risk_level);
                    return `
                        <div class="mb-3 p-4 ${colors.bg} rounded-lg border-l-4 ${colors.border}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm ${colors.text} font-medium">${icon} ${pred.risk_level}</p>
                                    <p class="text-lg font-bold text-gray-900">${pred.label}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-teal-600">${pred.percentage}</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full ${colors.badge}">
                                        ${pred.risk_level}
                                    </span>
                                </div>
                            </div>
                            <!-- Progress bar -->
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full ${pred.risk_level === 'High' ? 'bg-red-500' : pred.risk_level === 'Moderate' ? 'bg-yellow-500' : 'bg-green-500'}"
                                     style="width: ${(pred.probability * 100).toFixed(1)}%"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>

            <!-- AI Recommendations -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">💡 Action Plans (AI)</h3>
                ${summaryHtml}
                <div class="space-y-3">
                    ${recHtml}
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-2">⚠️ PERINGATAN PENTING</h3>
                <p class="text-red-900 text-sm">
                    ${data.disclaimer || 'Hasil ini hanya untuk tujuan edukasi dan skrining awal. Bukan pengganti diagnosis medis profesional. Segera konsultasikan dengan dokter untuk evaluasi lebih lanjut.'}
                </p>
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
