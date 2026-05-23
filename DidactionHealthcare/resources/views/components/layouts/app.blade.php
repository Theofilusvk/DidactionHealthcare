<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Didaction Healthcare — AI-Powered Personalized Health Guidance' }}</title>
    <meta name="description" content="{{ $description ?? 'Didaction Healthcare analyzes your health condition and provides personalized lifestyle and dietary recommendations powered by AI.' }}" />
    <meta name="author" content="Didaction Healthcare" />
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🩺</text></svg>" />

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet" />

    {{-- Alpine.js Intersect Plugin + Alpine --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Vite: Tailwind CSS + App JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-cream text-brand-dark antialiased font-sans overflow-x-hidden">

    {{ $slot }}

    {{-- Robust Scroll Reveal and Stats Counters Initialization --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Scroll Reveal Observer
            const revealElements = document.querySelectorAll('.reveal');
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: 0.1, 
                rootMargin: '0px 0px -20px 0px' 
            });
            revealElements.forEach(el => revealObserver.observe(el));

            // Stats Counter Observer
            const statsElement = document.querySelector('.hero-stats-trigger');
            if (statsElement) {
                const animateCounter = (el, target, suffix = '') => {
                    const duration = 1500;
                    const start = performance.now();
                    const step = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
                        const current = Math.round(eased * target);
                        el.textContent = current + suffix;
                        if (progress < 1) requestAnimationFrame(step);
                    };
                    requestAnimationFrame(step);
                };

                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const val1 = entry.target.querySelector('.stat-val-1');
                            const val2 = entry.target.querySelector('.stat-val-2');
                            const val3 = entry.target.querySelector('.stat-val-3');
                            
                            if (val1) animateCounter(val1, 92);
                            if (val2) {
                                const dur = 1500;
                                const startTime = performance.now();
                                const step = (now) => {
                                    const p = Math.min((now - startTime) / dur, 1);
                                    const eased = 1 - Math.pow(1 - p, 3);
                                    val2.textContent = (eased * 7.2).toFixed(1) + 'h';
                                    if (p < 1) requestAnimationFrame(step);
                                };
                                requestAnimationFrame(step);
                            }
                            if (val3) animateCounter(val3, 1840);
                            statsObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                statsObserver.observe(statsElement);
            }
        });
    </script>
</body>
</html>
