{{-- Hasil ujian penuh: passing grade, grafik, peringkat (Premium), ringkasan Owlspire. Data dari ResultInsights. --}}
@php
    $st = $insights['stats'];
    $pg = $insights['passing'];
    $segs = $insights['segments'];
    $rank = $insights['rank'];
    $rules = $attempt->type->passing_rules ?? [];
    $overallMin = isset($rules['min_percent']) ? (float) $rules['min_percent'] : null;
    $short = fn ($n) => preg_match('/\(([^)]+)\)/', $n, $m) ? $m[1] : $n;
    $miss = collect($pg['items'])->where('pass', false);

    // Radar: tiap bagian satu sumbu, skala 0-100%.
    $n = count($segs); $cx = 150; $cy = 140; $R = 90;
    $pt = fn ($i, $v) => [round($cx + $R * $v / 100 * sin(2 * M_PI * $i / max(1, $n)), 1), round($cy - $R * $v / 100 * cos(2 * M_PI * $i / max(1, $n)), 1)];
    $poly = function ($vals) use ($pt) { $o = []; foreach ($vals as $i => $v) { $o[] = implode(',', $pt($i, max(0, min(100, $v)))); } return implode(' ', $o); };
    $mine = array_map(fn ($s) => $s['pct'], $segs);
    $need = array_map(fn ($s) => $s['threshold'] ?? $overallMin ?? 0, $segs);
    $peerVals = array_map(fn ($s) => $s['peer_pct'], $segs);
    $hasPeers = ! in_array(null, $peerVals, true) && $n > 0;

    $tot = max(1, $st['total']);
    $dB = $st['benar'] / $tot * 100; $dS = $st['salah'] / $tot * 100; $dK = $st['kosong'] / $tot * 100;
@endphp

