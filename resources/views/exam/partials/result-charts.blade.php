{{-- Gambaran cepat untuk tab Ringkasan, semua mode: radar kemampuan + komposisi jawaban. --}}
@php
    $segs = $insights['segments'];
    $n = count($segs); $cx = 150; $cy = 140; $R = 90;
    $pt = fn ($i, $v) => [round($cx + $R * $v / 100 * sin(2 * M_PI * $i / max(1, $n)), 1), round($cy - $R * $v / 100 * cos(2 * M_PI * $i / max(1, $n)), 1)];
    $poly = function ($vals) use ($pt) { $o = []; foreach ($vals as $i => $v) { $o[] = implode(',', $pt($i, max(0, min(100, $v)))); } return implode(' ', $o); };
    $sn = fn ($x) => preg_match('/\(([^)]+)\)/', $x, $m) ? $m[1] : $x;
    $mine = array_map(fn ($s) => $s['pct'], $segs);
    $need = array_map(fn ($s) => $s['threshold'] ?? $overallMin ?? 0, $segs);
    $peerVals = array_map(fn ($s) => $s['peer_pct'], $segs);
    $hasPeers = $n > 0 && ! in_array(null, $peerVals, true);
    $best = collect($segs)->sortByDesc('pct')->first(); $worst = collect($segs)->sortBy('pct')->first();
    $under = collect($segs)->filter(fn ($s) => $s['threshold'] !== null && $s['gap'] < 0);
    $tot = max(1, $st['total']);
    $dB = $st['benar'] / $tot * 100; $dS = $st['salah'] / $tot * 100; $dK = $st['kosong'] / $tot * 100;
