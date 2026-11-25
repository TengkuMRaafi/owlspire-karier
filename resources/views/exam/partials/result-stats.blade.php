{{-- Statistik evaluasi diri hasil ujian. Data dari App\Services\Exam\ResultInsights. Gaya ditulis di sini supaya langsung jalan tanpa build ulang. --}}
@php
    $st = $insights['stats'];
    $peers = $insights['peers'];
    $rules = $attempt->type->passing_rules ?? [];
    $overallMin = $rules['min_percent'] ?? null;
    $fmtSec = fn ($s) => $s === null ? '-' : (intdiv($s, 60) > 0 ? intdiv($s, 60).' mnt '.($s % 60).' dtk' : $s.' dtk');
    $levelLabel = ['strong' => 'Kuat', 'ok' => 'Cukup', 'weak' => 'Perlu dikejar'];
    $short = function ($t, $n = 2) { $parts = preg_split('/(?<=[.!?])\s+/', trim((string) $t)); return implode(' ', array_slice($parts, 0, $n)); };
    $gapTxt = fn ($g) => rtrim(rtrim(number_format(abs($g), 1), '0'), '.');
    $segN = max(1, count($insights['segments']));
    $isFull = $isFull ?? false;
    $isPremium = $isPremium ?? false;
    $tabs = ['ringkasan' => 'Ringkasan', 'detail' => 'Detail', 'bahas' => 'Pembahasan'];
    $needReview = $st['salah'] + $st['kosong'];
    $tabIcons = [
        'ringkasan' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'detail' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'bahas' => '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2zM4 19V5M9 8h5M9 12h5"/>',
    ];
@endphp

