{{-- Pilih simulasi: 3 seleksi resmi + 1 jalur karier. Dipakai di landing dan menu Simulasi. Butuh $types, $careerPick, $careerTotal. --}}
@if ($types->isNotEmpty() || $careerPick->isNotEmpty())
    {{-- Lambang dipakai ulang lewat <use> --}}
    <svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
        <defs>
            <symbol id="sc-l-cpns" viewBox="0 0 32 32"><path d="M16 3 3 9v2h26V9zM6 13v10M12 13v10M20 13v10M26 13v10M3 26h26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="m16 5.6.9 1.9 2.1.3-1.5 1.5.4 2.1-1.9-1-1.9 1 .4-2.1L13 7.8l2.1-.3z" fill="currentColor"/></symbol>
            <symbol id="sc-l-bumn" viewBox="0 0 32 32"><path d="M5 28V12l8-4v20M13 28V4l14 6v18M3 28h26M17 12h6M17 17h6M17 22h6M8 15v2M8 20v2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="sc-l-polri" viewBox="0 0 32 32"><path d="M16 3 5 7v8c0 7 4.6 11.6 11 14 6.4-2.4 11-7 11-14V7z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/><path d="m16 9.5 1.9 4 4.4.5-3.2 3 .9 4.3-4-2.2-4 2.2.9-4.3-3.2-3 4.4-.5z" fill="currentColor"/></symbol>
            <symbol id="sc-l-tni" viewBox="0 0 32 32"><path d="m16 4 2.5 5.2 5.7.8-4.1 4 1 5.7L16 17l-5.1 2.7 1-5.7-4.1-4 5.7-.8z" fill="currentColor"/><path d="M2 20c4 0 7 1.4 9 4M30 20c-4 0-7 1.4-9 4M9 28h14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
            <symbol id="sc-l-compass" viewBox="0 0 32 32"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m21.5 10.5-3.3 7.7-7.7 3.3 3.3-7.7z" fill="currentColor"/></symbol>
        </defs>
    </svg>

    <div id="seleksi" class="sx-head">
        <p class="eyebrow">Pilih simulasi</p>
        <h2 class="font-display">Mau latihan yang mana?</h2>
        <p>Tiap simulasi dikerjakan dengan timer dan penilaian di server, seperti ujian sebenarnya.</p>
    </div>

    @php
        $scFacts = [
            'soal' => '<path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/>',
            'menit' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 2h6"/>',
            'server' => '<path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        ];
    @endphp

    <div class="sc-grid">
        @foreach ($types as $i => $type)
            @php
                $key = strtolower($type->code.' '.$type->name);
                $icon = str_contains($key, 'cpns') ? 'cpns' : (str_contains($key, 'bumn') ? 'bumn' : (str_contains($key, 'polri') ? 'polri' : (str_contains($key, 'tni') ? 'tni' : 'cpns')));
                $trialQ = (int) $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count));
            @endphp
            <article class="sc-card" style="--i: {{ $i }}" data-reveal>
                <span class="sc-ic" aria-hidden="true"><svg viewBox="0 0 32 32"><use href="#sc-l-{{ $icon }}"/></svg></span>
                <h3 class="font-display">{{ $type->name }}</h3>
                <p class="sc-desc">{{ $type->description }}</p>
                <ul class="sc-facts">
                    <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['soal'] !!}</svg>{{ $trialQ }} soal</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['menit'] !!}</svg>{{ (int) ($type->trial_duration_seconds / 60) }} menit</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['server'] !!}</svg>Dinilai di server</li>
                </ul>
                @if ($trialQ > 0)
                    <a href="{{ route('exam.show', $type) }}" class="btn-primary sc-btn">Coba simulasi</a>
                @else
                    <span class="btn-outline sc-btn is-off" aria-disabled="true">Soal sedang disiapkan</span>
                @endif
            </article>
        @endforeach

        {{-- Kartu keempat: Simulasi Jalur Karier. Peran dipilih bebas; keahlian hanya menyarankan urutan. --}}
        <article class="sc-card sc-card-career" style="--i: {{ $types->count() }}" data-reveal>
            <span class="sc-ic" aria-hidden="true"><svg viewBox="0 0 32 32"><use href="#sc-l-compass"/></svg></span>
            <h3 class="font-display">Simulasi Jalur Karier</h3>
            <p class="sc-desc">Pilih peran yang kamu tuju dari daftar karier. Soal simulasinya mengikuti peran itu.</p>
            <ul class="sc-facts">
                <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['soal'] !!}</svg>{{ $careerTotal }} pilihan peran</li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['menit'] !!}</svg>Soal sesuai peran</li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true">{!! $scFacts['server'] !!}</svg>Dinilai di server</li>
            </ul>
            <a href="{{ route('exam.index') }}#karier" class="btn-primary sc-btn">Pilih peran</a>
        </article>
    </div>
@endif