<style>
    .rv { --l: #e2e6ec; --soft: #f4f6f9; --ink: #1b2638; --mut: #5d6b82; --acc: var(--color-accent-600, #3b6cd4); --good: #2f9e6b; --bad: #d9480f; color: var(--ink); display: grid; gap: .9rem; }
    .rv-card { padding: 1.2rem 1.3rem; border: 1px solid var(--l); border-radius: 1.1rem; background: #fff; min-width: 0; }
    .rv-h { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); }
    .rv-verdict { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem; }
    .rv-verdict h2 { font-family: var(--font-display, inherit); font-size: 1.25rem; font-weight: 700; line-height: 1.3; }
    .rv-chip { display: inline-block; padding: .25rem .75rem; border-radius: 99px; font-size: .74rem; font-weight: 800; background: #e3f5ec; color: #1f7a50; }
    .rv-chip.is-bad { background: #fdebe3; color: #b13a08; }
    .rv-pg { display: grid; gap: 1.1rem; margin-top: 1.1rem; }
    .rv-pg-top { display: flex; justify-content: space-between; align-items: baseline; gap: .6rem; font-size: .86rem; }
    .rv-pg-top b { font-weight: 800; } .rv-pg-top span { color: var(--mut); font-size: .78rem; font-variant-numeric: tabular-nums; }
    .rv-bar { position: relative; height: .95rem; margin-top: .45rem; border-radius: 99px; background: var(--l); }
    .rv-bar > i { position: absolute; inset: 0 auto 0 0; border-radius: 99px; background: var(--good); transform-origin: left; animation: rv-grow .9s cubic-bezier(.2,.8,.2,1) both; }
    .rv-bar.is-bad > i { background: var(--bad); }
    .rv-bar > u { position: absolute; top: -.35rem; bottom: -.35rem; width: 3px; border-radius: 2px; background: var(--ink); }
    .rv-bar > u::after { content: attr(data-l); position: absolute; top: -1.15rem; left: 50%; transform: translateX(-50%); font-size: .66rem; font-weight: 800; text-decoration: none; white-space: nowrap; color: var(--ink); }
    .rv-note { margin-top: 1rem; font-size: .76rem; color: var(--mut); line-height: 1.5; }
    .rv-charts { display: grid; grid-template-columns: 1.15fr .85fr; gap: .9rem; }
    .rv-svg { display: block; width: 100%; max-width: 21rem; margin: .5rem auto 0; height: auto; }
    .rv-svg text { font-family: inherit; fill: var(--ink); }
    .rv-poly-me { fill: rgba(59,108,212,.22); stroke: var(--acc); stroke-width: 2.4; animation: rv-pop .8s ease both; transform-origin: 150px 140px; }
    .rv-leg { display: flex; flex-wrap: wrap; justify-content: center; gap: .3rem 1rem; margin-top: .6rem; font-size: .74rem; color: var(--mut); }
    .rv-leg i { display: inline-block; width: .7rem; height: .7rem; margin-right: .3rem; border-radius: 3px; vertical-align: -1px; }
    .rv-donut { position: relative; width: 11rem; height: 11rem; margin: .6rem auto 0; }
    .rv-donut svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .rv-donut circle { fill: none; stroke-width: 5.2; }
    .rv-donut .seg { animation: rv-dash 1s ease both; }
    .rv-donut-c { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
    .rv-donut-c b { font-size: 2rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; } .rv-donut-c span { font-size: .7rem; color: var(--mut); }
    .rv-rank { position: relative; overflow: hidden; border-radius: 1.1rem; border: 1px solid var(--l); background: #fff; }
    .rv-rank-fake { padding: 1.2rem 1.4rem; filter: blur(7px); pointer-events: none; user-select: none; }
    .rv-rank-fake b { display: block; font-size: 2.6rem; font-weight: 700; line-height: 1.1; } .rv-rank-fake p { margin-top: .3rem; font-size: .9rem; color: var(--mut); }
    .rv-rank-fake div { height: .8rem; margin-top: .8rem; border-radius: 99px; background: linear-gradient(90deg, var(--acc) 62%, var(--l) 62%); }
    .rv-rank-btn { position: absolute; inset: 0; display: grid; place-content: center; justify-items: center; gap: .35rem; padding: 1rem; background: rgba(255,255,255,.45); color: var(--ink); text-decoration: none; text-align: center; transition: background .2s; }
    .rv-rank-btn:hover { background: rgba(255,255,255,.2); }
    .rv-rank-btn b { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.3rem; border-radius: .9rem; background: var(--acc); color: #fff; font-size: .95rem; font-weight: 800; box-shadow: 0 8px 20px -8px rgba(59,108,212,.7); }
    .rv-rank-btn small { font-size: .78rem; font-weight: 600; color: #33415a; }
    .rv-rank-btn svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; }
    .rv-rank-open { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem; padding: 1.2rem 1.4rem; }
    .rv-rank-open b { font-size: 2.2rem; font-weight: 700; line-height: 1; } .rv-rank-open b em { font-style: normal; font-size: .95rem; color: var(--mut); font-weight: 600; }
    .rv-ai { display: grid; grid-template-columns: auto 1fr; gap: 1rem; }
    .rv-ai-av { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 99px; background: #eef4fd; border: 1px solid #dce8fb; }
    .rv-ai-av svg { width: 1.6rem; height: auto; }
    .rv-ai-name { font-size: .95rem; font-weight: 800; } .rv-ai-name small { display: block; font-size: .72rem; font-weight: 600; color: var(--mut); }
    .rv-ai-body { margin-top: .6rem; font-size: .88rem; line-height: 1.65; color: #33415a; white-space: pre-line; }
    .rv-sk { height: .7rem; margin-top: .55rem; border-radius: 99px; background: linear-gradient(90deg, var(--soft), #e8edf5, var(--soft)); background-size: 200% 100%; animation: rv-sh 1.2s linear infinite; }
    .rv-retry { margin-top: .5rem; padding: .35rem .9rem; border: 1.5px solid var(--l); border-radius: 99px; background: #fff; font: inherit; font-size: .8rem; font-weight: 700; cursor: pointer; }
    @keyframes rv-grow { from { transform: scaleX(0); } } @keyframes rv-pop { from { opacity: 0; transform: scale(.4); } }
    @keyframes rv-dash { from { stroke-dasharray: 0 100; } } @keyframes rv-sh { to { background-position: -200% 0; } }
    @media (max-width: 860px) { .rv-charts { grid-template-columns: 1fr; } }
    @media (prefers-reduced-motion: reduce) { .rv * { animation: none !important; } }
</style>

<div class="rv">
    {{-- ============ Passing grade ============ --}}
    <section class="rv-card" aria-labelledby="rv-pg-h">
        <div class="rv-verdict">
            <div>
                <p class="rv-h" id="rv-pg-h">Passing grade</p>
                <h2 class="mt-1">
                    @if ($pg['all_pass'])
                        Skormu menembus semua ambang. Kamu lolos passing grade.
                    @elseif ($pg['mode'] === 'segment')
                        Belum lolos: {{ $miss->count() }} bagian di bawah ambang ({{ $miss->map(fn ($i) => $short($i['name']).' kurang '.abs($i['gap']).' poin')->implode(', ') }}).
                    @else
                        Belum lolos: skor total kurang {{ abs($pg['items'][0]['gap'] ?? 0) }} poin dari ambang.
                    @endif
                </h2>
            </div>
            <span @class(['rv-chip', 'is-bad' => ! $pg['all_pass']])>{{ $pg['all_pass'] ? 'Lulus' : 'Belum lulus' }}</span>
        </div>

        <div class="rv-pg">
            @foreach ($pg['items'] as $it)
                @php $w = $it['maks'] > 0 ? min(100, $it['poin'] / $it['maks'] * 100) : 0; $m = $it['maks'] > 0 ? min(100, $it['need'] / $it['maks'] * 100) : 0; @endphp
                <div>
                    <div class="rv-pg-top"><b>{{ $it['name'] }}</b><span>{{ $it['poin'] }} poin &middot; ambang {{ $it['need'] }} &middot; maks {{ $it['maks'] }}</span></div>
                    <div @class(['rv-bar', 'is-bad' => ! $it['pass']]) role="img" aria-label="{{ $it['name'] }}: {{ $it['poin'] }} dari ambang {{ $it['need'] }}">
                        <i style="width: {{ $w }}%"></i>
                        <u style="left: {{ $m }}%" data-l="{{ $it['need'] }}"></u>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="rv-note">
            {{ $pg['source'] }}.
            @if ($pg['mode'] === 'segment') Semua bagian harus mencapai ambangnya masing-masing; skor total tinggi tidak menutup satu bagian yang jatuh.@endif
            Ambang resmi bisa berubah tiap formasi atau periode seleksi, jadi cek pengumuman terbaru instansi.
        </p>
    </section>

    {{-- ============ Grafik ============ --}}
    <div class="rv-charts">
        <section class="rv-card">
            <p class="rv-h">Peta kemampuan</p>
            <svg class="rv-svg" viewBox="0 0 300 280" role="img" aria-label="Grafik radar skor tiap bagian dibanding ambang">
                @foreach ([25, 50, 75, 100] as $ring)
                    <polygon points="{{ $poly(array_fill(0, $n, $ring)) }}" fill="none" stroke="#e2e6ec" stroke-width="1"/>
                @endforeach
                @foreach ($segs as $i => $s)
                    @php [$x, $y] = $pt($i, 100); [$lx, $ly] = $pt($i, 122); @endphp
                    <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $x }}" y2="{{ $y }}" stroke="#e2e6ec"/>
                    <text x="{{ $lx }}" y="{{ $ly }}" font-size="11" font-weight="700" text-anchor="middle" dominant-baseline="middle">{{ $short($s['name']) }}</text>
                @endforeach
                <polygon points="{{ $poly($need) }}" fill="none" stroke="#1b2638" stroke-width="1.8" stroke-dasharray="5 4"/>
                @if ($hasPeers)<polygon points="{{ $poly($peerVals) }}" fill="none" stroke="#b7791f" stroke-width="1.8"/>@endif
                <polygon class="rv-poly-me" points="{{ $poly($mine) }}"/>
                @foreach ($mine as $i => $v)@php [$x, $y] = $pt($i, max(0, min(100, $v))); @endphp<circle cx="{{ $x }}" cy="{{ $y }}" r="3.6" fill="#3b6cd4"/>@endforeach
            </svg>
            <div class="rv-leg">
                <span><i style="background:#3b6cd4"></i>Skormu</span>
                <span><i style="background:#1b2638"></i>Ambang lulus</span>
                @if ($hasPeers)<span><i style="background:#b7791f"></i>Rata-rata peserta</span>@endif
            </div>
        </section>

        <section class="rv-card">
            <p class="rv-h">Komposisi jawaban</p>
            <div class="rv-donut" role="img" aria-label="{{ $st['benar'] }} benar, {{ $st['salah'] }} salah, {{ $st['kosong'] }} kosong">
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" stroke="#e2e6ec"/>
                    <circle class="seg" cx="18" cy="18" r="15.9155" stroke="#2f9e6b" stroke-dasharray="{{ $dB }} {{ 100 - $dB }}" stroke-dashoffset="0"/>
                    <circle class="seg" cx="18" cy="18" r="15.9155" stroke="#e5484d" stroke-dasharray="{{ $dS }} {{ 100 - $dS }}" stroke-dashoffset="{{ -$dB }}"/>
                    <circle class="seg" cx="18" cy="18" r="15.9155" stroke="#aab4c4" stroke-dasharray="{{ $dK }} {{ 100 - $dK }}" stroke-dashoffset="{{ -($dB + $dS) }}"/>
                </svg>
                <div class="rv-donut-c"><b>{{ $st['accuracy'] }}%</b><span>akurasi</span></div>
            </div>
            <div class="rv-leg">
                <span><i style="background:#2f9e6b"></i>{{ $st['benar'] }} benar</span>
                <span><i style="background:#e5484d"></i>{{ $st['salah'] }} salah</span>
                <span><i style="background:#aab4c4"></i>{{ $st['kosong'] }} kosong</span>
            </div>
        </section>
    </div>

    {{-- ============ Peringkat kamu: terkunci untuk non-Premium ============ --}}
    @if ($isPremium && $rank)
        <section class="rv-rank rv-rank-open">
            <div><p class="rv-h">Peringkat kamu</p><b>#{{ $rank['pos'] }} <em>dari {{ $rank['of'] }} peserta</em></b></div>
            <a href="{{ route('exam.leaderboard', ['jenis' => $attempt->type->code]) }}" class="btn-outline text-sm">Lihat papan peringkat</a>
        </section>
    @elseif (! $isPremium)
        <section class="rv-rank">
            <div class="rv-rank-fake" aria-hidden="true"><p class="rv-h">Peringkat kamu</p><b>#12 dari 340 peserta</b><p>Kamu di atas 96% peserta lain</p><div></div></div>
            <a href="{{ route('billing.index') }}" class="rv-rank-btn">
                <b><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Peringkat kamu</b>
                <small>Buka dengan Premium</small>
            </a>
        </section>
    @endif

    {{-- ============ Ringkasan oleh Owlspire (parafrase AI) ============ --}}
    @if ($aiOn)
        <section class="rv-card rv-ai" id="rv-ai" data-url="{{ route('exam.ai.summary', $attempt) }}">
            <span class="rv-ai-av"><x-owl :animated="true" /></span>
            <div>
                <p class="rv-ai-name">Ringkasan Owlspire <small>Dibuat dari hasil pengerjaanmu</small></p>
                <div class="rv-ai-body" data-out><div class="rv-sk" style="width:95%"></div><div class="rv-sk" style="width:88%"></div><div class="rv-sk" style="width:60%"></div></div>
            </div>
        </section>
        <script>
            (function () {
                var box = document.getElementById('rv-ai'); if (!box) return;
                var out = box.querySelector('[data-out]'), sk = out.innerHTML;
                var tok = document.querySelector('meta[name="csrf-token"]');
                function load() {
                    out.innerHTML = sk;
                    fetch(box.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': tok ? tok.content : '', 'Accept': 'application/json' } })
                        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); })
                        .then(function (j) { out.textContent = j.text; })
                        .catch(function (e) {
                            out.innerHTML = '';
                            var p = document.createElement('p'); p.textContent = (e && e.message) || 'Ringkasan belum bisa dibuat.';
                            var b = document.createElement('button'); b.type = 'button'; b.className = 'rv-retry'; b.textContent = 'Coba lagi'; b.onclick = load;
                            out.appendChild(p); out.appendChild(b);
                        });
                }
                load();
            })();
        </script>
    @endif
</div>
