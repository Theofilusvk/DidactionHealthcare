{{-- Testimonials Section --}}
<section class="section-padding bg-white relative" id="testimonials">
    <div class="max-w-[1200px] mx-auto px-6">

        {{-- Section Header --}}
        <div class="text-center mb-16">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-4">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                Testimonials
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-800 mb-4"
                style="transition-delay: 0.1s;">
                Real People, Real Results
            </h2>
            <p class="reveal text-lg text-gray-500 max-w-[600px] mx-auto"
               style="transition-delay: 0.2s;">
                See how Didaction Healthcare is helping people take control of their well-being.
            </p>
        </div>

        {{-- Testimonials Container with Alpine.js Carousel controls --}}
        @php
            $testimonials = [
                [
                    'name' => 'James Rodriguez',
                    'condition' => 'Managing Type 2 Diabetes',
                    'quote' => 'The dietary recommendations were spot-on for my diabetes management. My blood sugar levels have been more stable than ever in the past three months.',
                    'avatar' => '👨',
                    'avatar_bg' => 'from-brand-teal-pale to-[#C5E8E8]',
                ],
                [
                    'name' => 'Priya Sharma',
                    'condition' => 'Managing Chronic Fatigue',
                    'quote' => 'I was skeptical at first, but the personalized activity plan helped me manage my chronic fatigue. I finally have energy to enjoy my evenings again.',
                    'avatar' => '👩',
                    'avatar_bg' => 'from-brand-sage-pale to-[#D4E9D7]',
                ],
                [
                    'name' => 'Michael Chen',
                    'condition' => 'Managing Hypertension',
                    'quote' => 'After my hypertension diagnosis, I felt lost. Didaction gave me a clear, simple plan. My doctor was impressed with my progress at the next checkup.',
                    'avatar' => '👨‍🦳',
                    'avatar_bg' => 'from-[#FDE8E3] to-[#FADDD5]',
                ]
            ];
        @endphp

        {{-- Desktop: 3-column Grid, Mobile: Interactive Carousel --}}
        <div x-data="{
                activeTab: 0,
                total: {{ count($testimonials) }},
                next() { this.activeTab = (this.activeTab + 1) % this.total; },
                prev() { this.activeTab = (this.activeTab - 1 + this.total) % this.total; }
             }"
             class="relative w-full max-w-[1000px] mx-auto">

            {{-- Cards Wrapper --}}
            <div class="hidden md:grid grid-cols-3 gap-6">
                @foreach ($testimonials as $i => $item)
                    <div class="reveal bg-white border border-gray-100 rounded-2xl p-8 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 relative"
                         style="transition-delay: {{ ($i + 1) * 0.1 }}s;">

                        {{-- Rating --}}
                        <div class="flex gap-0.5 mb-4 text-[#F5B731] text-base">
                            @for ($star = 0; $star < 5; $star++)
                                <span>★</span>
                            @endfor
                        </div>

                        {{-- Quote --}}
                        <p class="text-gray-600 italic leading-relaxed mb-8 relative z-10 before:content-['\201C'] before:font-display before:text-5xl before:text-brand-teal-pale before:absolute before:-top-3 before:-left-1 before:line-height-0">
                            {{ $item['quote'] }}
                        </p>

                        {{-- Author --}}
                        <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br {{ $item['avatar_bg'] }} flex items-center justify-center text-xl shrink-0">
                                {{ $item['avatar'] }}
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-gray-800">{{ $item['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $item['condition'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mobile Carousel Slide View --}}
            <div class="block md:hidden overflow-hidden relative px-4">
                <div class="flex transition-transform duration-300 ease-out"
                     :style="'transform: translateX(-' + (activeTab * 100) + '%)'">
                    @foreach ($testimonials as $i => $item)
                        <div class="w-full shrink-0 px-2">
                            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-md relative">
                                <div class="flex gap-0.5 mb-4 text-[#F5B731] text-base">
                                    @for ($star = 0; $star < 5; $star++)
                                        <span>★</span>
                                    @endfor
                                </div>
                                <p class="text-gray-600 italic leading-relaxed mb-6 relative z-10 before:content-['\201C'] before:font-display before:text-5xl before:text-brand-teal-pale before:absolute before:-top-3 before:-left-1">
                                    {{ $item['quote'] }}
                                </p>
                                <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                                    <div class="w-11 h-11 rounded-full bg-gradient-to-br {{ $item['avatar_bg'] }} flex items-center justify-center text-xl shrink-0">
                                        {{ $item['avatar'] }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-800">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $item['condition'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Carousel Indicator dots --}}
                <div class="flex justify-center gap-2 mt-6">
                    @foreach ($testimonials as $i => $item)
                        <button @click="activeTab = {{ $i }}"
                                class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                                :class="activeTab === {{ $i }} ? 'bg-brand-teal w-6' : 'bg-gray-300'">
                        </button>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>
