{{-- Footer Section --}}
<footer class="bg-brand-dark text-gray-400 py-16 md:py-24" role="contentinfo">
    <div class="max-w-[1200px] mx-auto px-6">
        
        {{-- Footer Top Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-12 pb-16 border-b border-white/8">
            {{-- Brand info --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center gap-3 font-display text-lg text-gray-200">
                    <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-teal-light to-brand-teal flex items-center justify-center">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M12 4v16M4 12h16"/>
                        </svg>
                    </span>
                    Didaction Healthcare
                </div>
                <p class="text-sm text-gray-400 max-w-[300px] leading-relaxed">
                    Your Health, Understood. Your Life, Transformed.
                </p>
                {{-- Social Icons --}}
                <div class="flex gap-3">
                    @foreach ([
                        ['Twitter', 'M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z'],
                        ['LinkedIn', 'M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z M2 9h4v12H2z M4 4a2 2 0 1 0 0 4 2 2 0 1 0 0-4z'],
                        ['Instagram', 'M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z M17.5 6.5h.01']
                    ] as [$label, $path])
                        <a href="#" class="w-9 h-9 rounded-full bg-white/6 flex items-center justify-center group hover:bg-brand-teal transition-all duration-300 hover:-translate-y-0.5" aria-label="{{ $label }}">
                            <svg class="w-4 h-4 fill-gray-400 group-hover:fill-white transition-colors" viewBox="0 0 24 24">
                                <path d="{{ $path }}"></path>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Column 1: Company --}}
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-200 mb-6">Company</h4>
                <ul class="space-y-3.5 text-sm">
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">About Us</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Careers</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Press Kit</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Blog</a></li>
                </ul>
            </div>

            {{-- Column 2: Support --}}
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-200 mb-6">Support</h4>
                <ul class="space-y-3.5 text-sm">
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Help Center</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Contact Us</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">FAQ</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Community</a></li>
                </ul>
            </div>

            {{-- Column 3: Legal --}}
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-200 mb-6">Legal</h4>
                <ul class="space-y-3.5 text-sm">
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">Cookie Policy</a></li>
                    <li><a href="#" class="hover:text-brand-teal-lighter transition-colors">HIPAA Compliance</a></li>
                </ul>
            </div>
        </div>

        {{-- Footer Bottom --}}
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-8 text-xs text-center md:text-left">
            <span>© 2026 Didaction Healthcare. All rights reserved.</span>
            <span class="text-gray-500 max-w-[400px] leading-relaxed italic md:text-right">
                ⚕️ Didaction Healthcare is not a substitute for professional medical advice, diagnosis, or treatment. Always consult your physician.
            </span>
        </div>

    </div>
</footer>
