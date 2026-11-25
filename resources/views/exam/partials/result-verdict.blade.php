{{-- Kartu status passing grade (lebar penuh). Peringkat ada di result-rank, di bawah grafik. --}}
@php
    $pg = $insights['passing'];
    $trial = $attempt->mode !== 'full';
    $hasPg = $pg['items'] !== [];
    $ok = $pg['all_pass'];
    $miss = collect($pg['items'])->where('pass', false);
    $shortName = fn ($n) => preg_match('/\(([^)]+)\)/', $n, $m) ? $m[1] : $n;
    $title = $trial ? ($ok ? 'Di atas ambang' : 'Di bawah ambang') : ($ok ? 'Lolos' : 'Belum lolos');
    $lead = match (true) {
        $trial && $ok => 'Skormu sudah melewati ambang. Ini baru gambaran dari soal mode coba, bukan status lulus.',
        $trial && $pg['mode'] === 'segment' => 'Masih ada bagian di bawah ambang. Ini baru gambaran dari soal mode coba, bukan status lulus.',
        $trial => 'Skormu masih di bawah ambang. Ini baru gambaran dari soal mode coba, bukan status lulus.',
        $ok && $pg['mode'] === 'segment' => 'Semua bagian sudah mencapai ambangnya. Pertahankan dan coba lagi dengan batas waktu yang sama.',
        $ok => 'Skor totalmu sudah melewati ambang. Pertahankan dan coba lagi dengan batas waktu yang sama.',
        $pg['mode'] === 'segment' => 'Semua bagian harus mencapai ambangnya. Masih kurang di '.$miss->map(fn ($i) => $shortName($i['name']).' ('.abs($i['gap']).' poin)')->implode(', ').'.',
        default => 'Skor totalmu kurang '.abs($pg['items'][0]['gap']).' poin dari ambang. Perkuat bagian dengan skor paling rendah dulu.',
    };
@endphp

