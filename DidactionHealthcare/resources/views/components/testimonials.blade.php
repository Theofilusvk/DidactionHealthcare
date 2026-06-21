{{-- Model & Methodology Section (replaces fake testimonials) --}}
<section class="section-padding bg-white relative" id="methodology" style="scroll-margin-top: 80px;">

    <div class="max-w-[1200px] mx-auto px-6">

        {{-- Section header --}}
        <div class="text-center mb-14">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-4">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                Model & Methodology
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-800 mb-4"
                style="transition-delay: 0.1s;">
                Built on Clinical Evidence,<br/>Not Assumptions.
            </h2>
            <p class="reveal text-lg text-gray-500 max-w-[560px] mx-auto leading-relaxed"
               style="transition-delay: 0.2s;">
                Didaction's predictions are grounded in public medical datasets and established clinical thresholds used by healthcare professionals.
            </p>
        </div>

        {{-- Two-column editorial layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start mb-14">

            {{-- Left: Model cards --}}
            <div class="reveal space-y-4" style="transition-delay: 0.1s;">
                <h3 class="font-display text-xl text-gray-800 mb-4">Prediction Models</h3>

                @php
                    $models = [
                        ['Hypertension Risk', 'Framingham Heart Study guidelines. Features: systolic BP, BMI, age, cholesterol.'],
                        ['Heart Disease Risk', 'UCI Heart Disease Dataset. Features: chest pain type, ECG results, max heart rate, cholesterol.'],
                        ['Stroke Risk', 'Kaggle Stroke Prediction Dataset (5,110 records). Features: glucose, BMI, age, hypertension flag.'],
                        ['Chronic Kidney Disease', 'UCI CKD Dataset. Features: serum creatinine, urine protein, blood glucose, hemoglobin.'],
                        ['Diabetes Risk', 'PIMA Indians Diabetes Dataset. Features: HbA1c, blood glucose, BMI, insulin level.'],
                    ];
                @endphp

                @foreach ($models as $i => [$disease, $detail])
                    <div class="reveal flex gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50 hover:border-brand-teal/20 hover:bg-brand-teal-wash transition-colors duration-200"
                         style="transition-delay: {{ ($i + 2) * 0.07 }}s;">
                        <div class="shrink-0 w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center shadow-sm mt-0.5">
                            <svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-800 mb-0.5">{{ $disease }}</div>
                            <div class="text-xs text-gray-500 leading-relaxed">{{ $detail }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Right: Clinical thresholds + methodology --}}
            <div class="reveal space-y-6" style="transition-delay: 0.2s;">
                <div>
                    <h3 class="font-display text-xl text-gray-800 mb-4">Clinical Thresholds Applied</h3>
                    <div class="grid grid-cols-2 gap-3">
                        @php
                            $thresholds = [
                                ['Systolic BP', '≥130 mmHg → Hypertension risk'],
                                ['Fasting Glucose', '≥126 mg/dL → Diabetes threshold'],
                                ['HbA1c', '≥6.5% → Diabetic range'],
                                ['BMI', '≥30 → Obese (elevated risk)'],
                                ['Urine Protein', 'Proteinuria → CKD marker'],
                                ['Serum Creatinine', 'eGFR-based CKD staging'],
                            ];
                        @endphp
                        @foreach ($thresholds as [$metric, $meaning])
                            <div class="bg-gray-50 border border-gray-100 rounded-xl p-3.5">
                                <div class="text-xs font-bold text-gray-800 mb-1">{{ $metric }}</div>
                                <div class="text-[11px] text-gray-500 leading-relaxed">{{ $meaning }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-brand-teal-pale border border-brand-teal/15 rounded-2xl p-6">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-8 h-8 rounded-lg bg-brand-teal flex items-center justify-center mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-brand-teal mb-1">Not a Medical Device</div>
                            <div class="text-xs text-[#0a5a5a] leading-relaxed">
                                Didaction Healthcare is a risk screening tool, not a diagnostic instrument. Predictions are probabilistic estimates based on population datasets. Always consult a licensed healthcare professional for diagnosis and treatment.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom: ML algorithm highlight bar --}}
        <div class="reveal border border-gray-100 rounded-2xl overflow-hidden" style="transition-delay: 0.3s;">
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                <div class="px-8 py-6 text-center sm:text-left">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Core Algorithm</div>
                    <div class="font-display text-2xl text-gray-900">XGBoost</div>
                    <div class="text-xs text-gray-400 mt-1">Gradient-boosted decision trees — industry standard for tabular clinical data</div>
                </div>
                <div class="px-8 py-6 text-center">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Models Deployed</div>
                    <div class="font-display text-2xl text-gray-900">5 Independent</div>
                    <div class="text-xs text-gray-400 mt-1">One per disease, trained and validated separately</div>
                </div>
                <div class="px-8 py-6 text-center sm:text-right">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Input Features</div>
                    <div class="font-display text-2xl text-gray-900">Clinical Biomarkers</div>
                    <div class="text-xs text-gray-400 mt-1">Glucose, BP, BMI, HbA1c, creatinine, protein — not lifestyle surveys</div>
                </div>
            </div>
        </div>

    </div>
</section>
