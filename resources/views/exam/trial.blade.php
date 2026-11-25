@extends('layouts.app')

@section('title', 'Coba simulasi '.$minutes.' menit')
@section('description', 'Coba simulasi: '.count($questions).' soal kasus kerja nyata dalam '.$minutes.' menit, satu soal per layar.')

@section('content')
    @php $total = count($questions); @endphp
    <style>
        .tx-wrap { max-width: 64rem; }
        .tx { --ink: #1b2638; --mut: #5b6b82; --line: #e2e6ec; --acc: #3b6cd4; --amb: #f0b429; --navy: #101b3c; --soft: #f3f5f8;
            position: relative; border: 1px solid var(--line); border-radius: 1.6rem; background: #fff; overflow: hidden; overflow: clip;
            box-shadow: 0 1px 2px rgb(27 38 56 / .06), 0 28px 60px -34px rgb(27 38 56 / .35); color: var(--ink); }
        .tx [hidden] { display: none !important; }
        .tx button { font: inherit; cursor: pointer; }
        .tx svg { display: block; }

        /* ---------- Pembuka ---------- */
        .tx-intro { display: grid; grid-template-columns: 1.15fr 1fr; }
        .tx-i-main { padding: 2.6rem 2.6rem 2.4rem; display: flex; flex-direction: column; justify-content: center; }
        .tx-eyebrow { display: inline-flex; align-items: center; gap: .5rem; font-size: .72rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--acc); }
        .tx-eyebrow i { width: .5rem; height: .5rem; border-radius: 99px; background: var(--amb); }
        .tx-h { margin-top: .7rem; font-size: clamp(1.7rem, 3vw, 2.2rem); font-weight: 700; letter-spacing: -.02em; line-height: 1.15; }
        .tx-h em { font-style: normal; color: var(--acc); }
        .tx-lead { margin-top: .8rem; max-width: 30rem; font-size: .95rem; line-height: 1.65; color: var(--mut); }
        .tx-rules { display: grid; gap: .65rem; margin: 1.5rem 0 0; padding: 0; list-style: none; }
        .tx-rules li { display: flex; align-items: center; gap: .75rem; font-size: .88rem; color: var(--ink); }
        .tx-rules span { flex: none; display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: .6rem; background: var(--soft); }
        .tx-rules svg { width: 1rem; height: 1rem; fill: none; stroke: var(--acc); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .tx-start { align-self: flex-start; margin-top: 1.8rem; padding: .95rem 2.2rem; border: 0; border-radius: 99px; background: var(--ink); font-size: 1rem; font-weight: 700; color: #fff; transition: transform .15s, background .15s; }
        .tx-start:hover { background: var(--acc); transform: translateY(-1px); }
        .tx-i-note { margin-top: .8rem; font-size: .78rem; color: var(--mut); }
        .tx-i-side { position: relative; display: grid; place-content: center; justify-items: center; gap: 1.4rem; padding: 2.4rem 2rem; background: radial-gradient(120% 90% at 20% 0%, #2a4a9a 0%, var(--navy) 60%); color: #fff; }
        .tx-dial { position: relative; width: 11.5rem; height: 11.5rem; }
        .tx-dial svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .tx-dial circle { fill: none; stroke-width: 7; }
        .tx-dial .bg { stroke: rgb(255 255 255 / .14); }
        .tx-dial .fg { stroke: var(--amb); stroke-linecap: round; stroke-dasharray: 326.7; stroke-dashoffset: 82; animation: tx-sweep 1.4s cubic-bezier(.2, .8, .2, 1) both; }
        .tx-dial div { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
        .tx-dial b { font-size: 2.1rem; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -.02em; }
        .tx-dial small { font-size: .68rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; color: rgb(255 255 255 / .6); }
        .tx-topics { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; max-width: 20rem; margin: 0; padding: 0; list-style: none; }
        .tx-topics li { padding: .28rem .65rem; border: 1px solid rgb(255 255 255 / .2); border-radius: 99px; font-size: .72rem; font-weight: 600; color: rgb(255 255 255 / .85); }
        @keyframes tx-sweep { from { stroke-dashoffset: 326.7; } }

        /* ---------- Ruang soal ---------- */
        .tx-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.6rem; background: var(--navy); color: #fff; }
        .tx-pos small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: rgb(255 255 255 / .55); }
        .tx-pos b { font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .tx-clock { display: inline-flex; align-items: center; gap: .55rem; padding: .5rem 1rem; border-radius: 99px; background: rgb(255 255 255 / .1); font-variant-numeric: tabular-nums; transition: background .2s, color .2s; }
        .tx-clock svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .tx-clock b { font-size: 1.15rem; font-weight: 700; }
        .tx-clock.is-warn { background: var(--amb); color: var(--ink); }
        .tx-clock.is-crit { background: #e5484d; color: #fff; animation: tx-pulse 1s ease-in-out infinite; }
        @keyframes tx-pulse { 50% { opacity: .7; } }
        .tx-prog { height: .3rem; background: var(--line); }
        .tx-prog i { display: block; height: 100%; background: var(--amb); transform-origin: left; transform: scaleX(0); transition: transform .3s; }
        .tx-timeup { margin: 0; padding: .6rem 1.6rem; background: #fdecec; font-size: .85rem; font-weight: 600; color: #b42318; }
        .tx-body { display: grid; grid-template-columns: 1fr 15rem; }
        .tx-main { display: flex; flex-direction: column; min-height: 30rem; padding: 1.8rem 2rem 1.4rem; }
        .tx-stage { flex: 1; animation: tx-in .28s ease both; }
        @keyframes tx-in { from { opacity: 0; transform: translateX(14px); } }
        .tx-no { display: inline-flex; align-items: baseline; gap: .35rem; font-weight: 700; color: var(--acc); }
        .tx-no b { font-size: 1.9rem; letter-spacing: -.02em; }
        .tx-no small { font-size: .85rem; color: var(--mut); }
        .tx-qt { margin: .6rem 0 0; font-size: 1.18rem; font-weight: 600; line-height: 1.5; letter-spacing: -.005em; }
        .tx-opts { display: grid; gap: .6rem; margin-top: 1.3rem; }
        .tx-opt { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .9rem; width: 100%; min-height: 3.6rem; padding: .7rem 1rem; border: 1.5px solid var(--line); border-radius: 1rem; background: #fff; text-align: left; font-size: .93rem; line-height: 1.45; color: var(--ink); transition: border-color .15s, background .15s, transform .15s; }
        .tx-opt:hover { border-color: #b9c8e8; background: #f8faff; }
        .tx-opt .let { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; background: var(--soft); font-size: .8rem; font-weight: 800; color: var(--mut); transition: background .15s, color .15s; }
        .tx-opt .tick { width: 1.2rem; height: 1.2rem; fill: none; stroke: #fff; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; opacity: 0; }
        .tx-opt.is-on { border-color: var(--acc); background: #eef3ff; }
        .tx-opt.is-on .let { background: var(--acc); color: #fff; }
        .tx-opt.is-on .tick { opacity: 1; stroke: var(--acc); }
        .tx-foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.4rem; padding-top: 1.1rem; border-top: 1px solid var(--line); }
        .tx-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.4rem; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; font-size: .9rem; font-weight: 700; color: var(--ink); transition: border-color .15s, background .15s; }
        .tx-btn:hover:not(:disabled) { border-color: var(--ink); }
        .tx-btn:disabled { opacity: .4; cursor: default; }
        .tx-btn svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
        .tx-btn.is-main { border-color: var(--ink); background: var(--ink); color: #fff; }
        .tx-btn.is-main:hover:not(:disabled) { border-color: var(--acc); background: var(--acc); }
        .tx-hint { font-size: .75rem; color: var(--mut); }
        .tx-nav { display: flex; flex-direction: column; gap: 1rem; padding: 1.8rem 1.4rem; border-left: 1px solid var(--line); background: var(--soft); }
        .tx-nav h3 { font-size: .72rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); }
        .tx-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: .45rem; }
        .tx-pg { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .tx-pg[hidden] { display: none; }
        .tx-pg span { font-size: .76rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--mut); }
        .tx-pg button { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; padding: 0; border: 1.5px solid var(--line); border-radius: .65rem; background: #fff; color: var(--ink); }
        .tx-pg button:disabled { opacity: .35; }
        .tx-pg svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
        .tx-dot { display: grid; place-items: center; aspect-ratio: 1; border: 1.5px solid var(--line); border-radius: .7rem; background: #fff; font-size: .8rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--mut); transition: all .15s; }
        .tx-dot.is-done { border-color: var(--acc); background: var(--acc); color: #fff; }
        .tx-dot.is-cur { border-color: var(--amb); box-shadow: 0 0 0 3px rgb(240 180 41 / .35); color: var(--ink); background: #fff; }
        .tx-dot.is-cur.is-done { background: var(--acc); color: #fff; }
        .tx-legend { display: grid; gap: .35rem; margin: 0; padding: 0; list-style: none; font-size: .76rem; color: var(--mut); }
        .tx-legend li { display: flex; align-items: center; gap: .5rem; }
        .tx-legend i { width: .8rem; height: .8rem; border-radius: .25rem; border: 1.5px solid var(--line); background: #fff; }
        .tx-legend .d i { background: var(--acc); border-color: var(--acc); }
        .tx-legend .c i { border-color: var(--amb); }
        .tx-send { margin-top: auto; width: 100%; justify-content: center; }
        .tx-confirm { position: absolute; inset: 0; z-index: 5; display: grid; place-items: center; padding: 1rem; background: rgb(16 27 60 / .55); backdrop-filter: blur(3px); }
        .tx-confirm div { width: min(24rem, 100%); padding: 1.6rem; border-radius: 1.2rem; background: #fff; text-align: center; box-shadow: 0 24px 60px -20px rgb(0 0 0 / .5); }
        .tx-confirm h3 { font-size: 1.1rem; font-weight: 700; }
        .tx-confirm p { margin-top: .5rem; font-size: .88rem; line-height: 1.5; color: var(--mut); }
        .tx-confirm nav { display: flex; gap: .6rem; justify-content: center; margin-top: 1.2rem; }

        /* ---------- Hasil ---------- */
        .tx-out { padding: 2.2rem 2.4rem 2.4rem; animation: tx-in .35s ease both; }
        .tx-sum { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 1.8rem; }
        .tx-ring { position: relative; width: 8.5rem; height: 8.5rem; }
        .tx-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .tx-ring circle { fill: none; stroke-width: 9; }
        .tx-ring .bg { stroke: var(--line); }
        .tx-ring .fg { stroke: var(--acc); stroke-linecap: round; transition: stroke-dashoffset 1s cubic-bezier(.2, .8, .2, 1); }
        .tx-ring div { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
        .tx-ring b { font-size: 1.9rem; font-weight: 700; letter-spacing: -.02em; }
        .tx-ring small { font-size: .72rem; font-weight: 700; color: var(--mut); }
        .tx-sum h2 { margin-top: .4rem; font-size: 1.5rem; font-weight: 700; letter-spacing: -.01em; line-height: 1.2; }
        .tx-tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin: 1rem 0 0; padding: 0; list-style: none; }
        .tx-tiles li { padding: .7rem .9rem; border: 1px solid var(--line); border-radius: .9rem; background: var(--soft); }
        .tx-tiles b { display: block; font-size: 1.15rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .tx-tiles span { font-size: .74rem; color: var(--mut); }
        .tx-stats { position: relative; margin-top: 1.6rem; border: 1px solid var(--line); border-radius: 1.3rem; background: #fff; overflow: hidden; }
        .tx-stats-in { display: grid; grid-template-columns: 1.1fr 1fr; align-items: center; gap: 1.4rem; padding: 1.4rem 1.6rem; }
        .tx-stats h3 { font-size: 1rem; font-weight: 700; }
        .tx-radar { width: 100%; max-width: 23rem; margin: 0 auto; }
        .tx-radar .grid { fill: none; stroke: #d5dbe6; stroke-width: 1; }
        .tx-radar .axis { stroke: #e2e6ec; stroke-width: 1; }
        .tx-radar .shape { fill: rgb(59 108 212 / .22); stroke: var(--acc); stroke-width: 2.2; stroke-linejoin: round; }
        .tx-radar .dot { fill: var(--acc); }
        .tx-radar text { font-size: 11.5px; font-weight: 700; fill: var(--ink); }
        .tx-bars { display: grid; gap: .8rem; margin: 1rem 0 0; padding: 0; list-style: none; }
        .tx-bars li { display: grid; grid-template-columns: 1fr auto; gap: .25rem .6rem; font-size: .82rem; }
        .tx-bars b { font-weight: 700; }
        .tx-bars em { font-style: normal; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--acc); }
        .tx-bars span { grid-column: 1 / -1; height: .5rem; border-radius: 99px; background: var(--line); overflow: hidden; }
        .tx-bars span i { display: block; height: 100%; border-radius: 99px; background: var(--acc); }
        .tx-again { margin-top: 1.2rem; padding: .6rem 1.2rem; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; font-size: .85rem; font-weight: 700; color: var(--ink); }
        .tx-ins { display: grid; grid-template-columns: repeat(3, 1fr); gap: .7rem; margin-top: 1rem; }
        .tx-ins article { padding: 1rem 1.2rem; border: 1px solid var(--line); border-radius: 1rem; background: #fff; }
        .tx-ins h4 { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--acc); }
        .tx-ins .is-good h4 { color: #2f9e6b; } .tx-ins .is-weak h4 { color: #d9480f; } .tx-ins .is-tip h4 { color: #b7791f; }
        .tx-ins p { margin-top: .4rem; font-size: .86rem; line-height: 1.55; color: var(--ink); }
        .tx-rev { display: grid; gap: .7rem; margin: 1.6rem 0 0; padding: 0; list-style: none; }
        .tx-rev li { padding: .9rem 1.1rem; border: 1px solid var(--line); border-left: 4px solid #e5484d; border-radius: .9rem; font-size: .86rem; line-height: 1.5; }
        .tx-rev li.is-ok { border-left-color: #2f9e6b; }
        .tx-rev b { display: block; font-size: .9rem; }
        .tx-rev small { display: block; margin-top: .3rem; color: var(--mut); }

        .tx-areas { display: grid; grid-template-columns: repeat(2, 1fr); align-items: stretch; gap: .8rem; margin-top: 1rem; }
        .tx-area { padding: 1rem 1.2rem; border: 1px solid var(--line); border-left: 4px solid var(--acc); border-radius: 1rem; background: #fff; }
        .tx-area.is-good { border-left-color: #2f9e6b; } .tx-area.is-weak { border-left-color: #d9480f; }
        .tx-area-h { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .3rem .8rem; }
        .tx-area-h b { font-size: .95rem; }
        .tx-area-h em { font-style: normal; font-size: .8rem; font-weight: 700; color: var(--mut); font-variant-numeric: tabular-nums; }
        .tx-area p { margin-top: .4rem; font-size: .85rem; line-height: 1.55; }
        .tx-area p small { display: block; margin-top: .25rem; color: var(--mut); font-size: .8rem; }
        .tx-sec-t { margin-top: 1.8rem; font-size: 1.05rem; font-weight: 700; }
        .tx-sec-s { margin-top: .2rem; font-size: .84rem; color: var(--mut); }
        .tx-rv-wrap { margin-top: 1.8rem; border: 1px solid var(--line); border-radius: 1.2rem; background: #fff; overflow: hidden; }
        .tx-rv-h { display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; padding: 1.05rem 1.3rem; border: 0; background: transparent; text-align: left; cursor: pointer; color: var(--ink); }
        .tx-rv-h:hover { background: var(--soft); }
        .tx-rv-h b { display: block; font-size: 1rem; }
        .tx-rv-h small { display: block; margin-top: .15rem; font-size: .8rem; color: var(--mut); }
        .tx-rv-h .tx-rv-act { display: inline-flex; align-items: center; gap: .4rem; flex-shrink: 0; padding: .4rem .85rem; border: 1.5px solid var(--line); border-radius: 99px; font-size: .78rem; font-weight: 800; }
        .tx-rv-h svg { width: .9rem; height: .9rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; transition: transform .25s; }
        .tx-rv-h[aria-expanded="true"] svg { transform: rotate(180deg); }
        .tx-rv-body { padding: 0 1.3rem 1.3rem; }
        .tx-rv-body .tx-rev { margin-top: .2rem; }
        .tx-next { display: grid; grid-template-columns: repeat(2, 1fr); gap: .8rem; margin-top: 1.2rem; }
        .tx-next a { display: block; padding: 1.1rem 1.3rem; border: 1.5px solid var(--line); border-radius: 1.1rem; background: #fff; color: var(--ink); text-decoration: none; transition: transform .15s, border-color .2s; }
        .tx-next a:hover { transform: translateY(-2px); border-color: var(--acc); }
        .tx-next a.is-main { border-color: var(--acc); background: var(--acc); color: #fff; }
        .tx-next small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; opacity: .7; }
        .tx-next b { display: block; margin-top: .25rem; font-size: 1rem; }
        .tx-next span { display: block; margin-top: .3rem; font-size: .82rem; line-height: 1.45; opacity: .85; }

        @media (max-width: 820px) {
            .tx-intro, .tx-body, .tx-stats-in, .tx-ins, .tx-next, .tx-areas { grid-template-columns: 1fr; }
            .tx-i-main { padding: 1.8rem 1.4rem; }
            .tx-i-side { order: -1; padding: 1.6rem 1rem; }
            .tx-dial { width: 8.5rem; height: 8.5rem; }
            .tx-nav { border-left: 0; border-top: 1px solid var(--line); }
            .tx-main { min-height: 0; padding: 1.4rem 1.1rem 1.1rem; }
            .tx-out { padding: 1.5rem 1.1rem; }
            .tx-sum { grid-template-columns: 1fr; justify-items: center; text-align: center; }
            .tx-hint { display: none; }
        }
        @media (max-width: 420px) { .tx-foot .tx-lbl { display: none; } .tx-foot [data-tx-prev] { flex: none; width: 3rem; padding: 0; justify-content: center; } .tx-foot .tx-btn { padding: 0 .9rem; } }
        @media (prefers-reduced-motion: reduce) { .tx-stage, .tx-out, .tx-dial .fg, .tx-clock.is-crit { animation: none; } .tx-nav, .tx-sheet-bd { transition: none !important; } }

        /* ---------- Hasil: tab ringkas ---------- */
        .tx-rt { position: sticky; top: var(--hh, 3.6rem); z-index: 15; display: flex; gap: .3rem; margin: 1.2rem 2rem 0; padding: .3rem; border: 1px solid var(--line); border-radius: 1rem; background: #fff; overflow-x: auto; scrollbar-width: none; }
        .tx-rt button { flex: 1 0 auto; padding: .55rem 1rem; border: 0; border-radius: .75rem; background: transparent; font-size: .86rem; font-weight: 700; color: var(--mut); white-space: nowrap; }
        .tx-rt button:hover { background: var(--soft); color: var(--ink); }
        .tx-rt button[aria-selected="true"] { background: var(--ink); color: #fff; }
        .tx-rt button em { margin-left: .35rem; padding: .05rem .45rem; border-radius: 99px; background: #d9480f; font-style: normal; font-size: .72rem; font-weight: 800; color: #fff; }
        .tx-rt button[data-t="bahas"]:not([aria-selected="true"]) { background: #eef3ff; color: var(--acc); }
        .tx-rp { margin: .9rem 2rem 0; }
        .tx-rp-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; align-items: center; }
        .tx-rcols { display: grid; grid-template-columns: repeat(3, 1fr); gap: .9rem; align-items: stretch; }
        .tx-rcol { padding: 1.05rem 1.2rem; border: 1px solid var(--line); border-top: 4px solid var(--acc); border-radius: 1.1rem; background: #fff; }
        .tx-rcol.is-good { border-top-color: #2f9e6b; } .tx-rcol.is-weak { border-top-color: #d9480f; } .tx-rcol.is-tip { border-top-color: #b7791f; }
        .tx-rcol h4 { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); }
        .tx-rcol.is-good h4 { color: #2f9e6b; } .tx-rcol.is-weak h4 { color: #d9480f; } .tx-rcol.is-tip h4 { color: #b7791f; }
        .tx-rcol article + article { margin-top: .8rem; padding-top: .8rem; border-top: 1px dashed var(--line); }
        .tx-rcol b { display: block; margin-top: .6rem; font-size: .92rem; } .tx-rcol article + article b { margin-top: 0; }
        .tx-rcol p { margin-top: .2rem; font-size: .82rem; line-height: 1.55; color: #33415a; }
        .tx-rsub { display: flex; gap: .4rem; margin-bottom: .8rem; overflow-x: auto; }
        .tx-rsub button { padding: .4rem .95rem; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; font-size: .8rem; font-weight: 700; color: var(--mut); white-space: nowrap; }
        .tx-rsel { display: none; }
        .tx-rsub button[aria-selected="true"] { border-color: var(--acc); background: var(--acc); color: #fff; }
        .tx-rdet { padding: 1.1rem 1.3rem; border: 1px solid var(--line); border-radius: 1.1rem; background: #fff; }
        .tx-rdet h4 { font-size: 1rem; font-weight: 800; } .tx-rdet h4 em { margin-left: .4rem; font-style: normal; font-size: .82rem; color: var(--mut); }
        .tx-rdet p { margin-top: .5rem; font-size: .86rem; line-height: 1.6; } .tx-rdet small { display: block; margin-top: .5rem; color: #2f6f4f; font-weight: 600; }
        .tx-rlist { max-height: 30rem; overflow-y: auto; border: 1px solid var(--line); border-radius: 1.1rem; background: #fff; }
        .tx-rlist details { border-bottom: 1px solid var(--line); } .tx-rlist details:last-child { border-bottom: 0; }
        .tx-rlist summary { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .8rem; padding: .75rem 1.1rem; cursor: pointer; list-style: none; font-size: .85rem; }
        .tx-rlist summary::-webkit-details-marker { display: none; } .tx-rlist summary:hover { background: var(--soft); }
        .tx-rlist summary span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tx-rlist summary i { padding: .15rem .6rem; border-radius: 99px; font-style: normal; font-size: .7rem; font-weight: 800; }
        .tx-rlist .is-ok summary i { background: #e3f5ec; color: #1f7a50; } .tx-rlist .is-bad summary i { background: #fdebe3; color: #b13a08; }
        .tx-rlist div { padding: 0 1.1rem 1rem; font-size: .84rem; line-height: 1.6; } .tx-rlist small { display: block; margin-top: .5rem; padding: .7rem .9rem; border-radius: .8rem; background: var(--soft); color: #33415a; }
        @media (max-width: 860px) { .tx-rt, .tx-rp { margin-left: 1rem; margin-right: 1rem; } .tx-rp-2, .tx-rcols { grid-template-columns: 1fr; } }

        /* ---------- Ruang soal: tampilan polos, disamakan dengan ruang ujian ---------- */
        .tx { --acc: #2f5fd0; --ink: #162033; --line: #e3e8f0; }
        .tx-bar { background: #fff; color: var(--ink); border-bottom: 1px solid var(--line); }
        .tx-pos small { color: var(--mut); }
        .tx-clock { background: #f1f4fa; color: var(--ink); }
        .tx-clock svg { stroke: var(--acc); }
        .tx-clock.is-warn { background: #f6c445; color: var(--ink); } .tx-clock.is-warn svg { stroke: var(--ink); }
        .tx-clock.is-crit { background: #e5484d; color: #fff; } .tx-clock.is-crit svg { stroke: #fff; }
        .tx-prog { height: .25rem; } .tx-prog i { background: var(--acc); }
        .tx-no { display: none; }
        .tx-qt { margin-top: .2rem; font-size: 1.3rem; line-height: 1.55; }
        .tx-opts { gap: .65rem; margin-top: 1.4rem; }
        .tx-opt { min-height: 3.5rem; border-radius: .9rem; font-size: 1rem; }
        .tx-opt:hover { border-color: #9bb4ea; background: #fff; }
        .tx-opt .let { width: 2rem; height: 2rem; border-radius: 50%; background: #eef1f6; color: #3d4c63; }
        .tx-opt.is-on { border-color: var(--acc); background: #f2f6ff; }
        .tx-btn { height: 3rem; padding: 0 1.4rem; border-radius: .8rem; }
        .tx-btn.is-main { border-color: var(--acc); background: var(--acc); }
        .tx-btn.is-main:hover:not(:disabled) { border-color: #2448a8; background: #2448a8; }
        .tx-nav { background: #fff; }
        .tx-dot { border-radius: .65rem; }
        .tx-dot.is-cur, .tx-dot.is-cur.is-done { border-color: var(--ink); box-shadow: none; outline: 2px solid var(--ink); outline-offset: 2px; }
        .tx-dot.is-cur:not(.is-done) { color: var(--ink); }
        .tx-send { background: var(--ink); border-color: var(--ink); color: #fff; }
        .tx-send:hover:not(:disabled) { background: var(--acc); border-color: var(--acc); }
        @media (max-width: 860px) {
            .tx-main { padding: 1.3rem 1.1rem 1rem; }
            .tx-foot { position: sticky; bottom: 0; z-index: 4; margin: 1.2rem -1.1rem -1rem; padding: .75rem 1rem 1rem; background: #fff; }
            .tx-hint { display: none; }
            .tx-foot .tx-btn { height: 3.25rem; }
            .tx-foot [data-tx-next] { flex: 1; justify-content: center; margin-left: .6rem; }
        }
        /* HP: soal dan pilihan muat di satu layar; timer di atas dan tombol di bawah tetap terlihat */
        @media (max-width: 860px) {
            .tx-bar { position: sticky; top: var(--hh, 3.6rem); z-index: 6; padding: .65rem 1rem; }
            .tx-prog { position: sticky; top: calc(var(--hh, 3.6rem) + 3.1rem); z-index: 6; }
            .tx-clock { padding: .35rem .8rem; } .tx-clock b { font-size: 1.05rem; }
            .tx-main { padding: 1rem .9rem .9rem; }
            .tx-qt { font-size: 1.05rem; line-height: 1.5; }
            .tx-opts { gap: .5rem; margin-top: 1rem; }
            .tx-opt { grid-template-columns: auto 1fr; gap: .7rem; min-height: 3rem; padding: .55rem .8rem; font-size: .95rem; line-height: 1.4; }
            .tx-opt .tick { display: none; }
            .tx-opt .let { width: 1.8rem; height: 1.8rem; }
            .tx-foot { margin: 1rem -.9rem -.9rem; padding: .65rem .9rem .85rem; box-shadow: 0 -8px 16px -14px rgb(22 32 51 / .45); }
            .tx-foot .tx-btn { height: 3rem; }
            .tx-nav { padding: 1.1rem .9rem 1.3rem; }
            .tx-rt { margin: .9rem .6rem 0; }
            .tx-rt button { padding: .5rem .55rem; font-size: .82rem; }
            .tx-rt button em { margin-left: .25rem; padding: .05rem .38rem; }
            .tx-rt, .tx-rp { margin-left: .6rem; margin-right: .6rem; }
        }

        /* ---------- HP: gaya simpel, daftar soal lewat panah di header ---------- */
        .tx-pos { display: block; padding: 0; border: 0; background: none; font: inherit; color: inherit; text-align: left; cursor: default; }
        .tx-pos .tx-pos-m, .tx-pos .tx-pos-ch, .tx-sheet-x, .tx-sheet-bd { display: none; }
        .tx-sheet-h { display: block; }
        .tx-sheet-h h3 { margin: 0; }
        .tx { scroll-margin-top: var(--hh, 3.6rem); }
        @media (max-width: 860px) {
            .qz.is-run .qz-wrap { padding: 0 0 1.5rem; }
            .qz.is-run .tx { border-width: 0 0 1px; border-radius: 0; box-shadow: none; }
            .tx-bar { padding: .6rem 1rem; gap: .6rem; }
            .tx-pos { display: flex; align-items: center; gap: .5rem; min-width: 0; flex: 1; cursor: pointer; }
            .tx-pos-t { display: block; min-width: 0; }
            .tx-pos-d { display: none !important; }
            .tx-pos .tx-pos-m { display: block; margin-top: .1rem; font-size: .76rem; font-weight: 600; letter-spacing: 0; text-transform: none; color: var(--mut); }
            .tx-pos-m b { font-size: .74rem; font-weight: 800; color: var(--acc); }
            .tx-pos .tx-pos-ch { display: block; flex: none; width: 1.7rem; height: 1.7rem; padding: .35rem; border-radius: 99px; background: #eaf0fd; fill: none; stroke: var(--acc); stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; box-sizing: border-box; }
            .tx-pos:active .tx-pos-ch { background: #d8e4fb; }
            .tx-main { padding: 1.1rem 1rem 0; }
            .tx-opt { border-width: 1.5px; box-shadow: none; }
            .tx-prog { top: calc(var(--hh, 3.6rem) + var(--bh, 3.9rem)); }

            /* Daftar soal: sheet dari bawah */
            .tx-sheet-bd { display: block; position: fixed; inset: 0; z-index: 85; background: rgb(16 27 60 / .45); opacity: 0; pointer-events: none; transition: opacity .2s; }
            .tx.is-sheet .tx-sheet-bd { opacity: 1; pointer-events: auto; }
            .tx-nav { position: fixed; left: 0; right: 0; bottom: 0; z-index: 90; gap: .9rem; max-height: 82vh; overflow-y: auto; padding: .9rem 1.1rem calc(1.2rem + env(safe-area-inset-bottom, 0px)); border: 0; border-radius: 1.4rem 1.4rem 0 0; background: #fff; box-shadow: 0 -20px 50px -18px rgb(16 27 60 / .5); transform: translateY(105%); visibility: hidden; transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility 0s .28s; }
            .tx.is-sheet .tx-nav { transform: none; visibility: visible; transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility 0s; }
            .tx-nav::before { content: ''; display: block; width: 2.4rem; height: .28rem; margin: 0 auto .1rem; border-radius: 99px; background: #d5dbe6; }
            .tx-sheet-h { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
            .tx-sheet-h h3 { font-size: .78rem; }
            .tx-sheet-x { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; padding: 0; border: 0; border-radius: 99px; background: var(--soft); color: var(--ink); }
            .tx-sheet-x svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; }
            .tx-grid { grid-template-columns: repeat(5, 1fr); gap: .5rem; }
            .tx-dot { font-size: .95rem; }
            .tx-legend { grid-template-columns: repeat(3, auto); justify-content: start; gap: .35rem 1rem; }
            .tx-nav .tx-hint { margin: 0; font-size: .82rem; }
            .tx-nav .tx-send { margin-top: .2rem; }
            .tx-confirm { position: fixed; z-index: 95; }
            .tx-body { grid-template-columns: minmax(0, 1fr); }
            .tx-foot { gap: .6rem; }
            .tx-foot [data-tx-next] { margin-left: 0; }

            /* Hasil: tab 3 kolom, tanpa scroll bersarang */
            .tx-rt { display: grid; grid-template-columns: repeat(3, 1fr); overflow: visible; margin-top: 1rem; }
            .tx-rt button { flex: none; padding: .6rem .3rem; text-align: center; }
            .tx-tiles { gap: .5rem; }
            .tx-tiles li { padding: .65rem .4rem; text-align: center; }
            .tx-tiles span { display: block; margin-top: .1rem; font-size: .7rem; line-height: 1.25; }
            .tx-rlist { max-height: none; overflow: visible; }
            .tx-rlist summary { grid-template-columns: auto 1fr auto; align-items: start; padding: .8rem .9rem; }
            .tx-rlist summary span { white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.4; }
            .tx-rlist details[open] summary span { -webkit-line-clamp: unset; }
            .tx-rlist div { padding: 0 .9rem 1rem; }
            .tx-radar { max-width: 19rem; }
        }
    </style>

    <section class="qz">
        <div class="qz-wrap tx-wrap">
            <div class="tx" data-tx aria-labelledby="tx-h">

                {{-- ============ Pembuka ============ --}}
                <div class="tx-intro" data-tx-intro>
                    <div class="tx-i-main">
                        <p class="tx-eyebrow">Coba simulasi</p>
                        <h1 class="font-display tx-h" id="tx-h">{{ $total }} soal kasus kerja dalam <em>{{ $minutes }} menit.</em></h1>
                        <p class="tx-lead">Tiap soal adalah situasi pekerjaan yang detail: hitungan biaya, keputusan teknis, aturan keselamatan, sampai pembacaan data. Dikerjakan satu per satu, seperti ujian sebenarnya.</p>

                        <ul class="tx-rules">
                            <li><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/></svg></span>{{ $total }} soal pilihan ganda, satu jawaban benar</li>
                            <li><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9.5 2.5h5"/></svg></span>Sekitar {{ (int) round($minutes * 60 / $total) }} detik per soal, timer jalan setelah Mulai</li>
                            <li><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>Boleh pindah soal dan mengubah jawaban sebelum dikirim</li>
                        </ul>

                        <button type="button" class="tx-start" data-tx-start>Mulai sekarang</button>
                        <p class="tx-i-note">Soal yang dilewati dihitung kosong, tanpa pengurangan nilai.</p>
                    </div>
                    <div class="tx-i-side" aria-hidden="true">
                        <div class="tx-dial">
                            <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="52" class="bg"/><circle cx="60" cy="60" r="52" class="fg"/></svg>
                            <div><b class="font-display">{{ sprintf('%02d:00', $minutes) }}</b><small>menit</small></div>
                        </div>
                        <ul class="tx-topics">
                            @foreach (['K3', 'Akuntansi', 'Pengadaan', 'Proyek', 'SQL', 'Pemasaran', 'Logistik', 'SDM', 'Layanan', 'Keamanan', 'Manufaktur', 'Keuangan', 'UX', 'Software', 'Kontrak'] as $topic)
                                <li>{{ $topic }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- ============ Ruang soal: satu soal per layar ============ --}}
                <div class="tx-exam" data-tx-exam hidden>
                    <header class="tx-bar">
                        <button type="button" class="tx-pos" data-tx-open aria-haspopup="dialog" aria-label="Lihat daftar soal">
                            <span class="tx-pos-t"><small class="tx-pos-d">Simulasi kerja</small><b data-tx-pos>Soal 1 dari {{ $total }}</b><small class="tx-pos-m"><b data-tx-hn>0</b> dari {{ $total }} soal terjawab</small></span>
                            <svg class="tx-pos-ch" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div class="tx-clock" data-tx-clock role="timer" aria-label="Sisa waktu">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9.5 2.5h5"/></svg>
                            <b data-tx-time>{{ sprintf('%02d:00', $minutes) }}</b>
                        </div>
                    </header>
                    <div class="tx-prog" aria-hidden="true"><i data-tx-prog></i></div>
                    <p class="tx-timeup" data-tx-timeup hidden>Waktu habis. Jawabanmu dikirim otomatis.</p>
                    <p class="sr-only" role="status" aria-live="assertive" data-tx-live></p>

                    <div class="tx-body">
                        <div class="tx-main">
                            <div class="tx-stage" data-tx-stage aria-live="polite"></div>
                            <div class="tx-foot">
                                <button type="button" class="tx-btn" data-tx-prev><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg><span class="tx-lbl">Sebelumnya</span></button>
                                <span class="tx-hint">Tekan 1 sampai 4 untuk memilih, panah untuk pindah</span>
                                <button type="button" class="tx-btn is-main" data-tx-next>Berikutnya<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                            </div>
                        </div>
                        <div class="tx-sheet-bd" data-tx-bd></div>
                        <aside class="tx-nav" id="tx-sheet" aria-label="Navigasi soal">
                            <div class="tx-sheet-h"><h3>Navigasi soal</h3><button type="button" class="tx-sheet-x" data-tx-close aria-label="Tutup daftar soal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
                            <div class="tx-grid" data-tx-grid></div>
                            <div class="tx-pg" data-tx-pg hidden>
                                <button type="button" data-tx-pg-prev aria-label="20 soal sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
                                <span data-tx-pg-t></span>
                                <button type="button" data-tx-pg-next aria-label="20 soal berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></button>
                            </div>
                            <ul class="tx-legend">
                                <li class="d"><i></i>Sudah dijawab</li>
                                <li class="c"><i></i>Soal saat ini</li>
                                <li><i></i>Belum dijawab</li>
                            </ul>
                            <p class="tx-hint" style="display:block"><b data-tx-n>0</b> dari {{ $total }} terjawab</p>
                            <button type="button" class="tx-btn tx-send" data-tx-send>Selesai &amp; kirim</button>
                        </aside>
                    </div>

                    <div class="tx-confirm" data-tx-confirm hidden role="dialog" aria-modal="true" aria-labelledby="tx-cf-h">
                        <div>
                            <h3 id="tx-cf-h">Kirim jawaban sekarang?</h3>
                            <p data-tx-cf-text></p>
                            <nav>
                                <button type="button" class="tx-btn" data-tx-cf-no>Periksa lagi</button>
                                <button type="button" class="tx-btn is-main" data-tx-cf-yes>Ya, kirim</button>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="tx-out" data-tx-out hidden aria-live="polite"></div>
            </div>
            <noscript><p class="qz-noscript">Simulasi ini butuh JavaScript. Aktifkan JavaScript lalu muat ulang halaman.</p></noscript>
        </div>
    </section>

    <script>
        (function () {
            const qs = @json($questions);
            const N = qs.length, SECONDS = {{ $minutes * 60 }};
            const AREAS = @json(array_values(config('arah-karier.trial_areas')));
            const URL_SUBMIT = @json(route('exam.trial.submit'));
            const URL_LEARN = @json($loggedIn ? route('account.learning') : route('learning.index'));
            const URL_EXAM = @json($loggedIn ? route('account.exams') : route('exam.index').'#karier');
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const post = (url, body) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) })
                .then((r) => r.ok ? r.json() : Promise.reject(r.status));
            const tickIcon = '<svg class="tick" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';

            const root = document.querySelector('[data-tx]');
            const q = (s) => root.querySelector(s);
            const intro = q('[data-tx-intro]'), exam = q('[data-tx-exam]'), out = q('[data-tx-out]'), stage = q('[data-tx-stage]');
            const pos = q('[data-tx-pos]'), prog = q('[data-tx-prog]'), grid = q('[data-tx-grid]'), cnt = q('[data-tx-n]');
            const prev = q('[data-tx-prev]'), next = q('[data-tx-next]'), send = q('[data-tx-send]');
            const hn = q('[data-tx-hn]'), qzRoot = document.querySelector('.qz'), isMobile = matchMedia('(max-width: 860px)');
            const clock = q('[data-tx-clock]'), time = q('[data-tx-time]'), live = q('[data-tx-live]'), timeup = q('[data-tx-timeup]');
            const confirmBox = q('[data-tx-confirm]'), cfText = q('[data-tx-cf-text]');
            let idx = 0, answers = [], timer = null, deadline = 0, sent = false, startedAt = 0;

            const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            const top = () => root.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
            const done = () => answers.filter((a) => a !== null && a !== undefined).length;

            // 5 kotak ke samping x 4 baris = 20 soal per halaman; sisanya lewat tombol panah
            const PER = 20, GP = Math.ceil(N / PER), pg = q('[data-tx-pg]');
            let gp = 0;
            function drawGrid() {
                const from = gp * PER, to = Math.min(N, from + PER);
                let h = '';
                for (let i = from; i < to; i++) h += '<button type="button" class="tx-dot" data-i="' + i + '" aria-label="Soal ' + (i + 1) + '">' + (i + 1) + '</button>';
                grid.innerHTML = h;
                pg.hidden = GP < 2;
                q('[data-tx-pg-t]').textContent = (from + 1) + '\u2013' + to + ' dari ' + N;
                q('[data-tx-pg-prev]').disabled = gp === 0;
                q('[data-tx-pg-next]').disabled = gp >= GP - 1;
            }
            drawGrid();

            function paint() {
                pos.textContent = 'Soal ' + (idx + 1) + ' dari ' + N;
                prog.style.transform = 'scaleX(' + ((idx + 1) / N) + ')';
                [].slice.call(grid.children).forEach((d) => { const i = +d.dataset.i; d.classList.toggle('is-cur', i === idx); d.classList.toggle('is-done', answers[i] !== null && answers[i] !== undefined); });
                cnt.textContent = done(); hn.textContent = done();
                root.style.setProperty('--bh', q('.tx-bar').offsetHeight + 'px');
                prev.disabled = idx === 0;
                next.innerHTML = idx === N - 1 ? 'Selesai<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' : 'Berikutnya<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
            }
            function show() {
                const it = qs[idx];
                stage.style.animation = 'none'; void stage.offsetWidth; stage.style.animation = '';
                stage.innerHTML = '<span class="tx-no"><b class="font-display">' + String(idx + 1).padStart(2, '0') + '</b><small>/ ' + String(N).padStart(2, '0') + '</small></span>' +
                    '<h2 class="tx-qt">' + esc(it.text) + '</h2><div class="tx-opts" role="radiogroup">' +
                    it.options.map((o, k) => '<button type="button" class="tx-opt' + (answers[idx] === k ? ' is-on' : '') + '" role="radio" aria-checked="' + (answers[idx] === k) + '" data-k="' + k + '"><i class="let">' + 'ABCD'[k] + '</i><span>' + esc(o) + '</span>' + tickIcon + '</button>').join('') + '</div>';
                // halaman kotak mengikuti soal aktif hanya saat berpindah soal (bukan saat panah halaman ditekan)
                if (Math.floor(idx / PER) !== gp) { gp = Math.floor(idx / PER); drawGrid(); }
                paint();
            }
            function go(i) { if (i < 0 || i >= N) return; idx = i; show(); }
            function openSheet() { if (!isMobile.matches) return; root.classList.add('is-sheet'); const c = q('[data-tx-close]'); if (c) c.focus({ preventScroll: true }); }
            function closeSheet() { root.classList.remove('is-sheet'); }

            function tick() {
                const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                time.textContent = fmt(left);
                clock.classList.toggle('is-warn', left <= 60 && left > 20);
                clock.classList.toggle('is-crit', left <= 20);
                if (left === 60) live.textContent = 'Sisa waktu satu menit.';
                if (left === 0) { timeup.hidden = false; live.textContent = 'Waktu habis. Jawabanmu dikirim otomatis.'; finish(); }
            }
            function start() {
                sent = false; answers = Array(N).fill(null); idx = 0;
                out.hidden = true; confirmBox.hidden = true; timeup.hidden = true;
                intro.hidden = true; exam.hidden = false; qzRoot.classList.add('is-run'); closeSheet();
                send.disabled = false; send.textContent = 'Selesai & kirim';
                startedAt = Date.now(); deadline = startedAt + SECONDS * 1000;
                show(); tick(); clearInterval(timer); timer = setInterval(tick, 250); top();
            }
            function ask() {
                const left = N - done();
                cfText.textContent = left ? 'Masih ada ' + left + ' soal yang belum dijawab. Soal kosong dihitung salah.' : 'Semua soal sudah terjawab. Jawaban tidak bisa diubah setelah dikirim.';
                confirmBox.hidden = false; confirmBox.querySelector('[data-tx-cf-yes]').focus();
            }
            function finish() {
                if (sent) return; sent = true; clearInterval(timer); confirmBox.hidden = true;
                const used = Math.min(SECONDS, Math.round((Date.now() - startedAt) / 1000));
                send.disabled = true; send.textContent = 'Mengirim...';
                post(URL_SUBMIT, { answers: answers }).then(function (d) {
                    exam.hidden = true; out.hidden = false; qzRoot.classList.remove('is-run'); closeSheet();
                    out.innerHTML = resultOpen(d, used);
                    const fg = out.querySelector('.tx-ring .fg'); if (fg) requestAnimationFrame(() => requestAnimationFrame(() => { fg.style.strokeDashoffset = fg.dataset.off; }));
                    out.querySelector('[data-tx-again]').addEventListener('click', function () { out.hidden = true; intro.hidden = false; top(); });
                    [].forEach.call(out.querySelectorAll('[data-tx-dd]'), wireDd);
                    out.onclick = function (e) {
                        const t = e.target.closest('[data-t]'); if (!t) return;
                        const g = t.getAttribute('data-g'), id = t.getAttribute('data-t');
                        [].forEach.call(out.querySelectorAll('[data-t][data-g="' + g + '"]'), (x) => x.setAttribute('aria-selected', String(x === t)));
                        [].forEach.call(out.querySelectorAll('[data-p][data-g="' + g + '"]'), (p) => { p.hidden = p.getAttribute('data-p') !== id; });
                    };
                    top();
                }).catch(function () { sent = false; send.disabled = false; send.textContent = 'Gagal mengirim, coba lagi'; confirmBox.hidden = true; });
            }

            q('[data-tx-start]').addEventListener('click', start);
            stage.addEventListener('click', (e) => { const b = e.target.closest('.tx-opt'); if (!b) return; answers[idx] = +b.dataset.k; show(); });
            grid.addEventListener('click', (e) => { const b = e.target.closest('.tx-dot'); if (b) { go(+b.dataset.i); closeSheet(); } });
            q('[data-tx-pg-prev]').addEventListener('click', () => { gp = Math.max(0, gp - 1); drawGrid(); paint(); });
            q('[data-tx-pg-next]').addEventListener('click', () => { gp = Math.min(GP - 1, gp + 1); drawGrid(); paint(); });
            q('[data-tx-open]').addEventListener('click', openSheet);
            q('[data-tx-close]').addEventListener('click', closeSheet);
            q('[data-tx-bd]').addEventListener('click', closeSheet);
            send.addEventListener('click', closeSheet);
            isMobile.addEventListener('change', closeSheet);
            addEventListener('resize', () => { if (!exam.hidden) root.style.setProperty('--bh', q('.tx-bar').offsetHeight + 'px'); });
            prev.addEventListener('click', () => go(idx - 1));
            next.addEventListener('click', () => { if (idx < N - 1) go(idx + 1); else ask(); });
            send.addEventListener('click', ask);
            q('[data-tx-cf-no]').addEventListener('click', () => { confirmBox.hidden = true; });
            q('[data-tx-cf-yes]').addEventListener('click', finish);
            addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeSheet();
                if (exam.hidden || !confirmBox.hidden || sent || e.ctrlKey || e.metaKey || e.altKey) return;
                if (/^[1-4]$/.test(e.key)) { answers[idx] = +e.key - 1; show(); }
                else if (e.key === 'ArrowRight') { if (idx < N - 1) go(idx + 1); }
                else if (e.key === 'ArrowLeft') { go(idx - 1); }
            });

            /* ---------- Radar enam sudut ---------- */
            function radar(vals) {
                const cx = 170, cy = 128, R = 84, n = vals.length;
                const pt = (r, k) => [cx + r * Math.cos(-Math.PI / 2 + k * 2 * Math.PI / n), cy + r * Math.sin(-Math.PI / 2 + k * 2 * Math.PI / n)];
                const poly = (r) => Array.from({ length: n }, (_, k) => pt(r, k).map((v) => v.toFixed(1)).join(',')).join(' ');
                let s = '<svg class="tx-radar" viewBox="-45 0 430 256" role="img" aria-label="Radar kemampuan per bidang">';
                [.33, .66, 1].forEach((m) => { s += '<polygon class="grid" points="' + poly(R * m) + '"/>'; });
                for (let k = 0; k < n; k++) { const p = pt(R, k); s += '<line class="axis" x1="' + cx + '" y1="' + cy + '" x2="' + p[0].toFixed(1) + '" y2="' + p[1].toFixed(1) + '"/>'; }
                s += '<polygon class="shape" points="' + vals.map((v, k) => pt(R * Math.max(.06, v), k).map((x) => x.toFixed(1)).join(',')).join(' ') + '"/>';
                vals.forEach((v, k) => { const p = pt(R * Math.max(.06, v), k); s += '<circle class="dot" cx="' + p[0].toFixed(1) + '" cy="' + p[1].toFixed(1) + '" r="3.2"/>'; });
                AREAS.forEach((a, k) => { const p = pt(R + 17, k); const anchor = p[0] < cx - 6 ? 'end' : (p[0] > cx + 6 ? 'start' : 'middle'); s += '<text x="' + p[0].toFixed(1) + '" y="' + (p[1] + 4).toFixed(1) + '" text-anchor="' + anchor + '">' + esc(a) + '</text>'; });
                return s + '</svg>';
            }
            const bars = (rows) => '<ul class="tx-bars">' + rows.map((r) => '<li><b>' + esc(r[0]) + '</b><em>' + r[1] + '%</em><span><i style="width:' + r[1] + '%"></i></span></li>').join('') + '</ul>';
            const ring = (frac, a, b) => {
                const C = 2 * Math.PI * 42;
                return '<div class="tx-ring"><svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="42" class="bg"/><circle cx="50" cy="50" r="42" class="fg" stroke-dasharray="' + C.toFixed(1) + '" stroke-dashoffset="' + C.toFixed(1) + '" data-off="' + (C * (1 - frac)).toFixed(1) + '"/></svg><div><b class="font-display">' + a + '</b><small>' + b + '</small></div></div>';
            };

            const TIPS = {
                keuangan: 'latih membaca laporan laba rugi, arus kas, dan menghitung titik impas dari angka nyata',
                operasional: 'pelajari rumus persediaan, efisiensi mesin, dan proses pembelian dari awal sampai pembayaran',
                proyek: 'biasakan menggambar alur kegiatan, menghitung jalur kritis, dan menetapkan SLA yang terukur',
                data: 'perbanyak latihan SQL join dan pola desain sistem yang aman terhadap pengiriman ulang',
                keselamatan: 'hafalkan hierarki pengendalian risiko dan prinsip akses minimum, lalu terapkan ke kasus kerja',
                bisnis: 'latih menghitung LTV, CAC, dan turnover, lalu tarik kesimpulan bisnisnya'
            };
            const AREA_INFO = {
                keuangan: 'Membaca laporan laba rugi, arus kas, titik impas, dan margin. Soal bidang ini menguji ketelitian hitungan dan kemampuan menarik kesimpulan dari angka.',
                operasional: 'Persediaan, efisiensi mesin, pembelian, dan alur produksi. Kesalahan biasanya muncul dari rumus yang tertukar atau satuan yang terlewat.',
                proyek: 'Jalur kritis, jadwal, SLA, dan koordinasi layanan. Yang diukur adalah cara berpikir runtut dan menetapkan target yang terukur.',
                data: 'SQL, desain sistem, dan pembacaan data. Soal menguji logika query serta kehati-hatian terhadap kasus tepi seperti pengiriman ulang.',
                keselamatan: 'K3, pengendalian risiko, dan akses minimum. Jawaban terbaik biasanya yang menghilangkan bahaya, bukan yang hanya melindungi pekerja.',
                bisnis: 'LTV, CAC, turnover, dan keputusan SDM. Soal menguji kemampuan mengubah angka menjadi keputusan bisnis.'
            };
            const first = (t) => { const m = String(t || '').match(/^.*?[.!?](?=\s|$)/); return m ? m[0] : String(t || ''); };
            // Dropdown kustom (desain sama dengan .ui-dd di seluruh situs) untuk memilih bidang di tab Rincian
            function wireDd(dd) {
                const btn = dd.querySelector('[data-tx-ddbtn]'), list = dd.querySelector('[data-tx-ddlist]'), label = dd.querySelector('[data-tx-ddlabel]');
                const opts = [].slice.call(list.querySelectorAll('[role=option]'));
                const place = (e) => { if (e && e.target === list) return; if (window.uiDdPlace) window.uiDdPlace(btn, list); };
                const away = (e) => { if (!dd.contains(e.target)) close(); };
                function close() {
                    dd.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false');
                    window.removeEventListener('scroll', place, true); window.removeEventListener('resize', place); document.removeEventListener('click', away);
                }
                function open() {
                    dd.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); place();
                    window.addEventListener('scroll', place, true); window.addEventListener('resize', place); setTimeout(() => document.addEventListener('click', away), 0);
                }
                btn.addEventListener('click', () => (dd.classList.contains('is-open') ? close() : open()));
                btn.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
                list.addEventListener('click', (e) => {
                    const o = e.target.closest('[role=option]'); if (!o) return;
                    const id = o.getAttribute('data-v');
                    opts.forEach((x) => x.setAttribute('aria-selected', String(x === o)));
                    label.textContent = o.textContent;
                    [].forEach.call(out.querySelectorAll('[data-t][data-g="area"]'), (x) => x.setAttribute('aria-selected', String(x.getAttribute('data-t') === id)));
                    [].forEach.call(out.querySelectorAll('[data-p][data-g="area"]'), (p) => { p.hidden = p.getAttribute('data-p') !== id; });
                    close(); btn.focus();
                });
            }
            function detail(d) {
                const by = d.areas.filter((a) => a.total > 0).sort((a, b) => a.pct - b.pct);
                return '<div class="tx-rsub">' + by.map((a, i) => '<button type="button" data-g="area" data-t="a' + i + '" aria-selected="' + (i === 0) + '">' + esc(a.label) + '</button>').join('') + '</div>' +
                    '<div class="tx-rsel"><span class="tx-rsel-k">Bidang</span><div class="ui-dd is-block" data-tx-dd><button type="button" class="ui-dd-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Pilih bidang" data-tx-ddbtn><span data-tx-ddlabel>' + esc(by[0].label) + ' (' + by[0].correct + '/' + by[0].total + ')</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button><ul class="ui-dd-list" role="listbox" tabindex="-1" aria-label="Pilih bidang" data-tx-ddlist>' + by.map((a, i) => '<li role="option" data-v="a' + i + '" aria-selected="' + (i === 0) + '">' + esc(a.label) + ' (' + a.correct + '/' + a.total + ')</li>').join('') + '</ul></div></div>' +
                    by.map((a, i) => '<section class="tx-rdet" data-g="area" data-p="a' + i + '"' + (i ? ' hidden' : '') + '><h4>' + esc(a.label) + '<em>' + a.correct + '/' + a.total + ' benar &middot; ' + a.pct + '%</em></h4><p>' + esc(first(AREA_INFO[a.key] || '')) + '</p><small>Saran latihan: ' + esc(TIPS[a.key] || 'ulangi pembahasan soal yang salah') + '.</small></section>').join('');
            }
            function review(d) {
                return '<div class="tx-rlist">' + d.items.map((it, i) => '<details class="' + (it.ok ? 'is-ok' : 'is-bad') + '"><summary><b>No. ' + (i + 1) + '</b><span>' + esc(it.text) + '</span><i>' + (it.ok ? 'Benar' : (it.picked === null ? 'Kosong' : 'Salah')) + '</i></summary><div>' + esc(it.text) + '<br>Jawabanmu: <b>' + esc(it.picked === null ? '(kosong)' : it.picked) + '</b>' + (it.ok ? '' : '. Yang benar: <b>' + esc(it.right) + '</b>') + '<small>' + esc(it.explain) + '</small></div></details>').join('') + '</div>';
            }
            function resultOpen(d, used) {
                const pct = Math.round(d.correct / d.total * 100);
                const tab = (id, l, on) => '<button type="button" data-g="main" data-t="' + id + '" aria-selected="' + on + '">' + l + '</button>';
                return '<div class="tx-sum">' + ring(d.correct / d.total, d.correct + '/' + d.total, pct + '%') +
                    '<div><p class="tx-eyebrow">Hasil simulasi</p><h2 class="font-display">Kamu menjawab benar ' + d.correct + ' dari ' + d.total + ' soal.</h2>' +
                    '<ul class="tx-tiles"><li><b>' + d.correct + '</b><span>Benar</span></li><li><b>' + (d.total - d.correct) + '</b><span>Salah atau kosong</span></li><li><b>' + fmt(used) + '</b><span>Waktu terpakai</span></li></ul></div></div>' +
                    '<div class="tx-rt" role="tablist">' + tab('bidang', 'Bidang', true) + tab('rincian', 'Rincian', false) + tab('bahas', 'Pembahasan' + (d.total - d.correct > 0 ? ' <em>' + (d.total - d.correct) + '</em>' : ''), false) + '</div>' +
                    '<div class="tx-rp" data-g="main" data-p="bidang"><div class="tx-rp-2"><div>' + radar(d.areas.map((a) => a.pct / 100)) + '</div><div>' + bars(d.areas.map((a) => [a.label + ' (' + a.correct + '/' + a.total + ')', a.pct])) + '</div></div></div>' +
                                        '<div class="tx-rp" data-g="main" data-p="rincian" hidden>' + detail(d) + '</div>' +
                    '<div class="tx-rp" data-g="main" data-p="bahas" hidden>' + review(d) + '</div>' +
                    '<div class="tx-next"><a href="' + URL_LEARN + '"><small>Perkuat dulu</small><b>Buka modul belajar</b><span>Mulai dari bidang yang paling lemah.</span></a>' +
                    '<a class="is-main" href="' + URL_EXAM + '"><small>Uji lebih dalam</small><b>Kerjakan soal ujian penuh</b><span>Lebih banyak soal dengan batas waktu asli.</span></a></div>' +
                    '<button type="button" class="tx-again" data-tx-again>Ulangi simulasi</button>';
            }
        })();
    </script>
<script>
        (function () { var h = document.querySelector('.site-header'); function f() { if (h) document.documentElement.style.setProperty('--hh', Math.ceil(h.getBoundingClientRect().height) + 'px'); } f(); window.addEventListener('resize', f); })();
    </script>
@endsection
