<x-layouts.app title="Didaction Healthcare - Cek Kesehatan AI">
    <x-navbar />

    <style>
        .bg-grid-pattern {
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, rgba(20, 184, 166, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(20, 184, 166, 0.05) 1px, transparent 1px);
        }
    </style>

    <div class="min-h-screen bg-gradient-to-br from-[#ECF9F6] via-[#F4FBF9] to-[#E8F8F5] bg-grid-pattern text-slate-800 font-sans selection:bg-teal-100 selection:text-teal-900 pb-20">
        <main class="max-w-7xl mx-auto px-4 lg:px-8 pt-12">
            <!-- CORE ASSESSMENT SECTION -->
            <section id="assessment-section" class="scroll-mt-24">
                
                <!-- Headline & Subheadline -->
                <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
                    <h2 class="font-serif text-4xl md:text-5xl font-bold tracking-tight text-[#003638]">
                        Cek Kesehatan Anda Sekarang
                    </h2>
                    <p class="text-slate-500 text-sm md:text-base">
                        Masukkan data kesehatan fungsional Anda di bawah ini. Algoritma klinis kecerdasan buatan medis Didaction akan langsung meninjau kombinasi metrik tubuh Anda secara komprehensif.
                    </p>
                </div>

                <!-- Dashboard Structured Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    
                    <!-- COLUMN LEFT: CARD INPUT FORM -->
                    <div class="bg-white rounded-3xl p-8 shadow-[0_20px_50px_rgba(20,184,166,0.06)] border border-teal-500/10 space-y-6">
                        
                        <div class="border-b border-slate-100 pb-5">
                            <h3 class="font-serif text-2xl font-bold text-[#003638]">
                                Data Kesehatan Anda
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">
                                Masukkan metrik tubuh Anda seakurat mungkin untuk hasil kecerdasan medis yang presisi.
                            </p>
                        </div>

                        <!-- Client Interaction Form -->
                        <form id="healthForm" onsubmit="handleAnalyzeSubmit(event)" class="space-y-5">
                            
                            <!-- Row 1: Usia & Jenis kelamin -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-slate-700 text-xs font-bold uppercase tracking-wider mb-2 block">
                                        Usia (tahun)
                                    </label>
                                    <input 
                                        type="number" 
                                        id="usia" 
                                        min="1" 
                                        max="120"
                                        required
                                        value="45"
                                        placeholder="Contoh: 45"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 transition-all text-slate-800 font-semibold bg-slate-50/50"
                                    >
                                </div>

                                <div>
                                    <label class="text-slate-700 text-xs font-bold uppercase tracking-wider mb-2 block">
                                        Jenis Kelamin
                                    </label>
                                    <select
                                        id="jenisKelamin"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 transition-all text-slate-800 font-semibold bg-slate-50/50 appearance-none cursor-pointer"
                                    >
                                        <option value="Laki-laki" selected>Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Kadar Glukosa -->
                            <div>
                                <label class="text-slate-700 text-xs font-bold uppercase tracking-wider mb-2 block">
                                    Kadar Glukosa Darah (mg/dL)
                                </label>
                                <input 
                                    type="number" 
                                    id="glukosa" 
                                    min="20" 
                                    max="600"
                                    required
                                    value="150"
                                    placeholder="Contoh: 150"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 transition-all text-slate-800 font-semibold bg-slate-50/50"
                                >
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase py-1 px-2.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-100/60 font-mono">
                                        Normal: 70-130 mg/dL (puasa)
                                    </span>
                                </div>
                            </div>

                            <!-- Tekanan Darah Sistolik -->
                            <div>
                                <label class="text-slate-700 text-xs font-bold uppercase tracking-wider mb-2 block">
                                    Tekanan Darah Sistolik (mmHg)
                                </label>
                                <input 
                                    type="number" 
                                    id="tekananSistolik" 
                                    min="50" 
                                    max="250"
                                    required
                                    value="140"
                                    placeholder="Contoh: 140"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 transition-all text-slate-800 font-semibold bg-slate-50/50"
                                >
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase py-1 px-2.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-100/60 font-mono">
                                        Normal: &lt;120 mmHg
                                    </span>
                                </div>
                            </div>

                            <!-- BMI -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-slate-700 text-xs font-bold uppercase tracking-wider block">
                                        BMI (Berat/Tinggi²)
                                    </label>
                                    <button 
                                        type="button"
                                        onclick="toggleBmiCalc()"
                                        class="text-xs text-[#005B60] hover:text-[#004245] font-semibold flex items-center gap-1 focus:outline-none hover:underline cursor-pointer"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 12m-6 0a6 6 0 1 0 12 0a6 6 0 1 0 -12 0"/><path d="M12 3v9"/></svg>
                                        Hitung BMI Saya
                                    </button>
                                </div>

                                <div id="bmiCalcDrawer" class="hidden mb-3 p-4 rounded-xl bg-teal-50/50 border border-teal-500/20 space-y-3">
                                    <h5 class="text-xs font-bold text-[#003638] flex items-center gap-1">Kalkulator BMI Instan</h5>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-[10px] text-slate-500 font-semibold block mb-1">Berat (kg)</label>
                                            <input 
                                                type="number"
                                                id="bmiWeight"
                                                placeholder="Contoh: 70"
                                                class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold"
                                            >
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-slate-500 font-semibold block mb-1">Tinggi (cm)</label>
                                            <input 
                                                type="number"
                                                id="bmiHeight"
                                                placeholder="Contoh: 170"
                                                class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold"
                                            >
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 text-xs pt-1">
                                        <button 
                                            type="button" 
                                            onclick="toggleBmiCalc()"
                                            class="px-3 py-1 text-slate-500 font-semibold hover:bg-slate-100 rounded"
                                        >
                                            Batal
                                        </button>
                                        <button 
                                            type="button" 
                                            onclick="applyBmiValue()"
                                            class="px-3 py-1 bg-[#005B60] hover:bg-[#00474A] text-white font-semibold rounded shadow-sm"
                                        >
                                            Terapkan
                                        </button>
                                    </div>
                                </div>

                                <input 
                                    type="number" 
                                    id="bmi" 
                                    step="0.1"
                                    min="10" 
                                    max="60"
                                    required
                                    value="28.5"
                                    placeholder="Contoh: 28.5"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 transition-all text-slate-800 font-semibold bg-slate-50/50"
                                >
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase py-1 px-2.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-100/60 font-mono">
                                        Norm: 18.5-24.9 | Overweight: 25-29.9 | Obesitas: &gt;=30
                                    </span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                id="submitBtn"
                                class="w-full bg-[#005B60] hover:bg-[#00474A] disabled:bg-slate-300 text-white font-semibold py-4 px-6 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-md shadow-teal-500/5 hover:shadow-teal-900/10 active:scale-[0.99] cursor-pointer"
                            >
                                <span>Analisis Kesehatan Saya</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </button>

                        </form>

                        <!-- QUICK BUTTONS -->
                        <div class="border-t border-slate-100 pt-5 space-y-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Coba dengan contoh data klinis:
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onclick="applySampleData(52, 'Laki-laki', 185, 135, 27.2)"
                                    class="bg-white hover:bg-teal-50/30 text-teal-900 border border-teal-600/30 font-semibold py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer hover:border-teal-500"
                                >
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Contoh: Diabetes (52 tahun)
                                </button>
                                <button
                                    type="button"
                                    onclick="applySampleData(58, 'Perempuan', 110, 165, 31.4)"
                                    class="bg-white hover:bg-teal-50/30 text-teal-900 border border-teal-600/30 font-semibold py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer hover:border-teal-500"
                                >
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Contoh: Penyakit Jantung
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMN RIGHT: CARD OUTPUT REPORT -->
                    <div class="bg-white rounded-3xl p-8 shadow-[0_20px_50px_rgba(20,184,166,0.06)] border border-teal-500/10 min-h-[660px] flex flex-col">
                        
                        <!-- Result Card Top Status line -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5 mb-6">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00A19D]"></span>
                                Hasil Analisis Medis
                            </span>
                            
                            <button 
                                id="resetBtn" 
                                onclick="resetAllForm()" 
                                class="hidden text-xs font-semibold text-slate-400 hover:text-slate-600 flex items-center gap-1 cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                Mulai Ulang
                            </button>
                        </div>

                        <!-- 1. EMPTY STATE -->
                        <div id="statusEmpty" class="flex-1 flex flex-col items-center justify-center py-20 text-center">
                            <div class="w-20 h-20 rounded-2xl bg-teal-50/50 flex items-center justify-center text-[#00A19D] mb-5 border border-teal-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 stroke-[1.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                            </div>
                            <h4 class="font-serif text-xl font-bold text-[#003638] mb-2">
                                Hasil Analisis Akan Muncul di Sini
                            </h4>
                            <p class="text-slate-500 text-xs leading-relaxed max-w-sm mx-auto">
                                Isi data kesehatan objektif Anda di sebelah kiri, lalu klik tombol <strong class="text-teal-900">"Analisis Kesehatan Saya"</strong> untuk mendapatkan diagnosis bertenaga kecerdasan medis secara menyeluruh.
                            </p>
                        </div>

                        <!-- 2. LOADING STATE -->
                        <div id="statusLoading" class="hidden flex-1 flex flex-col items-center justify-center py-20 text-center space-y-6">
                            <div class="relative">
                                <div class="w-24 h-24 rounded-full border-4 border-teal-50 border-t-teal-500 animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-[#00A19D]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                </div>
                            </div>
                            <div class="space-y-2 max-w-sm">
                                <h4 class="font-serif text-lg font-bold text-[#003638]">Mendeteksi Profil Medis Anda</h4>
                                <p id="loadingMessage" class="text-xs text-slate-400 font-mono">
                                    Mengumpulkan data kesehatan fisiologis...
                                </p>
                            </div>
                        </div>

                        <!-- 3. ERROR STATE -->
                        <div id="statusError" class="hidden flex-1 flex flex-col items-center justify-center p-8 text-center space-y-4">
                            <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <div class="space-y-2">
                                <h4 class="text-base font-bold text-slate-800">Gangguan Diagnosa Terdeteksi</h4>
                                <p id="errorMessage" class="text-xs text-slate-500 max-w-sm mx-auto"></p>
                            </div>
                        </div>

                        <!-- 4. ACTIVE RESULTS REPORT VIEW -->
                        <div id="statusSuccess" class="hidden flex-1 space-y-6">
                            
                            <!-- Header & Circular Health Gauge row -->
                            <div class="bg-[#FAFDFD] rounded-2xl p-5 border border-teal-500/5 flex flex-col sm:flex-row items-center gap-6">
                                
                                <div class="relative flex-shrink-0 w-24 h-24 rounded-full bg-white shadow-inner flex items-center justify-center border-4 border-slate-100">
                                    <div class="text-center z-10">
                                        <div id="reportScore" class="font-serif text-3xl font-extrabold text-[#005B60]">92</div>
                                        <div class="text-[8px] uppercase tracking-wider text-slate-400 font-bold">Health Score</div>
                                    </div>
                                    <svg class="absolute inset-0 w-full h-full -rotate-90">
                                        <circle cx="48" cy="48" r="44" class="stroke-teal-50 fill-none" stroke-width="4" />
                                        <circle id="gaugeProgressCircle" cx="48" cy="48" r="44" class="stroke-[#00A19D] fill-none transition-all duration-1000" stroke-width="4" stroke-dasharray="276" stroke-dashoffset="30" />
                                    </svg>
                                </div>

                                <div class="text-center sm:text-left space-y-1.5 flex-1">
                                    <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                                        <h4 class="text-slate-800 font-bold text-lg">Prediksi Status</h4>
                                        <span id="reportStatusBadge" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold leading-none">
                                            <span id="reportStatusBullet" class="w-1.5 h-1.5 rounded-full"></span>
                                            <span id="reportStatusText">Aman</span>
                                        </span>
                                    </div>
                                    <h5 id="reportSummaryTitle" class="text-xs font-bold text-[#005B60]">Analisis Selesai</h5>
                                    <p id="reportSummaryText" class="text-slate-500 text-xs leading-relaxed"></p>
                                </div>
                            </div>

                            <!-- Penyakit Metrics (Predictions) -->
                            <div class="space-y-2">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Prediksi Risiko Penyakit</h4>
                                <div id="metricsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <!-- Dynamically generated parameters metrics in JS -->
                                </div>
                            </div>

                            <!-- Recommendations list area -->
                            <div class="space-y-4 pt-2 border-t border-slate-100">
                                <div class="space-y-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                        <span class="text-[#00A19D]">●</span> Rekomendasi Kecerdasan Buatan (AI)
                                    </h4>
                                    <div id="listActionPlan" class="text-xs text-slate-600 space-y-3 font-medium">
                                        <!-- Dynamic elements from JS -->
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script>
        // Toggle the interactive BMI sliding drawer
        function toggleBmiCalc() {
            const drawer = document.getElementById('bmiCalcDrawer');
            drawer.classList.toggle('hidden');
        }

        // Apply interactive calculation values for BMIs
        function applyBmiValue() {
            const w = parseFloat(document.getElementById('bmiWeight').value);
            const h = parseFloat(document.getElementById('bmiHeight').value) / 100;

            if (w > 0 && h > 0) {
                const bmi = (w / (h * h)).toFixed(1);
                document.getElementById('bmi').value = bmi;
                toggleBmiCalc();
            } else {
                alert('Silakan isi dimensi berat dan tinggi tubuh Anda secara logis.');
            }
        }

        // Apply clinic simulation buttons
        function applySampleData(age, gender, glucose, bloodPressure, bmiVal) {
            document.getElementById('usia').value = age;
            document.getElementById('jenisKelamin').value = gender;
            document.getElementById('glukosa').value = glucose;
            document.getElementById('tekananSistolik').value = bloodPressure;
            document.getElementById('bmi').value = bmiVal;

            handleFormPost();
        }

        function resetAllForm() {
            document.getElementById('healthForm').reset();
            
            document.getElementById('statusEmpty').classList.remove('hidden');
            document.getElementById('statusLoading').classList.add('hidden');
            document.getElementById('statusError').classList.add('hidden');
            document.getElementById('statusSuccess').classList.add('hidden');
            document.getElementById('resetBtn').classList.add('hidden');
        }

        const loadingSteps = [
            'Mengumpulkan data kesehatan fisiologis Anda...',
            'Menghubungkan ke server diagnosa AI Didaction...',
            'Meninjau standar risiko klinis & kardiovaskular...',
            'Menyusun skema rekomendasi gizi & nutrisi...'
        ];
        let loadingInterval = null;

        function startLoadingTransition() {
            document.getElementById('statusEmpty').classList.add('hidden');
            document.getElementById('statusError').classList.add('hidden');
            document.getElementById('statusSuccess').classList.add('hidden');
            document.getElementById('statusLoading').classList.remove('hidden');
            document.getElementById('resetBtn').classList.add('hidden');

            let currentStep = 0;
            document.getElementById('loadingMessage').innerText = loadingSteps[0];
            
            loadingInterval = setInterval(() => {
                currentStep = (currentStep + 1) % loadingSteps.length;
                document.getElementById('loadingMessage').innerText = loadingSteps[currentStep];
            }, 1000);
        }

        function stopLoadingTransition() {
            if (loadingInterval) {
                clearInterval(loadingInterval);
                loadingInterval = null;
            }
            document.getElementById('statusLoading').classList.add('hidden');
        }

        function handleAnalyzeSubmit(e) {
            e.preventDefault();
            handleFormPost();
        }

        async function handleFormPost() {
            const age = parseInt(document.getElementById('usia').value);
            const gender = document.getElementById('jenisKelamin').value === 'Laki-laki' ? 1 : 0;
            const glucose = parseFloat(document.getElementById('glukosa').value);
            const blood_pressure = parseInt(document.getElementById('tekananSistolik').value);
            const bmi = parseFloat(document.getElementById('bmi').value);

            startLoadingTransition();

            try {
                // Point to the backend API route correctly
                const response = await fetch('/api/predict', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ age, gender, glucose, blood_pressure, bmi })
                });

                const result = await response.json();
                stopLoadingTransition();

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Terjadi gangguan pemrosesan data klinis.');
                }

                renderMedicalReport(result.data);

            } catch (err) {
                stopLoadingTransition();
                document.getElementById('statusError').classList.remove('hidden');
                document.getElementById('errorMessage').innerText = err.message;
            }
        }

        function renderMedicalReport(data) {
            document.getElementById('statusSuccess').classList.remove('hidden');
            document.getElementById('resetBtn').classList.remove('hidden');

            const predictions = Object.entries(data.predictions || {}).map(([key, p]) => p);
            let highestProb = 0;
            let riskStatus = 'Aman';

            predictions.forEach(p => {
                if(p.probability > highestProb) {
                    highestProb = p.probability;
                    if(p.risk_level === 'High') riskStatus = 'Bahaya';
                    else if(p.risk_level === 'Moderate' && riskStatus !== 'Bahaya') riskStatus = 'Peringatan';
                }
            });

            // Calculate score (out of 100)
            const score = Math.max(0, Math.round(100 - (highestProb * 100)));
            document.getElementById('reportScore').innerText = score;
            
            const circle = document.getElementById('gaugeProgressCircle');
            const circumference = 276; 
            const strokeDashoffset = circumference - (circumference * score) / 100;
            circle.style.strokeDashoffset = strokeDashoffset;

            document.getElementById('reportStatusText').innerText = riskStatus;
            
            const badge = document.getElementById('reportStatusBadge');
            const bullet = document.getElementById('reportStatusBullet');
            badge.className = "inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold leading-none";
            bullet.className = "w-1.5 h-1.5 rounded-full";

            if (riskStatus === 'Aman') {
                badge.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-100');
                bullet.classList.add('bg-emerald-500');
            } else if (riskStatus === 'Peringatan') {
                badge.classList.add('bg-amber-50', 'text-amber-700', 'border', 'border-amber-100');
                bullet.classList.add('bg-amber-500');
            } else {
                badge.classList.add('bg-rose-50', 'text-rose-700', 'border', 'border-rose-100');
                bullet.classList.add('bg-rose-500');
            }

            document.getElementById('reportSummaryTitle').innerText = data.highest_risk ? "Risiko Tertinggi: " + data.highest_risk : "Analisis Selesai";
            document.getElementById('reportSummaryText').innerText = data.summary || "Berikut adalah analisis dari metrik kesehatan Anda.";

            const metricsContainer = document.getElementById('metricsGrid');
            metricsContainer.innerHTML = '';
            
            predictions.sort((a,b) => b.probability - a.probability).forEach(metric => {
                let badgeClass = "bg-emerald-50 text-emerald-700";
                if (metric.risk_level === 'Moderate') badgeClass = "bg-amber-50 text-amber-700";
                else if (metric.risk_level === 'High') badgeClass = "bg-rose-50 text-rose-700";

                const widget = `
                    <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm space-y-1.5">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wide">${metric.label}</div>
                        <div class="font-mono text-lg font-bold text-slate-800">${metric.percentage}</div>
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded ${badgeClass}">
                            ${metric.risk_level}
                        </span>
                    </div>
                `;
                metricsContainer.innerHTML += widget;
            });

            const actionPlanContainer = document.getElementById('listActionPlan');
            actionPlanContainer.innerHTML = '';
            
            if (data.action_plans && data.action_plans.length > 0) {
                data.action_plans.forEach(plan => {
                    const priority = plan.priority || 'Sedang';
                    const prColor = priority === 'Tinggi' ? 'rose' : priority === 'Sedang' ? 'amber' : 'emerald';
                    
                    actionPlanContainer.innerHTML += `
                        <div class="p-4 bg-${prColor}-50 border-l-4 border-${prColor}-400 rounded-lg shadow-sm">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">${plan.title}</p>
                                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">${plan.description}</p>
                                </div>
                                <span class="ml-3 px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-${prColor}-100 text-${prColor}-800 rounded-full whitespace-nowrap">
                                    ${priority}
                                </span>
                            </div>
                        </div>
                    `;
                });
            } else {
                actionPlanContainer.innerHTML = '<p class="text-slate-500 italic">Rekomendasi AI tidak tersedia saat ini.</p>';
            }
        }
    </script>

    <x-footer />
</x-layouts.app>