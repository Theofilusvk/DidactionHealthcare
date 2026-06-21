{{-- How It Works — 4-Step Process --}}
<section class="section-padding bg-[#F7F8F9] relative" id="how-it-works" style="scroll-margin-top: 80px;">

    {{-- Top divider --}}
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

    <div class="max-w-[1200px] mx-auto px-6">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-4">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                How It Works
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-800 mb-4"
                style="transition-delay: 0.1s;">
                From Biomarkers to Insights<br/>in Four Steps.
            </h2>
            <p class="reveal text-lg text-gray-500 max-w-[540px] mx-auto"
               style="transition-delay: 0.2s;">
                A clear, transparent pipeline — no black box, no vague advice.
            </p>
        </div>

        {{-- Steps: vertical on mobile, horizontal with connecting line on desktop --}}
        <div class="relative">

            {{-- Connecting line (desktop only) --}}
            <div class="hidden lg:block absolute top-[44px] left-[calc(12.5%+44px)] right-[calc(12.5%+44px)] h-px bg-gray-200 z-[1]">
                <div class="absolute inset-0 bg-gradient-to-r from-brand-teal-pale via-brand-teal to-brand-teal-pale h-full"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
                @php
                    $steps = [
                        [
                            'num' => '01',
                            'title' => 'Enter Clinical Metrics',
                            'desc' => 'Input real biomarkers: blood glucose, systolic BP, BMI, HbA1c, urine protein, and more. No narrative descriptions — just the numbers that matter.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                        ],
                        [
                            'num' => '02',
                            'title' => 'XGBoost Analyzes',
                            'desc' => 'Five independently trained XGBoost models process your metrics simultaneously, each predicting risk for one disease using clinically validated feature sets.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>',
                        ],
                        [
                            'num' => '03',
                            'title' => 'View Score & Predictions',
                            'desc' => 'Receive your Health Score, risk percentages and HIGH / MODERATE / LOW levels for all 5 diseases, plus personalized diet and activity recommendations.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                        ],
                        [
                            'num' => '04',
                            'title' => 'Export Full PDF Report',
                            'desc' => 'Download a structured PDF containing all predictions, your Health Score, diet menus, and activity plan — formatted to share with a healthcare professional.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                        ],
                    ];
                @endphp

                @foreach ($steps as $i => $step)
                    <div class="reveal relative z-[2] text-center lg:text-left"
                         style="transition-delay: {{ ($i + 1) * 0.1 }}s;">

                        {{-- Number circle --}}
                        <div class="relative inline-flex mb-6">
                            <div class="w-[88px] h-[88px] rounded-full bg-white border border-gray-200 shadow-sm
                                        flex items-center justify-center mx-auto lg:mx-0
                                        transition-all duration-300 group-hover:border-brand-teal">
                                <svg class="w-6 h-6 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    {!! $step['icon'] !!}
                                </svg>
                            </div>
                            {{-- Step number badge --}}
                            <span class="absolute -top-1 -right-1 lg:right-auto lg:-left-1 w-7 h-7
                                         bg-brand-teal rounded-full text-white text-xs font-bold
                                         flex items-center justify-center
                                         border-[3px] border-[#F7F8F9]">
                                {{ sprintf('%02d', $i + 1) }}
                            </span>
                        </div>

                        <h3 class="font-display text-[1.2rem] text-gray-800 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed max-w-[260px] mx-auto lg:mx-0">{{ $step['desc'] }}</p>

                        {{-- Vertical connector (mobile/sm only) --}}
                        @if ($i < count($steps) - 1)
                            <div class="lg:hidden flex justify-center mt-6">
                                <div class="w-px h-8 bg-gray-200"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