<style>
    .rs { --l: #e2e6ec; --soft: #f4f6f9; --ink: #1b2638; --mut: #5d6b82; --acc: var(--color-accent-600, #3b6cd4); --good: #2f9e6b; --bad: #d9480f; --warn: #b7791f; color: var(--ink); }
    .rs [hidden] { display: none !important; }
    :where(.rs button) { font: inherit; cursor: pointer; }
    .rs-tabs { position: sticky; top: var(--hh, 3.6rem); z-index: 20; scroll-margin-top: calc(var(--hh, 3.6rem) + .5rem); display: flex; flex-wrap: wrap; gap: .3rem; margin-top: 0; padding: .3rem; border: 1px solid var(--l); border-radius: 1rem; background: #fff; box-shadow: 0 6px 16px -12px rgba(27,38,56,.5); }
    .rs-tab { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; flex: 1 1 auto; text-align: center; padding: .7rem 1.1rem; border: 0; border-radius: .75rem; background: transparent; font-size: .88rem; font-weight: 700; color: var(--mut); white-space: nowrap; }
    .rs-tab:hover { background: var(--soft); color: var(--ink); }
    .rs-tab[aria-selected="true"] { background: var(--ink); color: #fff; }
    .rs-tab svg { width: 1.05rem; height: 1.05rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; flex: none; }
    .rs-tab em { min-width: 1.35rem; padding: .05rem .4rem; border-radius: 99px; background: var(--bad); font-style: normal; font-size: .72rem; font-weight: 800; line-height: 1.3; color: #fff; }
    .rs-tab.is-hot:not([aria-selected="true"]) { background: #eef3ff; color: var(--acc); }
    .rs-tab.is-hot:not([aria-selected="true"]):hover { background: #e2eaff; }
    .rs-cta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem 1.2rem; margin-top: 1.2rem; padding: 1.05rem 1.3rem; border: 1.5px solid #c9d9f7; border-radius: 1.1rem; background: #eef3ff; }
    .rs-cta b { display: block; font-size: 1rem; } .rs-cta span { display: block; margin-top: .15rem; font-size: .82rem; color: var(--mut); }
    .rs-cta button { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.2rem; border: 0; border-radius: .8rem; background: var(--acc); font-size: .88rem; font-weight: 700; color: #fff; transition: transform .15s; }
    .rs-cta button:hover { transform: translateY(-1px); }
    .rs-cta button svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
    .rs-pane { margin-top: .9rem; }
    .rs-h { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); margin: 1rem 0 .6rem; }
    .rs-h:first-child { margin-top: 0; }
    .rs-row { display: grid; gap: .9rem; }
    .rs-c { padding: 1.05rem 1.2rem; border: 1px solid var(--l); border-left: 5px solid var(--acc); border-radius: 1.1rem; background: #fff; min-width: 0; }
    .rs-c.is-strong { border-left-color: var(--good); } .rs-c.is-weak { border-left-color: var(--bad); }
    .rs-c h3 { font-size: 1rem; font-weight: 800; }
    .rs-c > p { margin-top: .15rem; font-size: .78rem; color: var(--mut); font-variant-numeric: tabular-nums; }
    .rs-badge { display: inline-block; margin-top: .6rem; padding: .22rem .65rem; border-radius: 99px; font-size: .72rem; font-weight: 800; background: var(--soft); }
    .rs-badge.is-strong { background: #e3f5ec; color: #1f7a50; } .rs-badge.is-weak { background: #fdebe3; color: #b13a08; } .rs-badge.is-ok { background: #fdf3de; color: #8a5a0f; }
    .rs-track { position: relative; height: .6rem; margin-top: .9rem; border-radius: 99px; background: var(--l); }
    .rs-track > i { position: absolute; inset: 0 auto 0 0; border-radius: 99px; background: var(--acc); }
    .rs-track > u { position: absolute; top: -.3rem; bottom: -.3rem; width: 2px; background: var(--ink); }
    .rs-track > s { position: absolute; top: -.2rem; bottom: -.2rem; width: 2px; background: var(--warn); }
    .rs-legend { display: flex; flex-wrap: wrap; gap: .2rem .9rem; margin-top: .5rem; font-size: .72rem; color: var(--mut); }
    .rs-split { display: flex; height: .45rem; margin-top: .8rem; border-radius: 99px; overflow: hidden; background: var(--l); }
    .rs-split i { display: block; height: 100%; } .rs-split .b { background: var(--good); } .rs-split .s { background: #e5484d; } .rs-split .k { background: #aab4c4; }
    .rs-split-l { display: flex; gap: .9rem; margin-top: .4rem; font-size: .76rem; color: var(--mut); } .rs-split-l b { color: var(--ink); }
    .rs-prio { margin: 0; padding: 0; list-style: none; counter-reset: p; display: grid; gap: .8rem; }
    .rs-prio li { counter-increment: p; padding: .85rem 1.05rem; border: 1px solid var(--l); border-radius: 1rem; background: #fff; font-size: .84rem; }
    .rs-prio li::before { content: counter(p); display: grid; place-items: center; width: 1.6rem; height: 1.6rem; margin-bottom: .5rem; border-radius: 99px; background: var(--ink); font-size: .78rem; font-weight: 800; color: #fff; }
    .rs-prio b { display: block; } .rs-prio small { color: var(--mut); font-size: .76rem; } .rs-prio em { display: block; margin-top: .3rem; font-style: normal; font-weight: 800; color: var(--acc); }
    .rs-sub { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .9rem; }
    .rs-sub button { padding: .45rem 1rem; border: 1.5px solid var(--l); border-radius: 99px; background: #fff; font-size: .82rem; font-weight: 700; color: var(--mut); }
    .rs-sub button[aria-selected="true"] { border-color: var(--acc); background: var(--acc); color: #fff; }
    .rs-two { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; align-items: stretch; }
    .rs-mini { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin: .9rem 0 0; padding: 0; list-style: none; }
    .rs-mini li { padding: .55rem .75rem; border-radius: .75rem; background: var(--soft); }
    .rs-mini b { display: block; font-size: 1rem; font-variant-numeric: tabular-nums; } .rs-mini span { font-size: .7rem; color: var(--mut); }
    .rs-dl { display: grid; gap: .8rem; }
    .rs-dl h4 { font-size: .66rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: var(--acc); }
    .rs-dl .d h4 { color: var(--bad); } .rs-dl .t h4 { color: var(--good); }
    .rs-dl p { margin-top: .2rem; font-size: .84rem; line-height: 1.55; }
    .rs-gain { margin-top: .9rem; padding: .65rem .9rem; border-radius: .8rem; background: #eef3ff; font-size: .82rem; line-height: 1.5; }
    .rs-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: .9rem; align-items: stretch; }
    .rs-col { padding: 1.1rem 1.2rem; border: 1px solid var(--l); border-top: 4px solid var(--acc); border-radius: 1.1rem; background: #fff; }
    .rs-col.is-good { border-top-color: var(--good); } .rs-col.is-weak { border-top-color: var(--bad); } .rs-col.is-tip { border-top-color: var(--warn); }
    .rs-col > h3 { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .rs-col.is-good > h3 { color: var(--good); } .rs-col.is-weak > h3 { color: var(--bad); } .rs-col.is-tip > h3 { color: var(--warn); }
    .rs-col article { margin-top: .75rem; } .rs-col article + article { margin-top: .85rem; padding-top: .85rem; border-top: 1px dashed var(--l); }
    .rs-col h4 { font-size: .9rem; font-weight: 800; } .rs-col p { margin-top: .2rem; font-size: .82rem; line-height: 1.55; color: #33415a; }
    .rs-rvbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .7rem; }
    .rs-rvbar p { font-size: .8rem; color: var(--mut); }
    .rs-fl { display: flex; gap: .4rem; }
    .rs-fl button { padding: .35rem .9rem; border: 1.5px solid var(--l); border-radius: 99px; background: #fff; font-size: .8rem; font-weight: 700; color: var(--mut); }
    .rs-fl button[aria-pressed="true"] { border-color: var(--acc); background: var(--acc); color: #fff; }
    .rs-list { max-height: 34rem; overflow-y: auto; border: 1px solid var(--l); border-radius: 1.1rem; background: #fff; }
    .rs-q { border-bottom: 1px solid var(--l); } .rs-q:last-child { border-bottom: 0; }
    .rs-q summary { display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: .8rem; padding: .8rem 1.1rem; cursor: pointer; list-style: none; font-size: .86rem; }
    .rs-q summary::-webkit-details-marker { display: none; }
    .rs-q summary:hover { background: var(--soft); }
    .rs-q summary b { color: var(--mut); font-size: .78rem; white-space: nowrap; }
    .rs-q summary span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rs-q summary svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; transition: transform .2s; }
    .rs-q[open] summary svg { transform: rotate(180deg); }
    .rs-st { padding: .15rem .6rem; border-radius: 99px; font-size: .7rem; font-weight: 800; background: var(--soft); }
    .rs-st.benar { background: #e3f5ec; color: #1f7a50; } .rs-st.salah { background: #fdebe3; color: #b13a08; }
    .rs-qb { padding: .2rem 1.1rem 1.1rem; }
    .rs-qb ul { margin: .8rem 0 0; padding: 0; list-style: none; display: grid; gap: .4rem; font-size: .84rem; }
    .rs-qb li { display: flex; gap: .6rem; padding: .5rem .75rem; border: 1px solid var(--l); border-radius: .7rem; }
    .rs-qb li.k { border-color: #6fcf9f; background: #eefaf3; } .rs-qb li.x { border-color: #f0a98a; background: #fff3ee; }
    .rs-qb li span { flex: 1; } .rs-qb li em { font-style: normal; font-size: .72rem; font-weight: 800; }
    .rs-ex { margin-top: .8rem; padding: .8rem 1rem; border-radius: .8rem; background: var(--soft); font-size: .84rem; line-height: 1.55; }
    .rs-ai { margin-top: .8rem; }
    .rs-ai > button { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .9rem .4rem .5rem; border: 1.5px solid #dce8fb; border-radius: 99px; background: #eef4fd; font-size: .8rem; font-weight: 800; color: var(--acc); }
    .rs-ai > button svg { width: .95rem; height: auto; }
    .rs-ai-out { margin-top: .6rem; padding: .8rem 1rem; border: 1px solid #dce8fb; border-radius: .8rem; background: #f7faff; font-size: .84rem; line-height: 1.6; white-space: pre-line; }
    .rs-ai-out b { display: block; margin-bottom: .25rem; font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; color: var(--acc); }
    .rs-askbtn { display: inline-flex; align-items: center; gap: .45rem; margin-top: .8rem; padding: .3rem .85rem .3rem .4rem; border: 1.5px solid #dce8fb; border-radius: 99px; background: #eef4fd; font-size: .76rem; font-weight: 800; color: var(--acc); transition: background .15s, transform .15s; }
    .rs-askbtn:hover { background: #e2edfd; transform: translateY(-1px); }
    .rs-askbtn svg { width: .9rem; height: auto; }
    /* ---- Detail: grafik ---- */
    .dt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 19rem), 1fr)); gap: .9rem; align-items: stretch; }
    .dt-card { display: flex; flex-direction: column; padding: 1.1rem 1.2rem; border: 1px solid var(--l); border-radius: 1.2rem; background: #fff; min-width: 0; }
    .dt-t { margin: 0; font-size: .98rem; font-weight: 800; }
    .dt-s { margin: .15rem 0 0; font-size: .76rem; color: var(--mut); }
    .dt-svg { display: block; width: 100%; height: auto; margin-top: .7rem; }
    .dt-svg text { font-family: inherit; fill: var(--ink); }
    .dt-col { transform-origin: bottom; transform-box: fill-box; animation: dt-rise .8s cubic-bezier(.2, .8, .2, 1) both; }
    .dt-leg { display: flex; flex-wrap: wrap; gap: .2rem .9rem; margin-top: .6rem; font-size: .72rem; color: var(--mut); }
    .dt-leg i { display: inline-block; width: .7rem; height: .7rem; margin-right: .3rem; border-radius: 3px; vertical-align: -1px; }
    .dt-rows { display: grid; gap: .95rem; margin-top: .9rem; }
    .dt-row-h { display: flex; justify-content: space-between; gap: .6rem; font-size: .8rem; }
    .dt-row-h b { font-weight: 800; } .dt-row-h span { color: var(--mut); font-variant-numeric: tabular-nums; } .dt-row-h em { font-style: normal; font-weight: 800; color: #b7791f; }
    .dt-bar { position: relative; display: flex; height: .8rem; margin-top: .35rem; border-radius: 99px; background: var(--l); overflow: visible; }
    .dt-bar i { display: block; height: 100%; animation: dt-grow .9s cubic-bezier(.2, .8, .2, 1) both; transform-origin: left; }
    .dt-bar i:first-child { border-radius: 99px 0 0 99px; } .dt-bar i:last-of-type { border-radius: 0 99px 99px 0; } .dt-bar i:only-of-type { border-radius: 99px; }
    .dt-bar .a { background: var(--acc); }
    .dt-bar .r { background: repeating-linear-gradient(135deg, #f5c542 0 5px, #fbe08a 5px 10px); }
    .dt-bar .ok { background: var(--good); } .dt-bar .slow { background: #e5484d; }
    .dt-bar u { position: absolute; top: -.25rem; bottom: -.25rem; width: 2px; background: var(--ink); }
    .dt-why { margin: .9rem 0 0; padding-top: .8rem; border-top: 1px dashed var(--l); font-size: .82rem; line-height: 1.6; color: #33415a; }
    .dt-why b { color: var(--ink); }
    .dt-segs { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr)); gap: .9rem; margin-top: .2rem; }
    .dt-seg { display: flex; flex-direction: column; padding: 1.1rem 1.2rem; border: 1px solid var(--l); border-top: 4px solid var(--acc); border-radius: 1.2rem; background: #fff; min-width: 0; }
    .dt-seg.is-strong { border-top-color: var(--good); } .dt-seg.is-weak { border-top-color: var(--bad); }
    .dt-seg-h { display: flex; align-items: center; gap: .9rem; }
    .dt-gauge { position: relative; flex: none; width: 4.6rem; height: 4.6rem; }
    .dt-gauge svg { width: 100%; height: 100%; transform: rotate(-90deg); } .dt-gauge circle { fill: none; stroke-width: 4.6; }
    .dt-gauge .fg { stroke-linecap: round; animation: dt-dash 1s ease both; }
    .dt-gauge b { position: absolute; inset: 0; display: grid; place-content: center; font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .dt-seg-h h4 { margin: 0; font-size: .98rem; font-weight: 800; line-height: 1.25; } .dt-seg-h p { margin: .15rem 0 0; font-size: .76rem; color: var(--mut); font-variant-numeric: tabular-nums; }
    .dt-m { display: grid; gap: .65rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    .dt-m li { display: grid; grid-template-columns: 5.2rem 1fr 2.6rem; align-items: center; gap: .6rem; font-size: .76rem; }
    .dt-m span { color: var(--mut); font-weight: 700; } .dt-m b { text-align: right; font-variant-numeric: tabular-nums; }
    .dt-m .dt-bar { margin-top: 0; height: .55rem; }
    .dt-tip { display: flex; gap: .55rem; margin: 1rem 0 0; padding: .65rem .8rem; border-radius: .85rem; background: #eef3ff; font-size: .8rem; line-height: 1.5; }
    .dt-tip::before { content: ''; flex: none; width: .5rem; height: .5rem; margin-top: .4rem; border-radius: 50%; background: var(--acc); }
    .dt-seg.is-weak .dt-tip { background: #fdf0ea; } .dt-seg.is-weak .dt-tip::before { background: var(--bad); }
    .dt-seg.is-strong .dt-tip { background: #e9f7f0; } .dt-seg.is-strong .dt-tip::before { background: var(--good); }
    .dt-more { margin-top: .8rem; border-top: 1px dashed var(--l); padding-top: .6rem; }
    .dt-more summary { cursor: pointer; list-style: none; font-size: .78rem; font-weight: 800; color: var(--acc); }
    .dt-more summary::-webkit-details-marker { display: none; } .dt-more summary::after { content: ' +'; } .dt-more[open] summary::after { content: ' \2212'; }
    .dt-more dl { display: grid; gap: .6rem; margin: .7rem 0 0; } .dt-more dt { font-size: .64rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: var(--mut); }
    .dt-more dd { margin: .1rem 0 0; font-size: .82rem; line-height: 1.55; }
    .dt-seg .rs-askbtn { align-self: flex-start; margin-top: auto; }
    .dt-seg .dt-more + .rs-askbtn { margin-top: .9rem; }
    @keyframes dt-rise { from { transform: scaleY(0); } } @keyframes dt-grow { from { transform: scaleX(0); } } @keyframes dt-dash { from { stroke-dasharray: 0 100; } }
    @media (prefers-reduced-motion: reduce) { .dt-col, .dt-bar i, .dt-gauge .fg { animation: none; } }
    .rs-next { display: grid; grid-template-columns: repeat(2, 1fr); gap: .9rem; margin-top: 1rem; }
    .rs-next a { display: block; padding: 1.05rem 1.3rem; border: 1.5px solid var(--l); border-radius: 1.1rem; background: #fff; color: var(--ink); text-decoration: none; transition: transform .15s, border-color .2s; }
    .rs-next a:hover { transform: translateY(-2px); border-color: var(--acc); }
    .rs-next a.is-main { border-color: var(--acc); background: var(--acc); color: #fff; }
    .rs-next small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; opacity: .75; }
    .rs-next b { display: block; margin-top: .2rem; font-size: 1rem; } .rs-next span { display: block; margin-top: .25rem; font-size: .82rem; opacity: .85; }
    @media (max-width: 860px) {
        .rs-row, .rs-prio { grid-template-columns: 1fr !important; }
        .rs-tabs { flex-wrap: nowrap; }
        .rs-tab { flex: 1 1 0; flex-direction: column; gap: .15rem; padding: .55rem .25rem; font-size: .76rem; position: relative; }
        .rs-tab em { position: absolute; top: .2rem; right: .35rem; }
        .rs-cta button { width: 100%; justify-content: center; }
        .rs-two, .rs-cols, .rs-next { grid-template-columns: 1fr; }
        .rs-q summary { grid-template-columns: auto 1fr auto; } .rs-q summary svg { display: none; }
    }
    /* Kartu "Perkuat dulu / Uji lagi": isi rata kiri, tinggi sama, teks tidak bergerigi. Prefiks .rs menimpa mobile-final.css */
    .rs-next a { display: flex; flex-direction: column; align-items: stretch; text-align: left; }
    .rs-next b { text-wrap: balance; }
    @media (max-width: 639px) {
        .rs .rs-next { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; gap: .6rem; }
        .rs .rs-next a { padding: .8rem .75rem; }
        .rs .rs-next b { margin-top: .3rem; font-size: .9rem; line-height: 1.25; text-wrap: balance; }
        /* Deskripsi rata kiri-kanan diatur oleh rapi.js (pemenggalan suku kata + jarak huruf per baris), bukan hyphens:auto yang tidak ada kamus bahasa Indonesia-nya */
        .rs .rs-next span { margin-top: .35rem; font-size: .72rem; line-height: 1.5; }
    }
</style>

<div class="rs">
    @if ($isFull || $insights['passing']['items'] !== [])
        @include('exam.partials.result-verdict', ['insights' => $insights, 'attempt' => $attempt, 'isPremium' => $isPremium])
    @endif

    <div class="rs-tabs" role="tablist" aria-label="Isi hasil">
        @foreach ($tabs as $id => $label)
            <button type="button" @class(['rs-tab', 'is-hot' => $id === 'bahas']) role="tab" data-g="main" data-t="{{ $id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">{!! $tabIcons[$id] !!}</svg>{{ $label }}@if ($id === 'bahas' && $needReview > 0)<em>{{ $needReview }}</em>@endif
            </button>
        @endforeach
    </div>

    {{-- ============ Ringkasan ============ --}}
    <div class="rs-pane" data-g="main" data-p="ringkasan">
        <h3 class="rs-h">Skor tiap bagian @if ($overallMin)&middot; ambang lulus {{ $overallMin }}% @endif</h3>
        <div class="rs-row" style="grid-template-columns: repeat({{ min($segN, 4) }}, 1fr)">
            @foreach ($insights['segments'] as $seg)
                @php
                    $tot = max(1, $seg['total']); $thr = $seg['threshold'];
                    $verdict = $thr !== null ? ($seg['gap'] >= 0 ? 'Di atas ambang +'.$gapTxt($seg['gap']) : 'Di bawah ambang -'.$gapTxt($seg['gap'])) : $levelLabel[$seg['level']];
                @endphp
                <article @class(['rs-c', 'is-strong' => $seg['level'] === 'strong', 'is-weak' => $seg['level'] === 'weak'])>
                    <h3>{{ $seg['name'] }}</h3>
                    <p>{{ $seg['poin'] }} / {{ $seg['maks'] }} poin &middot; {{ $seg['pct'] }}%</p>
                    <span @class(['rs-badge', 'is-strong' => $seg['level'] === 'strong', 'is-weak' => $seg['level'] === 'weak', 'is-ok' => $seg['level'] === 'ok'])>{{ $verdict }}</span>
                    <div class="rs-track" role="img" aria-label="{{ $seg['name'] }} {{ $seg['pct'] }} persen">
                        <i style="width: {{ $seg['pct'] }}%"></i>
                        @if ($thr !== null)<u style="left: {{ min(100, $thr) }}%"></u>@endif
                        @if ($seg['peer_pct'] !== null)<s style="left: {{ min(100, $seg['peer_pct']) }}%"></s>@endif
                    </div>
                    <div class="rs-legend"><span>Skormu</span>@if ($thr !== null)<span>| Ambang {{ $gapTxt($thr) }}%</span>@endif @if ($seg['peer_pct'] !== null)<span>| Peserta {{ $seg['peer_pct'] }}%</span>@endif</div>
                    <div class="rs-split"><i class="b" style="width: {{ $seg['benar'] / $tot * 100 }}%"></i><i class="s" style="width: {{ $seg['salah'] / $tot * 100 }}%"></i><i class="k" style="width: {{ $seg['kosong'] / $tot * 100 }}%"></i></div>
                    <p class="rs-split-l"><span><b>{{ $seg['benar'] }}</b> benar</span><span><b>{{ $seg['salah'] }}</b> salah</span><span><b>{{ $seg['kosong'] }}</b> kosong</span></p>
                    @if ($seg['level'] !== 'strong')<button type="button" class="rs-askbtn" data-ask="Jelaskan hasilku di {{ $seg['name'] }}: kenapa skornya {{ $seg['pct'] }}% dan apa yang harus kulatih dulu?"><x-owl />Minta Owlspire jelaskan</button>@endif
                </article>
            @endforeach
        </div>
        @include('exam.partials.result-charts', ['insights' => $insights, 'st' => $st, 'overallMin' => $overallMin])

        <h3 class="rs-h">Prioritas belajar</h3>
        <ol class="rs-prio" style="grid-template-columns: repeat({{ max(1, min(3, count($insights['priority']))) }}, 1fr)">
            @foreach (array_slice($insights['priority'], 0, 3) as $seg)
                <li><b>{{ $seg['name'] }}</b><small>{{ $seg['lost'] }} soal salah/kosong &middot; {{ $seg['cause'] === 'waktu' ? 'kendala: waktu' : ($seg['cause'] === 'materi' ? 'kendala: materi' : ($seg['cause'] === 'kuat' ? 'pertahankan' : 'rapikan soal salah')) }}</small><em>+{{ $seg['recoverable'] }} poin</em></li>
            @endforeach
        </ol>

        <div class="rs-cta">
            <div><b>Lihat pembahasan soal</b><span>@if ($needReview > 0){{ $needReview }} soal salah atau kosong sudah dibahas satu per satu.@else Semua soal sudah dibahas satu per satu.@endif</span></div>
            <button type="button" data-jump="bahas">Buka pembahasan<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </div>
    </div>

    {{-- ============ Detail: per bagian dan analisis lanjutan ============ --}}
    <div class="rs-pane" data-g="main" data-p="detail" hidden>
        @php
            $dSegs = $insights['segments'];
            $sn = fn ($x) => preg_match('/\(([^)]+)\)/', $x, $m) ? $m[1] : \Illuminate\Support\Str::limit($x, 14);
            $allowed = $st['allowed_seconds'];
            $avgList = array_values(array_filter(array_map(fn ($x) => $x['avg_seconds'], $dSegs), fn ($v) => $v !== null));
            $tMax = max(1, (float) ($allowed ?? 0), ...array_map('floatval', $avgList)) * 1.15;
            $potTotal = array_sum(array_column($dSegs, 'recoverable'));
            $potTop = collect($dSegs)->sortByDesc('recoverable')->first();
            // Grafik kolom: presisi vs terjawab
            $gn = max(1, count($dSegs)); $gw = 310 / $gn; $bw = min(30, $gw * .28); $base = 150; $ph = 124;
            $guess = collect($dSegs)->filter(fn ($x) => $x['answered_rate'] >= 90 && $x['acc_answered'] < 50)->map(fn ($x) => $sn($x['name']))->values();
            $skip = collect($dSegs)->filter(fn ($x) => $x['answered_rate'] < 70)->map(fn ($x) => $sn($x['name']))->values();
            $slowSegs = collect($dSegs)->filter(fn ($x) => $x['avg_seconds'] !== null && $allowed !== null && $x['avg_seconds'] > $allowed)->map(fn ($x) => $sn($x['name']))->values();
            $fast = $st['avg_seconds'] !== null && $allowed !== null && $st['avg_seconds'] < $allowed * .4;
        @endphp

        <h3 class="rs-h">Grafik per bagian</h3>
        <div class="dt-grid">
            {{-- Presisi vs terjawab --}}
            <section class="dt-card">
                <h4 class="dt-t">Presisi dan terjawab</h4>
                <p class="dt-s">Berapa soal yang kamu jawab, dan berapa yang tepat</p>
                <svg class="dt-svg" viewBox="0 0 340 186" role="img" aria-label="Grafik kolom presisi dan persentase soal terjawab tiap bagian">
                    @foreach ([0, 50, 100] as $g)
                        @php $gy = $base - $g / 100 * $ph; @endphp
                        <line x1="28" y1="{{ $gy }}" x2="338" y2="{{ $gy }}" stroke="#e2e6ec" stroke-dasharray="{{ $g === 0 ? '0' : '3 4' }}"/>
                        <text x="22" y="{{ $gy + 3 }}" font-size="9" text-anchor="end" style="fill:#667794">{{ $g }}</text>
                    @endforeach
                    @foreach ($dSegs as $i => $x)
                        @php $cx0 = 28 + $gw * ($i + .5); $h1 = max(1, $x['acc_answered'] / 100 * $ph); $h2 = max(1, $x['answered_rate'] / 100 * $ph); @endphp
                        <rect class="dt-col" x="{{ $cx0 - $bw - 2 }}" y="{{ $base - $h1 }}" width="{{ $bw }}" height="{{ $h1 }}" rx="4" fill="#3b6cd4" style="animation-delay: {{ $i * .08 }}s"/>
                        <rect class="dt-col" x="{{ $cx0 + 2 }}" y="{{ $base - $h2 }}" width="{{ $bw }}" height="{{ $h2 }}" rx="4" fill="#5eb89a" style="animation-delay: {{ $i * .08 + .05 }}s"/>
                        <text x="{{ $cx0 - $bw / 2 - 2 }}" y="{{ $base - $h1 - 4 }}" font-size="9.5" font-weight="700" text-anchor="middle">{{ $x['acc_answered'] }}%</text>
                        <text x="{{ $cx0 + $bw / 2 + 2 }}" y="{{ $base - $h2 - 4 }}" font-size="9.5" font-weight="700" text-anchor="middle">{{ $x['answered_rate'] }}%</text>
                        <text x="{{ $cx0 }}" y="{{ $base + 17 }}" font-size="10.5" font-weight="700" text-anchor="middle">{{ $sn($x['name']) }}</text>
                    @endforeach
                </svg>
                <div class="dt-leg"><span><i style="background:#3b6cd4"></i>Presisi</span><span><i style="background:#5eb89a"></i>Terjawab</span></div>
                <p class="dt-why">
                    @if ($guess->isNotEmpty()) Hampir semua soal di <b>{{ $guess->implode(', ') }}</b> kamu jawab, tapi banyak yang meleset. Tandanya materi belum nempel atau jawaban masih menebak.
                    @elseif ($skip->isNotEmpty()) Banyak soal dikosongkan di <b>{{ $skip->implode(', ') }}</b>. Biasanya karena waktu atau ragu menjawab; latih tempo dulu.
                    @else Presisi dan jumlah soal terjawab cukup seimbang di semua bagian. @endif
                </p>
            </section>

            {{-- Potensi poin --}}
            <section class="dt-card">
                <h4 class="dt-t">Skor dan poin yang masih bisa diambil</h4>
                <p class="dt-s">Biru skormu, garis kuning potensi, garis hitam ambang lulus</p>
                <div class="dt-rows">
                    @foreach ($dSegs as $i => $x)
                        @php $pot = max(0, min(100 - $x['pct'], $x['maks'] > 0 ? $x['recoverable'] / $x['maks'] * 100 : 0)); @endphp
                        <div>
                            <div class="dt-row-h"><b>{{ $sn($x['name']) }}</b><span>{{ $x['poin'] }}/{{ $x['maks'] }} poin &middot; <em>+{{ $x['recoverable'] }}</em></span></div>
                            <div class="dt-bar" role="img" aria-label="{{ $x['name'] }} {{ $x['pct'] }} persen, potensi tambahan {{ $x['recoverable'] }} poin">
                                <i class="a" style="width: {{ $x['pct'] }}%; animation-delay: {{ $i * .08 }}s"></i><i class="r" style="width: {{ $pot }}%; animation-delay: {{ $i * .08 + .1 }}s"></i>
                                @if ($x['threshold'] !== null)<u style="left: {{ min(100, $x['threshold']) }}%"></u>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="dt-why">Potensi terbesar ada di <b>{{ $sn($potTop['name']) }}</b>, sekitar <b>+{{ $potTop['recoverable'] }} poin</b>. Kalau semua bagian dibereskan, total yang bisa kamu tambah kira-kira <b>{{ $potTotal }} poin</b>.</p>
            </section>

            {{-- Kecepatan --}}
            <section class="dt-card">
                <h4 class="dt-t">Waktu per soal</h4>
                <p class="dt-s">Garis hitam adalah jatah waktu rata-rata{{ $allowed !== null ? ' ('.$fmtSec($allowed).')' : '' }}</p>
                <div class="dt-rows">
                    @foreach ($dSegs as $i => $x)
                        <div>
                            <div class="dt-row-h"><b>{{ $sn($x['name']) }}</b><span>{{ $fmtSec($x['avg_seconds']) }}</span></div>
                            <div class="dt-bar" role="img" aria-label="{{ $x['name'] }} {{ $fmtSec($x['avg_seconds']) }} per soal">
                                @if ($x['avg_seconds'] !== null)<i class="{{ $allowed !== null && $x['avg_seconds'] > $allowed ? 'slow' : 'ok' }}" style="width: {{ min(100, $x['avg_seconds'] / $tMax * 100) }}%; animation-delay: {{ $i * .08 }}s"></i>@endif
                                @if ($allowed !== null)<u style="left: {{ min(100, $allowed / $tMax * 100) }}%"></u>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="dt-why">
                    @if ($allowed === null) Jatah waktu belum tersedia untuk ujian ini.
                    @elseif ($slowSegs->isNotEmpty()) Waktu di <b>{{ $slowSegs->implode(', ') }}</b> melewati jatah. Latih tempo di bagian itu supaya tidak kehabisan waktu.
                    @elseif ($fast) Kamu menjawab jauh lebih cepat dari jatah (<b>{{ $fmtSec($st['avg_seconds']) }}</b> dari <b>{{ $fmtSec($allowed) }}</b>). Pakai waktu lebih untuk membaca soal dan memeriksa ulang.
                    @else Semua bagian masih dalam jatah waktu, jadi kendala skor lebih ke materi dan ketelitian. @endif
                </p>
            </section>
        </div>

        @if ($isFull)
            <h3 class="rs-h">Analisis lanjutan</h3>
            @include('exam.partials.result-analysis', ['insights' => $insights, 'attempt' => $attempt, 'st' => $st, 'fmtSec' => $fmtSec, 'overallMin' => $overallMin])
        @endif

        <div class="rs-cta">
            <div><b>Lihat pembahasan soal</b><span>@if ($needReview > 0){{ $needReview }} soal salah atau kosong sudah dibahas satu per satu.@else Semua soal sudah dibahas satu per satu.@endif</span></div>
            <button type="button" data-jump="bahas">Buka pembahasan<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </div>
    </div>

    {{-- ============ Pembahasan: baris yang bisa dibuka, scroll internal ============ --}}
    <div class="rs-pane" data-g="main" data-p="bahas" hidden>
        <div class="rs-rvbar">
            <p>{{ $st['salah'] }} salah &middot; {{ $st['kosong'] }} kosong &middot; {{ $st['benar'] }} benar. @auth Soal salah masuk antrean ulang di akunmu.@endauth</p>
            <div class="rs-fl" role="group" aria-label="Saring soal">
                @foreach (['semua' => 'Semua soal', 'perlu' => 'Perlu dibahas'] as $key => $label)
                    <button type="button" data-filter="{{ $key }}" aria-pressed="{{ $key === 'semua' ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <div class="rs-list">
            @foreach ($review as $row)
                <details class="rs-q" data-status="{{ $row['status'] }}">
                    <summary><b>No. {{ $row['no'] }}</b><span>{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $row['body']), 90) }}</span><i class="rs-st {{ $row['status'] }}">{{ ucfirst($row['status']) }}</i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></summary>
                    <div class="rs-qb">
                        <p style="font-size:.7rem;font-weight:800;color:var(--mut)">{{ $row['segment'] }}</p>
                        <p class="whitespace-pre-line" style="margin-top:.3rem;font-weight:600;line-height:1.55">{{ $row['body'] }}</p>
                        @if (! empty($row['image']))
                            <img src="{{ $row['image'] }}" alt="Gambar soal" loading="lazy" style="display:block;max-width:100%;max-height:18rem;margin:.7rem auto 0;border:1px solid var(--l);border-radius:.8rem;background:#fff;object-fit:contain">
                        @endif
                        <ul>
                            @foreach ($row['options'] as $key => $text)
                                @php $isKey = $key === $row['answer']; $isPick = $key === $row['selected']; @endphp
                                <li @class(['k' => $isKey, 'x' => $isPick && ! $isKey])><b>{{ $key }}.</b><span>{{ $text }}</span>@if ($isKey)<em>Kunci</em>@elseif ($isPick)<em>Jawabanmu</em>@endif</li>
                            @endforeach
                        </ul>
                        @if ($row['explanation'])<p class="rs-ex"><b>Pembahasan.</b> {{ $row['explanation'] }}</p>@endif
                        @if (($aiOn ?? false) && $row['explanation'])
                            <div class="rs-ai"><button type="button" data-ai="{{ $row['qid'] }}"><x-owl />Jelaskan dengan kata lain</button></div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </div>

    @php $weakest = $insights['priority'][0] ?? null; $learnUrl = auth()->check() ? route('account.learning') : route('learning.index'); @endphp
    @include('exam.partials.result-rank', ['insights' => $insights, 'attempt' => $attempt, 'isPremium' => $isPremium])

    <div class="rs-next">
        <a href="{{ $learnUrl }}"><small>Perkuat dulu</small><b>Buka modul belajar</b><span>{{ $weakest ? 'Mulai dari '.$weakest['name'].'.' : 'Tutup bagian yang masih lemah.' }}</span></a>
        <a href="{{ route('exam.show', $attempt->type) }}" class="is-main"><small>Uji lagi</small><b>Kerjakan ujian penuh</b><span>Batas waktu dan passing grade seperti aslinya.</span></a>
    </div>
</div>

<script>
    (function () {
        var root = document.currentScript.previousElementSibling; if (!root) return;
        var hdr = document.querySelector('.site-header');
        function setHh() { root.style.setProperty('--hh', (hdr ? Math.ceil(hdr.getBoundingClientRect().height) : 0) + 'px'); }
        setHh(); window.addEventListener('resize', setHh);
        var tabsBar = root.querySelector('.rs-tabs');
        function toTabs() { var top = tabsBar.getBoundingClientRect().top, hh = hdr ? hdr.getBoundingClientRect().height : 0; if (top <= hh + 4 || top < 0) { tabsBar.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }
        root.addEventListener('click', function (e) {
            var j = e.target.closest('[data-jump]');
            if (j) {
                var tab = root.querySelector('.rs-tab[data-t="' + j.getAttribute('data-jump') + '"]');
                if (tab) { tab.click(); tabsBar.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                return;
            }
            var t = e.target.closest('[data-t]');
            if (t) {
                var g = t.getAttribute('data-g'), id = t.getAttribute('data-t');
                [].forEach.call(root.querySelectorAll('[data-t][data-g="' + g + '"]'), function (x) { x.setAttribute('aria-selected', String(x === t)); });
                [].forEach.call(root.querySelectorAll('[data-p][data-g="' + g + '"]'), function (p) { p.hidden = p.getAttribute('data-p') !== id; });
                if (g === 'main') { toTabs(); }
                return;
            }
            var a = e.target.closest('[data-ai]');
            if (a) {
                var holder = a.parentNode, old = holder.querySelector('.rs-ai-out'); if (old) { old.remove(); }
                var out = document.createElement('div'); out.className = 'rs-ai-out'; out.textContent = 'Owlspire sedang menulis ulang...'; holder.appendChild(out);
                var tok = document.querySelector('meta[name="csrf-token"]'); a.disabled = true;
                var fd = new FormData(); fd.append('question_id', a.getAttribute('data-ai'));
                fetch('{{ route('exam.ai.explain', $attempt) }}', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': tok ? tok.content : '', 'Accept': 'application/json' } })
                    .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); })
                    .then(function (j) { out.textContent = ''; var h = document.createElement('b'); h.textContent = 'Penjelasan Owlspire'; out.appendChild(h); out.appendChild(document.createTextNode(j.text)); })
                    .catch(function (x) { out.textContent = (x && x.message) || 'Penjelasan belum bisa dibuat.'; })
                    .finally(function () { a.disabled = false; });
                return;
            }
            var f = e.target.closest('[data-filter]');
            if (f) {
                var k = f.getAttribute('data-filter');
                [].forEach.call(root.querySelectorAll('[data-filter]'), function (b) { b.setAttribute('aria-pressed', String(b === f)); });
                [].forEach.call(root.querySelectorAll('.rs-q'), function (q) { q.hidden = k === 'perlu' && q.getAttribute('data-status') === 'benar'; });
            }
        });
    })();
</script>
