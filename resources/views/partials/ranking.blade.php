{{-- PERINGKAT: podium juara + daftar, satu tab per seleksi. Pengganti kartu seleksi di landing. --}}
@php
    $pkBoards = $boards ?? collect();
    $pkMask = fn ($r) => \App\Services\Exam\ExamStats::maskName($r?->user?->name);
    // Metadata tiap papan: nama, ikon, tautan. Papan karier tidak punya ExamType tunggal.
    $pkMeta = function ($b) {
        if (! empty($b['career'])) {
            return ['name' => 'Simulasi Jalur Karier', 'icon' => 'compass', 'board' => route('exam.leaderboard'), 'join' => route('exam.index').'#karier', 'join_label' => 'Pilih Simulasi Jalur Karier'];
        }
        $t = $b['type']; $code = strtolower($t->code); $name = strtolower($t->name);
        $icon = str_contains($code.$name, 'cpns') ? 'cpns' : (str_contains($code.$name, 'bumn') ? 'bumn' : (str_contains($code.$name, 'polri') ? 'polri' : (str_contains($code.$name, 'tni') ? 'tni' : 'cpns')));
        return ['name' => $t->name, 'icon' => $icon, 'board' => route('exam.leaderboard', ['jenis' => $t->code]), 'join' => route('exam.show', $t), 'join_label' => 'Ikut '.$t->name];
    };
    $pkTime = function ($r) { $s = $r?->durationSeconds() ?? 0; return intdiv($s, 60).'m '.($s % 60).'d'; };
@endphp

