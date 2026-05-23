{{-- Hero Section --}}
<section class="relative min-h-screen flex items-center pt-[calc(72px+6rem)] pb-32 overflow-hidden" id="home">

    {{-- Background gradient mesh --}}
    <div class="absolute inset-0 z-0
                bg-[radial-gradient(ellipse_80%_60%_at_20%_40%,rgba(13,110,110,0.06)_0%,transparent_70%),radial-gradient(ellipse_60%_50%_at_80%_30%,rgba(143,187,153,0.08)_0%,transparent_70%),radial-gradient(ellipse_50%_40%_at_50%_80%,rgba(14,133,133,0.04)_0%,transparent_70%),linear-gradient(180deg,white_0%,#F0FAFA_50%,white_100%)]">
    </div>

    {{-- Geometric medical pattern --}}
    <div class="absolute inset-0 z-[1] opacity-[0.025]
                bg-[linear-gradient(#0D6E6E_1px,transparent_1px),linear-gradient(90deg,#0D6E6E_1px,transparent_1px)]
                bg-[size:60px_60px]">
    </div>

    {{-- Floating orbs --}}
    <div class="absolute w-[400px] h-[400px] rounded-full blur-[60px] z-[1] animate-orb bg-brand-teal/7 top-[10%] -left-[5%]"></div>
    <div class="absolute w-[300px] h-[300px] rounded-full blur-[60px] z-[1] animate-orb bg-brand-sage/10 bottom-[10%] -right-[5%]" style="animation-delay: -3s;"></div>
    <div class="absolute w-[200px] h-[200px] rounded-full blur-[60px] z-[1] animate-orb bg-brand-teal-light/8 top-1/2 left-1/2" style="animation-delay: -5s;"></div>

    <div class="relative z-[2] max-w-[1200px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

        {{-- Content --}}
        <div class="max-w-[560px] lg:max-w-none text-center lg:text-left mx-auto lg:mx-0">

            {{-- Badge --}}
            <div class="reveal inline-flex items-center gap-2 bg-brand-teal-pale text-brand-teal text-xs font-semibold px-4 py-1.5 rounded-full border border-brand-teal/10 mb-8">
                <span class="w-1.5 h-1.5 bg-brand-teal rounded-full animate-pulse-dot"></span>
                AI-Powered Health Platform
            </div>

            {{-- Headline --}}
            <h1 class="reveal font-display text-[clamp(2.5rem,5vw+1rem,4.25rem)] leading-[1.15] text-gray-900 mb-6"
                style="transition-delay: 0.1s;">
                Tell Us Your Health Condition.<br/>
                <span class="text-brand-teal">We'll Guide Your Life.</span>
            </h1>

            {{-- Subtitle --}}
            <p class="reveal text-lg text-gray-500 leading-relaxed mb-10 max-w-[480px] mx-auto lg:mx-0"
               style="transition-delay: 0.2s;">
                Get AI-powered recommendations for your daily lifestyle and nutrition — tailored to exactly how you feel today.
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
        </div>

        {{-- Floating health card visual --}}
        <div class="reveal relative flex justify-center items-center order-first lg:order-last"
             style="transition-delay: 0.2s;">

            {{-- Top floating card --}}
            <div class="hidden lg:flex absolute top-0 -right-5 z-[3]
                        bg-white rounded-xl px-4 py-2.5 shadow-lg items-center gap-2
                        text-xs font-semibold text-gray-700 border border-gray-100
                        animate-float-accent">
                <span class="w-7 h-7 rounded-lg bg-brand-sage-pale flex items-center justify-center text-sm">🥗</span>
                Diet Plan Updated
            </div>

            {{-- Main dashboard card --}}
            <div class="card-glass rounded-3xl p-8 w-full max-w-[420px] animate-float">

                {{-- Card header --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-teal-pale to-brand-sage-pale rounded-full flex items-center justify-center text-xl">
                        👤
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-sm text-gray-800">Sarah Mitchell</div>
                        <div class="text-xs text-gray-400">Health Dashboard</div>
                    </div>
                    <div class="flex items-center gap-1 text-xs font-semibold text-brand-teal">
                        <span class="w-2 h-2 bg-brand-teal rounded-full animate-pulse-dot"></span>
                        Active
                    </div>
                </div>

                {{-- Stats row --}}
                <div class="grid grid-cols-3 gap-3 mb-6 hero-stats-trigger">
                    <div class="bg-white rounded-xl p-3.5 text-center border border-gray-100">
                        <span class="block font-display text-xl text-gray-800 stat-val-1">92</span>
                        <span class="text-xs text-gray-400 mt-0.5">Health Score</span>
                    </div>
                    <div class="bg-white rounded-xl p-3.5 text-center border border-gray-100">
                        <span class="block font-display text-xl text-gray-800 stat-val-2">7.2h</span>
                        <span class="text-xs text-gray-400 mt-0.5">Avg Sleep</span>
                    </div>
                    <div class="bg-white rounded-xl p-3.5 text-center border border-gray-100">
                        <span class="block font-display text-xl text-gray-800 stat-val-3">1840</span>
                        <span class="text-xs text-gray-400 mt-0.5">Cal Target</span>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="mb-4">
                    <div class="flex justify-between text-xs mb-2">
                        <span class="font-semibold text-gray-700">Weekly Goal Progress</span>
                        <span class="font-semibold text-brand-teal">78%</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-brand-teal to-brand-sage rounded-full animate-progress-fill"
                             style="width: 78%;"></div>
                    </div>
                </div>

                {{-- Recommendation --}}
                <div class="bg-brand-teal-pale rounded-xl p-4 flex items-start gap-3 border border-brand-teal/8">
                    <span class="text-lg shrink-0 mt-0.5">💡</span>
                    <div class="text-xs text-brand-teal leading-relaxed">
                        <strong class="block font-semibold mb-0.5">Today's Recommendation</strong>
                        Add leafy greens & omega-3 rich foods. Consider a 20-min evening walk for better sleep quality.
                    </div>
                </div>
            </div>

            {{-- Bottom floating card --}}
            <div class="hidden lg:flex absolute bottom-5 -left-3 z-[3]
                        bg-white rounded-xl px-4 py-2.5 shadow-lg items-center gap-2
                        text-xs font-semibold text-gray-700 border border-gray-100
                        animate-float-accent" style="animation-delay: -2.5s;">
                <span class="w-7 h-7 rounded-lg bg-brand-teal-pale flex items-center justify-center text-sm">📊</span>
                Progress: +12%
            </div>
        </div>
    </div>
</section>
