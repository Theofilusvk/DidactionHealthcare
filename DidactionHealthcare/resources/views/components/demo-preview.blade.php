{{-- Demo Preview Section --}}
<section class="section-padding bg-[#F7F8F9] relative" id="get-started">
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

    <div class="max-w-[1200px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        {{-- Left Content --}}
        <div class="max-w-[480px] text-center lg:text-left mx-auto lg:mx-0">
            <div class="reveal inline-flex items-center gap-2 text-sm font-semibold tracking-wider uppercase text-brand-teal mb-4">
                <span class="inline-block w-6 h-0.5 bg-brand-teal rounded-full"></span>
                Try It Yourself
            </div>
            <h2 class="reveal font-display text-[clamp(1.75rem,3vw+0.5rem,2.75rem)] leading-[1.15] text-gray-800 mb-4"
                style="transition-delay: 0.1s;">
                See What Personalized Health Looks Like
            </h2>
            <p class="reveal text-lg text-gray-500 mb-8"
               style="transition-delay: 0.2s;">
                Describe your condition in your own words. Our AI understands natural language and provides meaningful, actionable guidance.
            </p>

            <div class="reveal space-y-4 text-left"
                 style="transition-delay: 0.3s;">
                @foreach ([
                    'No sign-up required to preview',
                    'Results in under 30 seconds',
                    '100% private — data never stored'
                ] as $item)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-brand-teal shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-600">{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right Interactive Card --}}
        <div class="reveal w-full max-w-[500px] mx-auto"
             style="transition-delay: 0.2s;">
            <div x-data="{
                userInput: 'I\'ve been experiencing frequent fatigue and difficulty sleeping. I also have mild hypertension and want to improve my diet.',
                loading: false,
                buttonText: 'Get Recommendations',
                clickedTags: [],
                addTag(tag) {
                    if (!this.userInput.includes(tag)) {
                        this.userInput += (this.userInput ? ' ' : '') + tag;
                    }
                    if (!this.clickedTags.includes(tag)) {
                        this.clickedTags.push(tag);
                    }
                },
                submitDemo() {
                    this.loading = true;
                    this.buttonText = 'Analyzing...';
                    setTimeout(() => {
                        this.loading = false;
                        this.buttonText = 'Get Recommendations';
                    }, 1500);
                }
            }" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                {{-- Card Header --}}
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-sage"></span>
                    <span class="text-xs font-semibold text-gray-500 ml-2">Health Assessment Preview</span>
                </div>

                {{-- Card Body --}}
                <div class="p-6 md:p-8">
                    {{-- Text Area --}}
                    <label for="demo-condition" class="sr-only">Health Condition Description</label>
                    <textarea
                        id="demo-condition"
                        x-model="userInput"
                        class="w-full min-h-[100px] border 1.5 border-gray-200 rounded-xl p-4 text-sm text-gray-600 resize-none outline-none focus:border-brand-teal focus:bg-white bg-gray-50 transition-colors duration-200"
                        placeholder="Describe your current health condition..."
                    ></textarea>

                    {{-- Dynamic Tags --}}
                    <div class="flex flex-wrap gap-2 my-4">
                        @foreach (['#Fatigue', '#Hypertension', '#Sleep Issues', '#Diet'] as $tag)
                            <button
                                type="button"
                                @click="addTag('{{ $tag }}')"
                                :class="clickedTags.includes('{{ $tag }}') ? 'bg-brand-teal text-white border-brand-teal' : 'bg-brand-teal-pale text-brand-teal hover:bg-brand-teal hover:text-white border-transparent'"
                                class="px-3 py-1 text-xs font-medium rounded-full border transition-all duration-200"
                            >
                                {{ $tag }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Submit Button --}}
                    <button
                        type="button"
                        @click="submitDemo"
                        :disabled="loading"
                        class="w-full btn-primary text-center flex items-center justify-center gap-2 transition-all duration-300"
                        :class="loading ? 'opacity-70 pointer-events-none' : ''"
                    >
                        <span x-text="buttonText">Get Recommendations</span>
                        <svg x-show="!loading" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>

                    {{-- Results Preview --}}
                    <div class="mt-6 pt-6 border-t border-gray-100 relative">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Your Personalized Results</div>

                        <div class="grid grid-cols-2 gap-3 filter blur-[3px] select-none pointer-events-none transition-all duration-300"
                             :class="loading ? 'opacity-30' : ''">
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div class="text-lg mb-1">🥗</div>
                                <div class="text-xs font-bold text-gray-700 mb-1">Diet Plan</div>
                                <div class="text-[11px] text-gray-400 leading-normal">Focus on potassium-rich foods: bananas, spinach, sweet potatoes. Reduce sodium intake.</div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div class="text-lg mb-1">🏃</div>
                                <div class="text-xs font-bold text-gray-700 mb-1">Activity Plan</div>
                                <div class="text-[11px] text-gray-400 leading-normal">30-min moderate walking daily. Add yoga or stretching before bed to improve sleep.</div>
                            </div>
                        </div>

                        {{-- Blurred Overlay Button --}}
                        <div class="absolute inset-0 flex items-center justify-center z-10">
                            <a href="#get-started" class="bg-white hover:bg-gray-50 border border-gray-200 rounded-full px-4 py-2 text-xs font-semibold text-brand-teal shadow-md transition-all duration-200">
                                🔒 Sign up to see full results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