<div class="pk" data-pk aria-labelledby="pk-title">
    <svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
        <defs>
            <symbol id="pk-i-crown" viewBox="0 0 24 24"><path d="M2.2 7.6 7 11.4 12 3.5l5 7.9 4.8-3.8-2 11.4H4.2z"/><rect x="4.4" y="20.2" width="15.2" height="1.8" rx=".9"/></symbol>
            <symbol id="pk-l-cpns" viewBox="0 0 32 32"><path d="M16 3 3 9v2h26V9zM6 13v10M12 13v10M20 13v10M26 13v10M3 26h26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="m16 5.6.9 1.9 2.1.3-1.5 1.5.4 2.1-1.9-1-1.9 1 .4-2.1L13 7.8l2.1-.3z" fill="currentColor"/></symbol>
            <symbol id="pk-l-bumn" viewBox="0 0 32 32"><path d="M5 28V12l8-4v20M13 28V4l14 6v18M3 28h26M17 12h6M17 17h6M17 22h6M8 15v2M8 20v2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="pk-l-polri" viewBox="0 0 32 32"><path d="M16 3 5 7v8c0 7 4.6 11.6 11 14 6.4-2.4 11-7 11-14V7z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/><path d="m16 9.5 1.9 4 4.4.5-3.2 3 .9 4.3-4-2.2-4 2.2.9-4.3-3.2-3 4.4-.5z" fill="currentColor"/></symbol>
            <symbol id="pk-l-tni" viewBox="0 0 32 32"><path d="m16 4 2.5 5.2 5.7.8-4.1 4 1 5.7L16 17l-5.1 2.7 1-5.7-4.1-4 5.7-.8z" fill="currentColor"/><path d="M2 20c4 0 7 1.4 9 4M30 20c-4 0-7 1.4-9 4M9 28h14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
            <symbol id="pk-l-compass" viewBox="0 0 32 32"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m21.5 10.5-3.3 7.7-7.7 3.3 3.3-7.7z" fill="currentColor"/><circle cx="16" cy="16" r="1.6" fill="#1b2638"/></symbol>
            <symbol id="pk-i-trophy" viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        </defs>
    </svg>

    <div>
        <div class="duo-head" data-reveal>
            <div class="duo-top">
                <p class="eyebrow">Papan peringkat</p>
                <a href="{{ route('exam.leaderboard') }}" class="pk-all">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#pk-i-trophy"/></svg>
                    <span>Lihat peringkat lengkap</span>
                    <svg class="pk-all-ar" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
            <h2 id="pk-title" class="font-display tracking-tight text-ink">Siapa yang paling <em class="pk-em">siap hari ini?</em></h2>
            <p class="duo-sub">Skor tertinggi dari ujian penuh. Skor sama diurutkan dari pengerjaan tercepat. Nama disamarkan.</p>
        </div>

        @if ($pkBoards->isEmpty())
            <p class="mt-12 rounded-2xl border border-dashed border-graphite-300 p-10 text-center text-graphite-500">Peringkat akan muncul setelah ujian pertama selesai dikerjakan.</p>
        @else
            <div class="pk-card" data-reveal>
                <div class="pk-tabs" role="tablist" aria-label="Pilih seleksi">
                    @foreach ($pkBoards as $i => $b)
                        @php $m = $pkMeta($b); @endphp
                        <a href="{{ $m['board'] }}" role="tab" id="pk-tab-{{ $i }}" aria-controls="pk-pane-{{ $i }}" @class(['pk-tab', 'is-on' => $i === 0]) aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-label="{{ $m['name'] }}" title="{{ $m['name'] }}" data-pk-tab="{{ $i }}"><svg class="pk-logo is-{{ $m['icon'] }}" viewBox="0 0 32 32" aria-hidden="true"><use href="#pk-l-{{ $m['icon'] }}"/></svg><span class="pk-tab-t">{{ $m['name'] }}</span></a>
                    @endforeach
                </div>

                @foreach ($pkBoards as $i => $b)
                    @php $m = $pkMeta($b); $rows = $b['rows']; @endphp
                    <div class="pk-pane" id="pk-pane-{{ $i }}" role="tabpanel" aria-labelledby="pk-tab-{{ $i }}" data-pk-pane="{{ $i }}">
                        {{-- Podium (hiasan; daftar teks ada di sisi kanan) --}}
                        <div class="pk-stage">
                            <div class="pk-glow" aria-hidden="true"></div>
                            <p class="pk-stage-k"><svg class="pk-logo pk-logo-lg is-{{ $m['icon'] }}" viewBox="0 0 32 32" aria-hidden="true"><use href="#pk-l-{{ $m['icon'] }}"/></svg>{{ $m['name'] }}</p>
                            <ol class="pk-podium" aria-label="Tiga teratas {{ $m['name'] }}">
                                @foreach ([[1, 'pk-p2'], [0, 'pk-p1'], [2, 'pk-p3']] as [$idx, $cls])
                                    @php $r = $rows->get($idx); @endphp
                                    <li class="pk-pod {{ $cls }}{{ $r ? '' : ' is-empty' }}" style="--d: {{ $idx }}">
                                        <div class="pk-who">
                                            @if ($idx === 0)<svg class="pk-crown" aria-hidden="true"><use href="#pk-i-crown"/></svg>@endif
                                            <span class="pk-av" aria-hidden="true">{{ $r ? mb_strtoupper(mb_substr($pkMask($r), 0, 1)) : '?' }}</span>
                                            <b>{{ $r ? $pkMask($r) : 'Kursi kosong' }}</b>
                                            <small>{{ $r ? $r->total_score.' poin' : 'Belum ada' }}</small>
                                        </div>
                                        <div class="pk-bar" aria-hidden="true"><span>{{ $idx + 1 }}</span></div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        {{-- Daftar teks: urutan 1 sampai 5 --}}
                        <div class="pk-side">
                            <div class="pk-list" role="table" aria-label="Lima teratas {{ $m['name'] }}">
                                <div class="pk-row pk-head" role="row">
                                    <span role="columnheader">#</span>
                                    <span role="columnheader">Peserta</span>
                                    <span role="columnheader" class="pk-num">Skor</span>
                                    <span role="columnheader" class="pk-num pk-hide-s">Waktu</span>
                                </div>
                                @for ($n = 1; $n <= 5; $n++)
                                    @php $r = $rows->get($n - 1); $me = $r && auth()->id() === $r->user_id; @endphp
                                    <div @class(['pk-row', 'pk-t'.$n => $n <= 3, 'is-empty' => ! $r, 'is-me' => $me]) role="row" style="--i: {{ $n - 1 }}">
                                        <span class="pk-rank" role="cell"><i>{{ $n }}</i></span>
                                        <span class="pk-name" role="cell">
                                            @if ($r)
                                                <b>{{ $pkMask($r) }}@if ($me)<em class="pk-me">Kamu</em>@endif</b>
                                                <small>{{ $r->finished_at?->translatedFormat('d M Y') }}@if ($r->passed)<span class="pk-pass">Lulus</span>@endif</small>
                                            @else
                                                <b>Kursi kosong</b><small>Jadilah yang pertama</small>
                                            @endif
                                        </span>
                                        <span class="pk-num pk-score" role="cell">{{ $r ? $r->total_score : '-' }}</span>
                                        <span class="pk-num pk-time pk-hide-s" role="cell">{{ $r ? $pkTime($r) : '-' }}</span>
                                    </div>
                                @endfor
                            </div>

                            <div class="pk-cta">
                                <a href="{{ $m['join'] }}" class="btn-primary">{{ $m['join_label'] }}</a>
                                <a href="{{ $m['board'] }}" class="btn-outline">Daftar lengkap</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
    (function () {
        var root = document.querySelector('[data-pk]'); if (!root) return;
        var tabs = [].slice.call(root.querySelectorAll('[data-pk-tab]'));
        var panes = [].slice.call(root.querySelectorAll('[data-pk-pane]'));
        if (!tabs.length) return;
        // Tanpa JS semua tab adalah tautan biasa ke papan lengkap; dengan JS tab berganti di tempat.
        function show(n) {
            tabs.forEach(function (t, i) { var on = i === n; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; });
            panes.forEach(function (p, i) { p.hidden = i !== n; });
        }
        show(0);

        // Ganti otomatis tiap 5 detik. Berhenti saat disentuh/di-hover/difokus, tab tersembunyi,
        // section di luar layar, atau pengguna memilih mengurangi animasi.
        var cur = 0, timer = null, paused = false, visible = true;
        var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function stop() { if (timer) { clearInterval(timer); timer = null; } }
        function start() {
            stop();
            if (still || paused || !visible || document.hidden || tabs.length < 2) return;
            timer = setInterval(function () { cur = (cur + 1) % tabs.length; show(cur); }, 5000);
        }
        function go(n) { cur = n; show(n); start(); }

        tabs.forEach(function (t, i) {
            t.addEventListener('click', function (e) { e.preventDefault(); go(i); });
            t.addEventListener('keydown', function (e) {
                var k = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0; if (!k) return;
                e.preventDefault(); var n = (i + k + tabs.length) % tabs.length; go(n); tabs[n].focus();
            });
        });
        root.addEventListener('mouseenter', function () { paused = true; stop(); });
        root.addEventListener('mouseleave', function () { paused = false; start(); });
        root.addEventListener('focusin', function () { paused = true; stop(); });
        root.addEventListener('focusout', function () { paused = false; start(); });
        root.addEventListener('touchstart', function () { paused = true; stop(); }, { passive: true });
        root.addEventListener('touchend', function () { setTimeout(function () { paused = false; start(); }, 8000); }, { passive: true });
        document.addEventListener('visibilitychange', start);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { visible = en[0].isIntersecting; start(); }).observe(root);
        }
        start();
    })();
</script>
