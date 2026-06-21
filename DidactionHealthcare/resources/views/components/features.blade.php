{{-- Features Section — Bento/Editorial Layout --}}
<section
    class="section-padding relative bg-white"
    id="features"
    style="scroll-margin-top: 80px;"
>
    {{-- Subtle grid texture --}}
    <div class="absolute inset-0 z-0 opacity-[0.018]
                bg-[linear-gradient(#0D6E6E_1px,transparent_1px),linear-gradient(90deg,#0D6E6E_1px,transparent_1px)]
                bg-[size:60px_60px]"></div>

    <div class="relative z-[1] max-w-[1200px] mx-auto px-6">

        {{-- Section header --}}
        <div class="mb-16 max-w-[640px]">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-5">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                What Didaction Does
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-900 mb-4"
                style="transition-delay: 0.1s;">
                Clinical Intelligence,<br/>Not Generic Advice.
            </h2>
            <p class="reveal text-[1.0625rem] text-gray-500 leading-[1.75]"
               style="transition-delay: 0.2s;">
                Every output is grounded in the metrics you provide — real biomarkers, real disease models, real recommendations.
            </p>
        </div>

        {{--
            BENTO GRID
            - gap-5 for uniform spacing (20px) horizontally & vertically
            - items-stretch is default grid behavior — all cards in a row share equal height
            - Row 1: Risk Prediction (lg:col-span-2) + Health Score (lg:col-span-1)
            - Row 2: Diet (col-span-1) + Activity (col-span-1) + PDF (col-span-1)
            - Row 3: Clinical Metrics (lg:col-span-2) → sits in col 1-2, PDF fills col 3 above
        --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- ═══════════════════════════════════════
                 ROW 1, CARD A: Multi-Disease Risk Prediction
                 Spans 2/3 columns on large screens
            ═══════════════════════════════════════ --}}
            <div class="reveal lg:col-span-2 relative rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden group flex flex-col"
                 style="transition-delay: 0.05s;">

                {{-- Coral accent top stripe --}}
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-coral"></div>

                {{-- Consistent 28px padding on all sides (pt accounts for the 3px stripe) --}}
                <div class="p-7 flex flex-col flex-1 pt-9">

                    {{-- Icon --}}
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center mb-4 shadow-sm shrink-0">
                        <svg class="w-5 h-5 text-brand-coral" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>

                    {{-- Title --}}
                    <h3 class="font-display text-[1.25rem] leading-snug text-gray-900 mb-2">Multi-Disease Risk Prediction</h3>

                    {{-- Description --}}
                    <p class="text-sm text-gray-500 leading-[1.7] mb-6 max-w-[420px]">
                        Simultaneously predicts risk for <strong class="font-semibold text-gray-700">5 chronic diseases</strong> — each with a percentage score and severity level — powered by XGBoost machine learning models.
                    </p>

                    {{-- Disease rows — 2-column grid, consistent row height --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-auto">
                        @php
                            $diseases = [
                                ['Hypertension',           'HIGH',     'bg-red-50   text-red-600  border-red-100'],
                                ['Heart Disease',          'MODERATE', 'bg-amber-50 text-amber-700 border-amber-100'],
                                ['Stroke',                 'LOW',      'bg-teal-50  text-[#0D6E6E] border-teal-100'],
                                ['Chronic Kidney Disease', 'LOW',      'bg-teal-50  text-[#0D6E6E] border-teal-100'],
                                ['Diabetes',               'MODERATE', 'bg-amber-50 text-amber-700 border-amber-100'],
                            ];
                        @endphp
                        @foreach ($diseases as [$name, $level, $badgeClass])
                            <div class="flex items-center justify-between bg-white rounded-xl px-4 py-3 border border-gray-100 min-h-[44px]">
                                <span class="text-[0.8125rem] font-medium text-gray-700 leading-tight">{{ $name }}</span>
                                <span class="shrink-0 ml-3 text-[0.625rem] font-bold tracking-wide px-2.5 py-1 rounded-full border {{ $badgeClass }}">{{ $level }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════
                 ROW 1, CARD B: Health Score
                 Dark teal background — same height as Risk card via grid stretch
            ═══════════════════════════════════════ --}}
            <div class="reveal relative rounded-2xl border border-brand-teal/20 bg-brand-teal overflow-hidden flex flex-col"
                 style="transition-delay: 0.1s;">

                <div class="p-7 flex flex-col flex-1">

                    {{-- Icon --}}
                    <div class="w-10 h-10 rounded-xl bg-white/25 border border-white/30 flex items-center justify-center mb-4 shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>

                    {{-- Title --}}
                    <h3 class="font-display text-[1.25rem] leading-snug text-white mb-2">Health Score</h3>

                    {{-- Description — flex-1 pushes score to bottom --}}
                    <p class="text-sm text-white/90 leading-[1.7] flex-1">
                        A composite score from 0–100 that aggregates all risk factors into a single, human-readable number — your overall health status at a glance.
                    </p>

                    {{-- Score display — pinned to bottom --}}
                    <div class="mt-6 pt-5 border-t border-white/15">
                        <div class="flex items-end gap-1.5">
                            <span class="font-display text-[3.25rem] text-white leading-none">75</span>
                            <span class="text-white/70 text-base mb-1.5">/ 100</span>
                        </div>
                        <div class="text-white/70 text-xs mt-1.5 font-medium tracking-wide uppercase">Moderate risk profile</div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════
                 ROW 2, CARD C: Personalized Diet Plans
            ═══════════════════════════════════════ --}}
            <div class="reveal relative rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden group flex flex-col"
                 style="transition-delay: 0.15s;">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-teal opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="p-7 flex flex-col flex-1">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center mb-4 shadow-sm shrink-0">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-[1.25rem] leading-snug text-gray-900 mb-2">Personalized Diet Plans</h3>
                    <p class="text-sm text-gray-500 leading-[1.7]">
                        Daily meal menus tailored to your disease profile. If you have CKD, protein is restricted. If you have hypertension, sodium is limited. Not generic — condition-specific.
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════
                 ROW 2, CARD D: Safe Activity Guidance
            ═══════════════════════════════════════ --}}
            <div class="reveal relative rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden group flex flex-col"
                 style="transition-delay: 0.2s;">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-teal opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="p-7 flex flex-col flex-1">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center mb-4 shadow-sm shrink-0">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-[1.25rem] leading-snug text-gray-900 mb-2">Safe Activity Guidance</h3>
                    <p class="text-sm text-gray-500 leading-[1.7]">
                        Physical activity plans adapted to your condition — with explicit safety notes. High hypertension risk? High-intensity cardio is flagged. Every recommendation includes a safety annotation.
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════
                 ROW 2, CARD E: Full Report to PDF
            ═══════════════════════════════════════ --}}
            <div class="reveal relative rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden group flex flex-col"
                 style="transition-delay: 0.25s;">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-teal opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="p-7 flex flex-col flex-1">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center mb-4 shadow-sm shrink-0">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-[1.25rem] leading-snug text-gray-900 mb-2">Full Report to PDF</h3>
                    <p class="text-sm text-gray-500 leading-[1.7]">
                        Export a complete health report — all predictions, scores, diet menus, and activity plans — as a structured PDF you can bring to your doctor.
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════
                 ROW 3, CARD F: Clinical Metrics
                 Spans 2 columns (matches row 1 left card width)
            ═══════════════════════════════════════ --}}
            <div class="reveal md:col-span-2 lg:col-span-2 relative rounded-2xl border border-gray-200 bg-gray-50 overflow-hidden group flex flex-col"
                 style="transition-delay: 0.3s;">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-teal opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="p-7 flex flex-col flex-1">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center mb-4 shadow-sm shrink-0">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-[1.25rem] leading-snug text-gray-900 mb-2">Analysis from Real Clinical Metrics</h3>
                    <p class="text-sm text-gray-500 leading-[1.7] mb-5 max-w-[520px]">
                        No guesswork. Predictions are derived directly from the biomarkers clinicians actually use, not self-reported feelings or lifestyle surveys.
                    </p>

                    {{-- Pill tags — uniform height & padding, consistent gap --}}
                    <div class="flex flex-wrap gap-2 mt-auto">
                        @foreach ([
                            'Blood Glucose (mg/dL)',
                            'Systolic Blood Pressure',
                            'BMI',
                            'HbA1c (%)',
                            'Urine Protein',
                            'Serum Creatinine',
                            'Cholesterol',
                            'Age & Sex',
                        ] as $metric)
                            <span class="inline-flex items-center h-8 text-xs font-medium text-gray-700 bg-white border border-gray-200 px-3.5 rounded-full shadow-sm whitespace-nowrap">
                                {{ $metric }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>{{-- /grid --}}
    </div>
</section>
