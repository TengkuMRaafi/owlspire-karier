{{-- PAPAN SKOR OWLSPIRE: satu baris angka (rating, pengguna, simulasi, soal) di antara hero dan roadmap. Di HP disusun 2x2 (ikon di kiri angka). --}}
@php
    // Mode contoh (PLATFORM_SCOREBOARD_DEMO, bawaan aktif): angka fiktif dari config agar landing terlihat hidup.
    // Mode nyata: angka dihitung dari database (di-cache 10 menit); rating hanya tampil bila PLATFORM_RATING diisi.
    $sbDemo = (bool) config('platform.scoreboard.demo');
    $sbFake = config('platform.scoreboard.demo_values', []);
    $sbRating = config('platform.scoreboard.rating') ?? ($sbDemo ? ($sbFake['rating'] ?? null) : null);
    $sbRatingCount = config('platform.scoreboard.rating_count') ?? ($sbDemo ? ($sbFake['rating_count'] ?? null) : null);
    if ($sbDemo) {
        $sbStats = [
            'pengguna' => $sbFake['pengguna'] ?? 0,
            'soal' => $sbFake['soal'] ?? 0,
            'selesai' => $sbFake['selesai'] ?? 0,
        ];
    } else {
        try {
            $sbStats = \Illuminate\Support\Facades\Cache::remember('landing.scoreboard', 600, fn () => [
                'pengguna' => \App\Models\User::count(),
                'soal' => \App\Models\Question::count(),
                'selesai' => \App\Models\ExamAttempt::whereNotNull('finished_at')->count(),
            ]);
        } catch (\Throwable $e) {
            report($e); // landing tetap tampil walau database belum siap
            $sbStats = ['pengguna' => 0, 'soal' => 0, 'selesai' => 0];
        }
    }

    // Ikon garis 24x24 (tanpa emoji)
    $sbIcons = [
        'rating' => '<path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3 6.1 20.6l1.3-6.6L2.5 9.4l6.6-.8z"/>',
        'pengguna' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'selesai' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'soal' => '<path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/>',
    ];

    // [kunci, label, keterangan (hanya desktop), nilai]
    $sbTiles = collect();
    if ($sbRating) {
        $sbTiles->push(['rating', 'Rating', $sbRatingCount ? 'dari '.number_format($sbRatingCount, 0, ',', '.').' penilaian' : 'dari 5 bintang', $sbRating]);
    }
    $sbTiles->push(['pengguna', 'Pengguna', 'sudah bergabung', $sbStats['pengguna']]);
    $sbTiles->push(['selesai', 'Simulasi', 'selesai dikerjakan', $sbStats['selesai']]);
    $sbTiles->push(['soal', 'Soal', 'siap dilatih', $sbStats['soal']]);

    // Konfeti tetap (dihitung dari indeks) agar tampilan stabil; hanya muncul di layar lebar.
    $sbPal = ['#f0b429', '#7fa8e8', '#ff7aa8', '#5eead4', '#c4a8ff'];
    $sbConf = collect(range(0, 13))->map(fn ($i) => [
        'x' => $i % 2 ? 2 + ($i * 7 % 12) : 86 + ($i * 5 % 12),
        'y' => 8 + ($i * 37 % 80),
        'c' => $sbPal[$i % 5],
        'k' => ['sq', 'bar', 'dot'][$i % 3],
        'd' => ($i * 13 % 41) / 10,
        'r' => $i * 47 % 181,
    ]);
@endphp