@endphp
<style>
    .ra { display: grid; grid-template-columns: 1.15fr .85fr; gap: .9rem; }
    .ra-card { padding: 1.15rem 1.25rem; border: 1px solid var(--l); border-radius: 1.1rem; background: #fff; min-width: 0; }
    .ra-card.is-wide { grid-column: 1 / -1; }
    .ra-svg { display: block; width: 100%; max-width: 20rem; height: auto; margin: .4rem auto 0; }
    .ra-svg text { font-family: inherit; fill: var(--ink); }
    .ra-me { fill: rgba(59,108,212,.22); stroke: var(--acc); stroke-width: 2.4; transform-origin: 150px 140px; animation: ra-pop .8s ease both; }
    .ra-leg { display: flex; flex-wrap: wrap; justify-content: center; gap: .25rem 1rem; margin-top: .5rem; font-size: .74rem; color: var(--mut); }
    .ra-leg i { display: inline-block; width: .7rem; height: .7rem; margin-right: .3rem; border-radius: 3px; vertical-align: -1px; }
    .ra-donut { position: relative; width: 10.5rem; height: 10.5rem; margin: .6rem auto 0; }
    .ra-donut svg { width: 100%; height: 100%; transform: rotate(-90deg); } .ra-donut circle { fill: none; stroke-width: 5.2; } .ra-donut .sg { animation: ra-dash 1s ease both; }
    .ra-donut-c { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
    .ra-donut-c b { font-size: 1.9rem; font-weight: 700; line-height: 1; } .ra-donut-c span { font-size: .7rem; color: var(--mut); }
    .ra-why { margin-top: .8rem; padding-top: .8rem; border-top: 1px dashed var(--l); font-size: .84rem; line-height: 1.6; color: #33415a; }
    .ra-why b { color: var(--ink); }
    .ra-time { display: grid; gap: .8rem; margin-top: .8rem; }
    .ra-time-top { display: flex; justify-content: space-between; gap: .5rem; font-size: .82rem; } .ra-time-top span { color: var(--mut); font-variant-numeric: tabular-nums; }
    .ra-bar { position: relative; height: .75rem; margin-top: .35rem; border-radius: 99px; background: var(--l); }
    .ra-bar > i { position: absolute; inset: 0 auto 0 0; border-radius: 99px; background: var(--good); animation: ra-grow .9s ease both; transform-origin: left; } .ra-bar.is-slow > i { background: var(--bad); }
    .ra-bar > u { position: absolute; top: -.3rem; bottom: -.3rem; width: 3px; border-radius: 2px; background: var(--ink); }
    .ra-ai { grid-column: 1 / -1; display: grid; grid-template-columns: auto 1fr; gap: 1rem; padding: 1.2rem 1.3rem; border: 1px solid #dce8fb; border-radius: 1.1rem; background: linear-gradient(180deg, #f7faff, #fff); }
    .ra-av { display: grid; place-items: center; width: 3.2rem; height: 3.2rem; border-radius: 50%; background: #eef4fd; border: 1px solid #dce8fb; } .ra-av svg { width: 1.7rem; height: auto; }
    .ra-ai-name { font-size: 1rem; font-weight: 800; } .ra-ai-name small { display: block; font-size: .72rem; font-weight: 600; color: var(--mut); }
    .ra-ai-body { margin-top: .7rem; font-size: .9rem; line-height: 1.7; color: #33415a; white-space: pre-line; }
    .ra-sk { height: .7rem; margin-top: .6rem; border-radius: 99px; background: linear-gradient(90deg, var(--soft), #e8edf5, var(--soft)); background-size: 200% 100%; animation: ra-sh 1.2s linear infinite; }
    .ra-note { margin-top: .6rem; font-size: .72rem; color: var(--mut); }
    @keyframes ra-grow { from { transform: scaleX(0); } } @keyframes ra-pop { from { opacity: 0; transform: scale(.4); } }
    @keyframes ra-dash { from { stroke-dasharray: 0 100; } } @keyframes ra-sh { to { background-position: -200% 0; } }
    @media (max-width: 860px) { .ra { grid-template-columns: 1fr; } }
    @media (prefers-reduced-motion: reduce) { .ra * { animation: none !important; } }
</style>
<h3 class="rs-h">Gambaran cepat</h3>
<div class="ra">
    <section class="ra-card">
        <h3 class="rs-h">Peta kemampuan</h3>
        <svg class="ra-svg" viewBox="0 0 300 280" role="img" aria-label="Grafik radar skor tiap bagian dibanding ambang lulus">
            @foreach ([25, 50, 75, 100] as $ring)<polygon points="{{ $poly(array_fill(0, $n, $ring)) }}" fill="none" stroke="#e2e6ec"/>@endforeach
            @foreach ($segs as $i => $s)
                @php [$x, $y] = $pt($i, 100); [$lx, $ly] = $pt($i, 123); @endphp
                <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $x }}" y2="{{ $y }}" stroke="#e2e6ec"/>
                <text x="{{ $lx }}" y="{{ $ly }}" font-size="11" font-weight="700" text-anchor="middle" dominant-baseline="middle">{{ $sn($s['name']) }}</text>
            @endforeach
            <polygon points="{{ $poly($need) }}" fill="none" stroke="#1b2638" stroke-width="1.8" stroke-dasharray="5 4"/>
            @if ($hasPeers)<polygon points="{{ $poly($peerVals) }}" fill="none" stroke="#b7791f" stroke-width="1.8"/>@endif
            <polygon class="ra-me" points="{{ $poly($mine) }}"/>
            @foreach ($mine as $i => $v)@php [$x, $y] = $pt($i, max(0, min(100, $v))); @endphp<circle cx="{{ $x }}" cy="{{ $y }}" r="3.6" fill="#3b6cd4"/>@endforeach
        </svg>
        <div class="ra-leg"><span><i style="background:#3b6cd4"></i>Skormu</span><span><i style="background:#1b2638"></i>Ambang lulus</span>@if ($hasPeers)<span><i style="background:#b7791f"></i>Rata-rata peserta</span>@endif</div>
        <p class="ra-why">
            Bagian terkuatmu <b>{{ $sn($best['name']) }} ({{ $best['pct'] }}%)</b>, yang paling rendah <b>{{ $sn($worst['name']) }} ({{ $worst['pct'] }}%)</b>.
            @if ($under->isNotEmpty()) Garis putus-putus adalah ambang lulus; bagian yang masih di dalam garis itu: <b>{{ $under->map(fn ($s) => $sn($s['name']))->implode(', ') }}</b>. Makin dekat bentuk biru ke garis, makin dekat kamu ke lolos.
            @else Semua bagian sudah di luar garis ambang, jadi fokusnya menjaga konsistensi. @endif
        </p>
    </section>

    <section class="ra-card">
        <h3 class="rs-h">Komposisi jawaban</h3>
        <div class="ra-donut" role="img" aria-label="{{ $st['benar'] }} benar, {{ $st['salah'] }} salah, {{ $st['kosong'] }} kosong">
            <svg viewBox="-2 -2 40 40">
                <circle cx="18" cy="18" r="15.9155" stroke="#e2e6ec"/>
                <circle class="sg" cx="18" cy="18" r="15.9155" stroke="#2f9e6b" stroke-dasharray="{{ $dB }} {{ 100 - $dB }}"/>
                <circle class="sg" cx="18" cy="18" r="15.9155" stroke="#e5484d" stroke-dasharray="{{ $dS }} {{ 100 - $dS }}" stroke-dashoffset="{{ -$dB }}"/>
                <circle class="sg" cx="18" cy="18" r="15.9155" stroke="#aab4c4" stroke-dasharray="{{ $dK }} {{ 100 - $dK }}" stroke-dashoffset="{{ -($dB + $dS) }}"/>
            </svg>
            <div class="ra-donut-c"><b>{{ $st['accuracy'] }}%</b><span>akurasi</span></div>
        </div>
        <div class="ra-leg"><span><i style="background:#2f9e6b"></i>{{ $st['benar'] }} benar</span><span><i style="background:#e5484d"></i>{{ $st['salah'] }} salah</span><span><i style="background:#aab4c4"></i>{{ $st['kosong'] }} kosong</span></div>
        <p class="ra-why">
            Presisimu <b>{{ $st['precision'] }}%</b> (benar dari soal yang kamu jawab).
            @if ($st['kosong'] > $st['salah']) Soal kosong lebih banyak daripada salah, jadi kemungkinan besar kendalanya waktu atau keberanian menjawab, bukan pemahaman.
            @elseif ($st['salah'] > $st['kosong']) Soal salah lebih banyak daripada kosong, jadi rapikan konsep dan biasakan membaca semua opsi sebelum memilih.
            @else Salah dan kosong seimbang; latih tempo dan konsep secara bersamaan. @endif
        </p>
    </section>
</div>
