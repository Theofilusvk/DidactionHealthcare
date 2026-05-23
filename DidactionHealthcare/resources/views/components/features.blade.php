{{-- Features Section --}}
<section class="section-padding relative" id="features">
    <div class="max-w-[1200px] mx-auto px-6">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-4">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                Features
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-800 mb-4"
                style="transition-delay: 0.1s;">
                Everything You Need for Better Health
            </h2>
            <p class="reveal text-lg text-gray-500 max-w-[600px] mx-auto"
               style="transition-delay: 0.2s;">
                Powered by advanced AI, designed with your well-being in mind.
            </p>
        </div>

        {{-- Feature grid --}}
        @php
            $features = [
                ['🥗', 'Personalized Diet Plans', 'Receive meal plans curated to your health conditions, dietary preferences, and nutritional needs.'],
                ['🏃', 'Lifestyle & Activity Guidance', 'Get daily movement and lifestyle recommendations that fit your current health status and energy levels.'],
                ['🩺', 'Condition-Based Insights', 'Understand how your conditions interact and receive targeted insights backed by clinical evidence.'],
                ['📊', 'Health Progress Tracking', 'Monitor your improvements over time with visual dashboards and milestone celebrations.'],
                ['🔒', 'Private & Secure Data', 'Your health data is encrypted and never shared. We follow strict healthcare privacy standards.'],
                ['⚡', 'Instant AI Recommendations', 'Get actionable health recommendations in seconds, not days. Our AI works in real-time.'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($features as $i => [$icon, $title, $text])
                <div class="reveal card-glass card-glass-hover rounded-2xl p-8 relative overflow-hidden group"
                     style="transition-delay: {{ ($i + 1) * 0.08 }}s;">

                    {{-- Top gradient accent line --}}
                    <div class="absolute top-0 left-0 right-0 h-[3px]
                                bg-gradient-to-r from-brand-teal to-brand-sage
                                opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    {{-- Icon --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-6
                                bg-brand-teal-pale border border-brand-teal/6">
                        {{ $icon }}
                    </div>

                    <h3 class="font-display text-[clamp(1.15rem,1.5vw+0.3rem,1.5rem)] text-gray-800 mb-2">
                        {{ $title }}
                    </h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ $text }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