<section class="sb" data-sb aria-label="Angka platform Owlspire">
    <div class="sb-fx" aria-hidden="true">
        @foreach ($sbConf as $p)
            <i class="sb-cf sb-cf-{{ $p['k'] }}" style="left:{{ $p['x'] }}%;top:{{ $p['y'] }}%;--c:{{ $p['c'] }};--d:-{{ $p['d'] }}s;--r:{{ $p['r'] }}deg"></i>
        @endforeach
        <svg class="sb-stream sb-stream-l" viewBox="0 0 220 120" fill="none" stroke-linecap="round"><path d="M0 14c40 0 40 30 80 30s40-30 80-30 40 30 60 30" stroke="#f0b429" stroke-width="3"/><path d="M0 40c40 0 40 30 80 30s40-30 80-30 40 30 60 30" stroke="#ff7aa8" stroke-width="3" opacity=".8"/><path d="M0 66c40 0 40 30 80 30s40-30 80-30" stroke="#7fa8e8" stroke-width="3" opacity=".7"/></svg>
        <svg class="sb-stream sb-stream-r" viewBox="0 0 220 120" fill="none" stroke-linecap="round"><path d="M220 14c-40 0-40 30-80 30s-40-30-80-30-40 30-60 30" stroke="#5eead4" stroke-width="3"/><path d="M220 40c-40 0-40 30-80 30s-40-30-80-30-40 30-60 30" stroke="#f0b429" stroke-width="3" opacity=".8"/><path d="M220 66c-40 0-40 30-80 30s-40-30-80-30" stroke="#c4a8ff" stroke-width="3" opacity=".7"/></svg>
        @foreach (['l', 'r'] as $side)
            <svg class="sb-popper sb-popper-{{ $side }}" viewBox="0 0 72 72" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M8 64 22 26l24 24z" fill="#f0b429" stroke="#b9851a" stroke-width="2"/><path d="M15 46l11 11M20 33l18 18" stroke="#b9851a" stroke-width="2"/><path d="M40 12v8M56 22l-6 5M62 38h-8M46 4l2 5M58 10l-4 5" stroke="#7fa8e8" stroke-width="3"/><circle cx="56" cy="14" r="2.5" fill="#ff7aa8"/><circle cx="64" cy="28" r="2.5" fill="#5eead4"/><circle cx="34" cy="8" r="2.5" fill="#c4a8ff"/></svg>
        @endforeach
    </div>

    <ul class="sb-row" style="--sb-cols: {{ $sbTiles->count() }}">
        @foreach ($sbTiles as [$key, $label, $note, $val])
            @php $isRate = $key === 'rating'; @endphp
            <li>
                <button type="button" @class(['sb-cell', 'sb-rate' => $isRate]) data-sb-tile title="Ketuk untuk memutar ulang">
                    <span class="sb-ic" aria-hidden="true"><svg viewBox="0 0 24 24">{!! $sbIcons[$key] !!}</svg></span>
                    <span class="sb-txt">
                        @if ($isRate)
                            <span class="sb-val" data-sb-val="{{ number_format($val, 1, '.', '') }}" data-sb-key="rating" data-dec="1">{{ number_format($val, 1, ',', '.') }}</span>
                        @else
                            <span class="sb-val" data-sb-val="{{ $val }}" data-sb-key="{{ $key }}">{{ number_format($val, 0, ',', '.') }}</span>
                        @endif
                        <small class="sb-lbl">{{ $label }}</small>
                        <em class="sb-note">{{ $note }}</em>
                    </span>
                </button>
            </li>
        @endforeach
    </ul>
</section>

<script>
    (function () {
        var root = document.querySelector('[data-sb]'); if (!root) return;
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        var narrow = matchMedia('(max-width: 330px)'); // hanya layar sangat sempit: angka besar dipendekkan (12,5rb)
        var cells = [].slice.call(root.querySelectorAll('[data-sb-tile]'));
        var raf = 0;

        function fmt(v, dec) {
            if (dec) return v.toFixed(1).replace('.', ',');
            v = Math.round(v);
            if (narrow.matches && v >= 1000) {
                var big = v >= 1e6, x = v / (big ? 1e6 : 1e3);
                return (x < 100 ? x.toFixed(1) : String(Math.round(x))).replace('.', ',').replace(/,0$/, '') + (big ? 'jt' : 'rb');
            }
            return String(v).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        function show(el, v) { el.textContent = fmt(v, !!el.dataset.dec); }
        function showAll() { cells.forEach(function (c) { var el = c.querySelector('[data-sb-val]'); show(el, +el.dataset.sbVal); }); }

        // Hitung naik 0 -> nilai akhir. Satu rAF untuk semua angka dan berhenti sendiri setelah selesai.
        function run(list) {
            cancelAnimationFrame(raf);
            if (reduce) { showAll(); return; }
            var t0 = performance.now(), D = 1300;
            (function step(now) {
                var k = Math.min(1, (now - t0) / D), e = 1 - Math.pow(1 - k, 3);
                list.forEach(function (c) { var el = c.querySelector('[data-sb-val]'); show(el, +el.dataset.sbVal * e); });
                if (k < 1) raf = requestAnimationFrame(step);
            })(t0);
        }

        showAll();
        var played = false;
        function play() { if (played) return; played = true; root.classList.add('is-on'); run(cells); }
        if (reduce || !('IntersectionObserver' in window)) play();
        else new IntersectionObserver(function (es, o) { if (es[0].isIntersecting) { play(); o.disconnect(); } }, { threshold: .35 }).observe(root);

        cells.forEach(function (c) { c.addEventListener('click', function () { if (played) run([c]); }); });
        narrow.addEventListener('change', showAll);
    })();
</script>
