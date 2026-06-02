{{-- Sticky Navbar with Alpine.js scroll + mobile toggle --}}
<nav
    x-data="{
        mobileOpen: false,
        scrolled: false,
        init() {
            this.scrolled = window.scrollY > 20;
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 20;
            }, { passive: true });
        }
    }"
    :class="scrolled
        ? 'bg-white/85 backdrop-blur-xl shadow-[0_1px_0_rgba(13,110,110,0.06),0_1px_3px_rgba(13,110,110,0.06)]'
        : 'bg-transparent'"
    class="fixed top-0 left-0 right-0 z-50 h-[72px] transition-all duration-300"
    id="navbar"
>
    <div class="max-w-[1200px] mx-auto px-6 flex items-center justify-between h-full">

        {{-- Logo --}}
        <a href="#" class="flex items-center gap-3 font-display text-xl text-gray-800 whitespace-nowrap">
            <span class="flex items-center justify-center w-9 h-9 bg-gradient-to-br from-brand-teal-light to-brand-teal rounded-lg shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 4v16M4 12h16"/>
                    <path d="M5.5 5.5l13 13M18.5 5.5l-13 13" opacity="0.3"/>
                </svg>
            </span>
            Didaction Healthcare
        </a>

        {{-- Desktop nav links --}}
        <ul class="hidden lg:flex items-center gap-8">
            @foreach ([
                ['#home', 'Home'],
                ['#how-it-works', 'How It Works'],
                ['#features', 'Features'],
                ['#testimonials', 'Testimonials'],
                ['#get-started', 'Get Started'],
            ] as [$href, $label])
                <li>
                    <a href="{{ $href }}"
                       class="text-sm font-medium text-gray-500 hover:text-brand-teal transition-colors duration-300
                              relative after:content-[''] after:absolute after:left-0 after:bottom-[-4px]
                              after:w-0 after:h-0.5 after:bg-brand-teal after:rounded-full
                              after:transition-all after:duration-300
                              hover:after:w-full">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Desktop CTA --}}
        <a href="/get-started" class="hidden lg:inline-flex btn-primary">
            Check My Health
        </a>

        {{-- Mobile hamburger --}}
        <button
            @click="mobileOpen = !mobileOpen"
            :aria-expanded="mobileOpen"
            class="flex lg:hidden flex-col gap-[5px] w-7 py-1 cursor-pointer"
            aria-label="Toggle menu"
        >
            <span class="block h-0.5 bg-gray-700 rounded-full transition-all duration-300"
                  :class="mobileOpen ? 'rotate-45 translate-y-[7px]' : ''"></span>
            <span class="block h-0.5 bg-gray-700 rounded-full transition-all duration-300"
                  :class="mobileOpen ? 'opacity-0' : ''"></span>
            <span class="block h-0.5 bg-gray-700 rounded-full transition-all duration-300"
                  :class="mobileOpen ? '-rotate-45 -translate-y-[7px]' : ''"></span>
        </button>
    </div>

    {{-- Mobile menu --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        @click.away="mobileOpen = false"
        class="lg:hidden bg-white/97 backdrop-blur-xl px-6 py-8 flex flex-col gap-5 shadow-lg"
    >
        @foreach ([
            ['#home', 'Home'],
            ['#how-it-works', 'How It Works'],
            ['#features', 'Features'],
            ['#testimonials', 'Testimonials'],
            ['#get-started', 'Get Started'],
        ] as [$href, $label])
            <a href="{{ $href }}"
               @click="mobileOpen = false"
               class="text-lg font-medium text-gray-600 hover:text-brand-teal transition-colors">
                {{ $label }}
            </a>
        @endforeach
        <a href="/get-started" @click="mobileOpen = false" class="btn-primary text-center">
            Check My Health
        </a>
    </div>
</nav>
