{{-- How It Works — 3-Step Process --}}
<section class="section-padding bg-[#F7F8F9] relative" id="how-it-works">

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
                Three Simple Steps to a Healthier You
            </h2>
            <p class="reveal text-lg text-gray-500 max-w-[600px] mx-auto"
               style="transition-delay: 0.2s;">
                No complex sign-ups or lengthy questionnaires. Our AI works with what you share.
            </p>
        </div>

        {{-- Steps --}}
        <div class="relative grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-6">

            {{-- Connecting line (desktop) --}}
            <div class="hidden md:block absolute top-12 left-[calc(16.666%+20px)] right-[calc(16.666%+20px)] h-0.5
                        bg-gradient-to-r from-brand-teal-pale via-brand-teal to-brand-teal-pale z-[1]"></div>

            @php
                $steps = [
                    ['📝', 'Share Your Condition', 'Input your health status, symptoms, or medical background in your own words — no medical jargon required.'],
                    ['🧠', 'AI Analyzes', 'Our system processes your data and maps it to evidence-based health guidelines and nutritional science.'],
                    ['✨', 'Get Your Plan', 'Receive personalized lifestyle habits and meal recommendations tailored specifically to you.'],
                ];
            @endphp

            @foreach ($steps as $i => [$icon, $title, $text])
                <div class="reveal text-center relative z-[2] max-w-[400px] mx-auto md:max-w-none"
                     style="transition-delay: {{ ($i + 1) * 0.1 }}s;">

                    {{-- Icon circle --}}
                    <div class="group">
                        <div class="w-24 h-24 rounded-full bg-white border-2 border-brand-teal-pale
                                    flex items-center justify-center mx-auto mb-6
                                    shadow-sm relative
                                    transition-all duration-300
                                    group-hover:border-brand-teal group-hover:shadow-md group-hover:-translate-y-1">

                            {{-- Number badge --}}
                            <span class="absolute -top-1 -right-1 w-7 h-7
                                         bg-gradient-to-br from-brand-teal-light to-brand-teal
                                         rounded-full text-white text-xs font-bold
                                         flex items-center justify-center
                                         border-[3px] border-[#F7F8F9]">
                                {{ $i + 1 }}
                            </span>

                            <span class="text-4xl">{{ $icon }}</span>
                        </div>
                    </div>

                    <h3 class="font-display text-[clamp(1.15rem,1.5vw+0.3rem,1.5rem)] text-gray-800 mb-2">
                        {{ $title }}
                    </h3>
                    <p class="text-sm text-gray-500 max-w-[280px] mx-auto leading-relaxed">
                        {{ $text }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
