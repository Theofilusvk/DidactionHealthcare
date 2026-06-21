{{-- Hero Section --}}
<section class="relative min-h-screen flex items-center pt-[152px] pb-28 overflow-hidden" id="home">

    {{-- Subtle light background --}}
    <div class="absolute inset-0 z-0 bg-[linear-gradient(180deg,white_0%,#F0FAFA_60%,white_100%)]"></div>

    {{-- Grid texture --}}
    <div class="absolute inset-0 z-[1] opacity-[0.025]
                bg-[linear-gradient(#0D6E6E_1px,transparent_1px),linear-gradient(90deg,#0D6E6E_1px,transparent_1px)]
                bg-[size:60px_60px]">
    </div>

    <div class="relative z-[2] max-w-[1200px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

        {{-- Left: Content --}}
        <div class="max-w-[560px] lg:max-w-none text-center lg:text-left mx-auto lg:mx-0">

            {{-- Eyebrow badge --}}
            <div class="reveal inline-flex items-center gap-2 bg-brand-teal-pale text-brand-teal text-xs font-semibold px-4 py-1.5 rounded-full border border-brand-teal/10 mb-8">
                <span class="w-1.5 h-1.5 bg-brand-teal rounded-full animate-pulse-dot"></span>
                XGBoost-Powered Clinical Risk Analysis
            </div>

            {{-- Headline --}}
            <h1 class="reveal font-display text-[clamp(2.5rem,5vw+1rem,4.25rem)] leading-[1.12] text-gray-900 mb-6"
                style="transition-delay: 0.1s;">
                Know Your Health Risks.<br/>
                <span class="text-brand-teal">Before They Know You.</span>
            </h1>

            {{-- Subtitle — concrete, not vague --}}
            <p class="reveal text-lg text-gray-500 leading-relaxed mb-10 max-w-[500px] mx-auto lg:mx-0"
               style="transition-delay: 0.2s;">
                Input your clinical metrics — blood glucose, blood pressure, BMI, HbA1c — and get instant risk predictions for <strong class="text-gray-700 font-semibold">5 chronic diseases</strong>, a composite Health Score, plus personalized diet and activity recommendations.
            </p>

            {{-- CTAs --}}
            <div class="reveal flex flex-wrap gap-4 justify-center lg:justify-start"
                 style="transition-delay: 0.3s;">
                <a href="#get-started" class="btn-primary-lg">
                    Start Free Assessment
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#how-it-works" class="btn-secondary-lg">
                    See How It Works
                </a>
            </div>

            {{-- Trust signals --}}
            <div class="reveal flex flex-wrap items-center gap-6 mt-10 justify-center lg:justify-start text-xs text-gray-400"
                 style="transition-delay: 0.4s;">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-brand-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    5 Disease Models
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-brand-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Clinical Biomarkers
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-brand-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    PDF Export
                </span>
            </div>
        </div>

        {{-- Right: Real Analysis Preview Card --}}
        <div class="reveal order-first lg:order-last w-full flex justify-center"
             style="transition-delay: 0.25s;">
            <div class="relative flex justify-center items-start w-full max-w-[400px]">
                {{-- Floating "Analisis Selesai" accent badge --}}
                <div class="hidden lg:flex absolute -top-3 -left-4 z-10
                            items-center gap-1.5
                            bg-white border border-teal-200 shadow-md
                            text-[11px] font-semibold text-teal-700
                            px-3 py-1.5 rounded-full
                            animate-float-accent">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#00A19D] animate-pulse-dot"></span>
                    Analisis Selesai
                </div>

                <img src="{{ asset('images/hero-preview.png') }}"
                     alt="Hasil Analisis Medis Didaction"
                     class="w-full rounded-3xl shadow-[0_20px_50px_rgba(20,184,166,0.15)] border border-teal-500/10">
            </div>
        </div>
    </div>
</section>
