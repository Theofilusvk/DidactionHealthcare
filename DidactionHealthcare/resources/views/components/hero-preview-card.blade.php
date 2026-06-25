{{--
    Hero Analysis Preview Card
    ─────────────────────────
    Static mockup that faithfully mirrors the real results page output.
    All values, badge classes, and typography are taken directly from
    prediction-form.blade.php so both pages look identical.

    Customisable PHP block at the top — swap values here to update the preview.
--}}

@php
    /* ── Preview data ── change these to update the mockup ────────────── */
    $previewScore    = 39;
    $previewStatus   = 'Bahaya';       // 'Aman' | 'Peringatan' | 'Bahaya'
    $highestRisk     = 'Hypertension (60.9%)';

    /* Top 5 predictions shown in the card */
    $previewDiseases = [
        ['label' => 'Hypertension',           'pct' => '60.9%', 'level' => 'High'],
        ['label' => 'Heart Disease',           'pct' => '54.4%', 'level' => 'Moderate'],
        ['label' => 'Chronic Kidney Disease',  'pct' => '52.3%', 'level' => 'Moderate'],
        ['label' => 'Diabetes',                'pct' => '50.9%', 'level' => 'Moderate'],
        ['label' => 'Stroke',                  'pct' => '49.8%', 'level' => 'Moderate'],
    ];

    /* Recommendation snippet */
    $previewRec = [
        'title'       => 'Pola Makan Seimbang',
        'description' => 'Isi setengah piring dengan sayuran dan buah, batasi gula di bawah 25 gram per hari.',
        'priority'    => 'Sedang',   // 'Tinggi' | 'Sedang' | 'Rendah'
    ];
    /* ──────────────────────────────────────────────────────────────────── */

    /* Status badge — mirrors exact classes from prediction-form.blade.php */
    $statusBadge  = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
    $statusBullet = 'bg-emerald-500';
    if ($previewStatus === 'Peringatan') {
        $statusBadge  = 'bg-amber-50 text-amber-700 border border-amber-100';
        $statusBullet = 'bg-amber-500';
    } elseif ($previewStatus === 'Bahaya') {
        $statusBadge  = 'bg-rose-50 text-rose-700 border border-rose-100';
        $statusBullet = 'bg-rose-500';
    }

    /* Disease-level badge colours — mirrors exact classes from prediction-form.blade.php */
    $levelColors = [
        'Low'      => 'bg-emerald-50 text-emerald-700',
        'Moderate' => 'bg-amber-50   text-amber-700',
        'High'     => 'bg-rose-50    text-rose-700',
    ];

    /* Recommendation priority colour — mirrors left-border strip approach */
    $recColor = $previewRec['priority'] === 'Tinggi' ? 'rose'
              : ($previewRec['priority'] === 'Sedang' ? 'amber' : 'emerald');

    /* Gauge math — circumference = 2π × 44 ≈ 276 */
    $circumference   = 276;
    $gaugeOffset     = $circumference - ($circumference * $previewScore / 100);
@endphp