<style>
    .rv { display: grid; gap: .55rem; margin-bottom: .75rem; }
    .rv-pass { position: relative; overflow: hidden; display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: .55rem 1.6rem; padding: 1rem 1.3rem .8rem; border-radius: 1.1rem; color: #fff; background: radial-gradient(120% 160% at 50% -40%, #2fbf85 0%, #137a50 55%, #0b4d33 100%); box-shadow: 0 14px 30px -22px rgba(13,95,62,.7); }
    .rv-pass.is-fail { background: radial-gradient(120% 160% at 50% -40%, #f07a3a 0%, #c3430f 55%, #7d2606 100%); box-shadow: 0 14px 30px -22px rgba(160,56,10,.7); }
    .rv-pass::before, .rv-pass::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none; }
    .rv-pass::before { width: 9rem; height: 9rem; left: -3rem; bottom: -5rem; } .rv-pass::after { width: 7rem; height: 7rem; right: -2rem; top: -3rem; }
    .rv-pass > * { position: relative; }
    .rv-eyebrow { margin: 0 0 .4rem; font-size: .66rem; font-weight: 800; letter-spacing: .14em; line-height: 1.5; text-transform: uppercase; opacity: .85; }
    .rv-main { display: flex; align-items: center; gap: .9rem; min-width: 0; }
    .rv-icon { display: grid; flex: none; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 50%; background: rgba(255,255,255,.2); animation: rv-pop .6s cubic-bezier(.2,.9,.3,1.3) both; }
    .rv-icon svg { width: 1.3rem; height: 1.3rem; fill: none; stroke: #fff; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
    .rv-title { margin-top: 0; font-family: var(--font-display, inherit); font-size: clamp(1.35rem, 3vw, 1.85rem); font-weight: 700; line-height: 1.1; text-transform: uppercase; }
    .rv-lead { max-width: 34rem; margin: .5rem 0 0; font-size: .84rem; line-height: 1.55; opacity: .95; }
    .rv-items { display: flex; gap: .6rem; }
    .rv-it { flex: 0 0 12.5rem; padding: .6rem .8rem; border-radius: .85rem; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); text-align: left; }
    .rv-it-name { display: flex; align-items: center; justify-content: space-between; gap: .4rem; font-size: .76rem; font-weight: 800; }
    .rv-chip { padding: .05rem .5rem; border-radius: 99px; background: rgba(255,255,255,.24); font-size: .64rem; font-weight: 800; white-space: nowrap; }
    .rv-it-num { margin-top: .2rem; font-size: 1.35rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .rv-it-num small { font-size: .7rem; font-weight: 600; opacity: .8; }
    .rv-bar { position: relative; height: .45rem; margin-top: .5rem; border-radius: 99px; background: rgba(255,255,255,.25); }
    .rv-bar > i { position: absolute; inset: 0 auto 0 0; border-radius: 99px; background: #fff; transform-origin: left; animation: rv-grow .9s cubic-bezier(.2,.8,.2,1) both; }
    .rv-bar > u { position: absolute; top: -.2rem; bottom: -.2rem; width: 3px; border-radius: 2px; background: #1b2638; }
    .rv-src { grid-column: 1 / -1; font-size: .7rem; line-height: 1.5; opacity: .85; }
    .rv-src summary { display: inline-block; cursor: pointer; font-weight: 700; list-style: none; text-decoration: underline; text-underline-offset: 2px; }
    .rv-src summary::-webkit-details-marker { display: none; }
    .rv-src p { max-width: 48rem; margin-top: .3rem; }
    @keyframes rv-grow { from { transform: scaleX(0); } } @keyframes rv-pop { from { opacity: 0; transform: scale(.3); } }
    @media (max-width: 860px) { .rv-pass { grid-template-columns: 1fr; gap: .9rem; padding: 1.1rem 1.1rem .9rem; } .rv-main { align-items: flex-start; } .rv-items { flex-direction: column; gap: .7rem; } .rv-it { flex: none; padding: .75rem .9rem; } .rv-it-num { margin-top: .3rem; } .rv-bar { margin-top: .7rem; } .rv-pbar p { white-space: normal; } }
    @media (min-width: 861px) and (max-width: 1100px) { .rv-items { flex-wrap: wrap; } .rv-it { flex: 1 1 10rem; } }
    @media (prefers-reduced-motion: reduce) { .rv * { animation: none !important; } }
</style>

<div class="rv">
    @if ($hasPg)
        <section @class(['rv-pass', 'is-fail' => ! $ok]) aria-labelledby="rv-verdict">
            <div class="rv-main">
                <span class="rv-icon" aria-hidden="true"><svg viewBox="0 0 24 24">@if ($ok)<path d="M5 12.5l4.5 4.5L19 7.5"/>@else<path d="M6 6l12 12M18 6L6 18"/>@endif</svg></span>
                <div>
                    <p class="rv-eyebrow">{{ $trial ? 'Perkiraan passing grade · mode coba' : 'Passing grade' }} · {{ $attempt->type->name }}</p>
                    <h2 class="rv-title" id="rv-verdict">{{ $title }}</h2>
                    <p class="rv-lead">{{ $lead }}</p>
                </div>
            </div>

            <div class="rv-items">
                @foreach ($pg['items'] as $it)
                    @php $w = $it['maks'] > 0 ? min(100, $it['poin'] / $it['maks'] * 100) : 0; $m = $it['maks'] > 0 ? min(100, $it['need'] / $it['maks'] * 100) : 0; @endphp
                    <div class="rv-it">
                        <div class="rv-it-name"><span>{{ $shortName($it['name']) }}</span><span class="rv-chip">{{ $it['pass'] ? 'Lolos' : 'Kurang '.abs($it['gap']) }}</span></div>
                        <p class="rv-it-num">{{ $it['poin'] }} <small>poin · ambang {{ $it['need'] }}</small></p>
                        <div class="rv-bar" role="img" aria-label="{{ $it['name'] }}: {{ $it['poin'] }} poin, ambang {{ $it['need'] }}"><i style="width: {{ $w }}%"></i><u style="left: {{ $m }}%"></u></div>
                    </div>
                @endforeach
            </div>
            <details class="rv-src">
                <summary>Tentang ambang ini</summary>
                <p>{{ $pg['source'] }}.{{ $trial ? ' Soal mode coba lebih sedikit, jadi ambang dihitung proporsional.' : '' }} Ambang resmi bisa berbeda tiap formasi dan periode, jadi cek pengumuman terbaru instansi.</p>
            </details>
        </section>
    @endif

</div>