{{-- ═══ Outer positioning wrapper ═══ --}}
<div class="relative flex justify-center items-start">

    {{-- ── Floating "Analisis Selesai" accent badge ── --}}
    <div class="hidden lg:flex absolute -top-4 -left-8 z-10
                items-center gap-1.5
                bg-white border border-teal-200 shadow-md
                text-[11px] font-semibold text-teal-700
                px-3 py-1.5 rounded-full
                animate-float-accent">
        <span class="w-1.5 h-1.5 rounded-full bg-[#00A19D] animate-pulse-dot"></span>
        Analisis Selesai
    </div>

    {{-- ── Main preview card — matches bg/shadow/border from results page ── --}}
    <div class="w-full max-w-[420px] bg-white rounded-3xl
                shadow-[0_20px_50px_rgba(20,184,166,0.10)]
                border border-teal-500/10
                overflow-hidden">

        {{-- ── Top bar ── --}}
        <div class="flex items-center justify-between
                    px-5 py-3.5
                    border-b border-slate-100 bg-[#FAFDFD]">
            <span class="inline-flex items-center gap-1.5
                         px-3 py-1 rounded-full
                         text-[11px] font-semibold
                         bg-teal-50 text-teal-800 border border-teal-200">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00A19D]"></span>
                Hasil Analisis Medis
            </span>
            <span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export PDF
            </span>
        </div>

        <div class="p-5 space-y-4">

            {{-- ════════════════════════════════════════
                 SECTION 1 — Health Score Gauge + Status
                 Matches the bg-[#FAFDFD] gauge row in results page
            ════════════════════════════════════════ --}}
            <div class="bg-[#FAFDFD] rounded-2xl p-4 border border-teal-500/5 flex items-center gap-4">

                {{-- SVG Gauge circle (96×96, same as results page w-24 h-24) --}}
                <div class="relative shrink-0 w-20 h-20 rounded-full bg-white shadow-inner
                            flex items-center justify-center border-4 border-slate-100">
                    {{-- Score text --}}
                    <div class="text-center z-10 relative">
                        <div class="font-serif text-2xl font-extrabold text-[#005B60] leading-none">{{ $previewScore }}</div>
                        <div class="text-[8px] uppercase tracking-wider text-slate-400 font-bold mt-0.5">Health Score</div>
                    </div>
                    {{-- Progress ring --}}
                    <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
                        <circle cx="40" cy="40" r="34" class="stroke-teal-50 fill-none" stroke-width="4"/>
                        <circle cx="40" cy="40" r="34"
                                class="fill-none"
                                stroke="#00A19D"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-dasharray="214"
                                stroke-dashoffset="{{ 214 - (214 * $previewScore / 100) }}"/>
                    </svg>
                </div>

                {{-- Status text block --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="font-bold text-slate-800 text-sm">Prediksi Status</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $statusBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusBullet }}"></span>
                            {{ $previewStatus }}
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-[#005B60] leading-tight mb-0.5">
                        Risiko Tertinggi: {{ $highestRisk }}
                    </p>
                    <p class="text-[10px] text-slate-400 leading-snug">Berdasarkan 6 metrik klinis</p>
                </div>
            </div>

            {{-- ════════════════════════════════════════
                 SECTION 2 — Top 3 Disease Predictions
                 Matches the metricsGrid cards from results page
            ════════════════════════════════════════ --}}
            <div>
                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                    Prediksi Risiko Penyakit
                </h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($previewDiseases as $index => $d)
                        @php 
                            $lc = $levelColors[$d['level']] ?? 'bg-emerald-50 text-emerald-700'; 
                            $isLastOdd = ($index === count($previewDiseases) - 1) && (count($previewDiseases) % 2 !== 0);
                            $colSpanClass = $isLastOdd ? 'col-span-2' : '';
                        @endphp
                        <div class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm space-y-1.5 {{ $colSpanClass }}">
                            <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wide leading-tight">
                                {{ $d['label'] }}
                            </div>
                            <div class="font-mono text-base font-bold text-slate-800 leading-none">
                                {{ $d['pct'] }}
                            </div>
                            <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded {{ $lc }}">
                                {{ $d['level'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ════════════════════════════════════════
                 SECTION 3 — One AI Recommendation snippet
                 Matches the border-l-4 recommendation cards
            ════════════════════════════════════════ --}}
            <div class="border-t border-slate-100 pt-3">
                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2">
                    <span class="text-[#00A19D]">●</span>
                    Rekomendasi AI
                </h4>
                <div class="p-3.5 bg-{{ $recColor }}-50 border-l-4 border-{{ $recColor }}-400 rounded-lg">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-800 text-xs leading-tight">{{ $previewRec['title'] }}</p>
                            <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">{{ $previewRec['description'] }}</p>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider
                                     bg-{{ $recColor }}-100 text-{{ $recColor }}-800 rounded-full">
                            {{ $previewRec['priority'] }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
