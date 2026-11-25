@extends('layouts.app')

@section('title', 'Cari arah kariermu')
@section('description', 'Tes arah karier dengan 20 pertanyaan keahlian berbasis situasi kerja nyata.')

@section('content')
    <style>
        .kq-wrap { max-width: 64rem; }
        .kq { max-width: 56rem; margin-inline: auto; }
        .kq { --ink: #1b2638; --mut: #5b6b82; --line: #e2e6ec; --acc: #3b6cd4; --amb: #f0b429; --navy: #101b3c; --soft: #f3f5f8;
            border: 1px solid var(--line); border-radius: 1.6rem; background: #fff; overflow: hidden; color: var(--ink);
            box-shadow: 0 1px 2px rgb(27 38 56 / .06), 0 28px 60px -34px rgb(27 38 56 / .35); }
        .kq[hidden] { display: none; }
        .kq-head { padding: 1.5rem 2rem 1.3rem; background: radial-gradient(120% 140% at 0% 0%, #2a4a9a 0%, var(--navy) 62%); color: #fff; }
        .kq-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .kq-id { display: flex; align-items: center; gap: .9rem; min-width: 0; }
        .kq-owl { flex: none; width: 2.8rem; height: 2.8rem; }
        .kq-eyebrow { font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #9fbcf0; }
        .kq-title { margin-top: .15rem; font-size: 1.2rem; font-weight: 700; letter-spacing: -.01em; line-height: 1.25; }
        .kq-count { flex: none; display: flex; align-items: baseline; gap: .3rem; font-variant-numeric: tabular-nums; }
        .kq-count b { font-size: 1.7rem; font-weight: 700; letter-spacing: -.02em; }
        .kq-count span { font-size: .85rem; color: rgb(255 255 255 / .6); }
        .kq-segs { display: grid; grid-template-columns: repeat(20, 1fr); gap: .22rem; margin-top: 1.1rem; }
        .kq-segs i { height: .32rem; border-radius: 99px; background: rgb(255 255 255 / .18); transition: background .25s; }
        .kq-segs i.is-done { background: #5b8def; }
        .kq-segs i.is-cur { background: var(--amb); }
        .kq-body { display: flex; flex-direction: column; min-height: 31rem; padding: 1.9rem 2rem 1.3rem; }
        .kq-stage { flex: 1; }
        .kq-q { animation: kq-in .3s ease both; }
        .kq-q.in-l { animation-name: kq-in-l; }
        @keyframes kq-in { from { opacity: 0; transform: translateX(16px); } }
        @keyframes kq-in-l { from { opacity: 0; transform: translateX(-16px); } }
        .kq-topic { display: inline-block; padding: .28rem .75rem; border-radius: 99px; background: #eef3ff; font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--acc); }
        .kq-qt { margin: .9rem 0 0; font-size: 1.22rem; font-weight: 600; line-height: 1.5; letter-spacing: -.005em; }
        .kq-opts { display: grid; grid-template-columns: repeat(2, 1fr); grid-auto-rows: 1fr; gap: .75rem; margin-top: 1.5rem; }
        .kq-opt { position: relative; display: grid; grid-template-columns: auto 1fr; align-items: start; gap: .85rem; min-height: 6.4rem; padding: 1rem 1.1rem; border: 1.5px solid var(--line); border-radius: 1.05rem; background: #fff; text-align: left; font: inherit; font-size: .9rem; line-height: 1.5; color: var(--ink); cursor: pointer; transition: border-color .15s, background .15s, box-shadow .15s; }
        .kq-opt:hover { border-color: #b9c8e8; background: #f8faff; }
        .kq-opt .let { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; background: var(--soft); font-size: .8rem; font-weight: 800; font-style: normal; color: var(--mut); transition: background .15s, color .15s; }
        .kq-opt.is-on { border-color: var(--acc); background: #eef3ff; box-shadow: 0 0 0 3px rgb(59 108 212 / .12); }
        .kq-opt.is-on .let { background: var(--acc); color: #fff; }
        .kq-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.1rem; border-top: 1px solid var(--line); }
        .kq-back { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem 1.2rem; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; font: inherit; font-size: .88rem; font-weight: 700; color: var(--ink); cursor: pointer; }
        .kq-back:hover:not(:disabled) { border-color: var(--ink); }
        .kq-back:disabled { opacity: .35; cursor: default; }
        .kq-back svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
        .kq-hint { font-size: .76rem; color: var(--mut); }
        .kq-calc { display: grid; place-content: center; justify-items: center; gap: .9rem; min-height: 22rem; text-align: center; color: var(--mut); font-weight: 600; }
        .kq-calc i { width: 2.4rem; height: 2.4rem; border: 3px solid var(--line); border-top-color: var(--acc); border-radius: 99px; animation: kq-spin .8s linear infinite; }
        @keyframes kq-spin { to { transform: rotate(360deg); } }
        @media (max-width: 760px) {
            .kq-head { padding: 1.2rem 1.1rem 1.1rem; }
            .kq-owl { display: none; }
            .kq-body { min-height: 0; padding: 1.4rem 1.1rem 1.1rem; }
            .kq-opts { grid-template-columns: 1fr; grid-auto-rows: auto; }
            .kq-opt { min-height: 0; }
            .kq-hint { display: none; }
        }
        @media (prefers-reduced-motion: reduce) { .kq-q, .kq-calc i { animation: none; } }

        /* ---------- Hasil tes: hero ringkas, 3 ringkasan satu baris, tab ---------- */
        .kr { --ink: #1b2638; --mut: #5b6b82; --line: #e2e6ec; --acc: #3b6cd4; --amb: #f0b429; --soft: #f3f5f8; --good: #2f9e6b; --bad: #d9480f; --warn: #b7791f; color: var(--ink); }
        .kr [hidden] { display: none !important; }
        :where(.kr button) { font: inherit; cursor: pointer; }
        .kr-hero { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 1.5rem; padding: 1.7rem 2.2rem; border-radius: 1.5rem; color: #fff;
            background: radial-gradient(70% 110% at 0% 110%, #2c4fa8 0%, rgb(44 79 168 / 0) 60%), linear-gradient(135deg, #14214a, #0c1531); box-shadow: 0 26px 50px -34px rgb(12 21 49 / .9); }
        .kr-eyebrow { font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #9fbcf0; }
        .kr-hero h2 { margin-top: .45rem; font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 700; line-height: 1.12; letter-spacing: -.02em; }
        .kr-hero h2 em { display: block; font-style: normal; color: var(--amb); }
        .kr-lead { max-width: 34rem; margin-top: .7rem; font-size: .9rem; line-height: 1.55; color: rgb(255 255 255 / .82); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .kr-cta { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.1rem; }
        .kr-again { padding: .65rem 1.2rem; border: 1.5px solid rgb(255 255 255 / .3); border-radius: .8rem; background: rgb(255 255 255 / .05); font-size: .88rem; font-weight: 700; color: #fff; }
        .kr-again:hover { background: rgb(255 255 255 / .12); }
        .kr-badge { display: grid; justify-items: center; gap: .7rem; }
        .kr-ring { position: relative; width: 8rem; height: 8rem; filter: drop-shadow(0 0 16px rgb(240 180 41 / .3)); }
        .kr-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .kr-ring circle { fill: none; stroke-width: 8; }
        .kr-ring .bg { stroke: rgb(255 255 255 / .14); }
        .kr-ring .fg { stroke: var(--amb); stroke-linecap: round; transition: stroke-dashoffset 1.2s cubic-bezier(.2, .8, .2, 1); }
        .kr-ring div { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
        .kr-ring b { font-size: 1.9rem; font-weight: 700; line-height: 1; }
        .kr-ring small { margin-top: .25rem; font-size: .66rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #a9c1f1; }
        .kr-div { max-width: 14rem; padding: .3rem .85rem; border-radius: 99px; background: rgb(255 255 255 / .12); text-align: center; font-size: .72rem; font-weight: 700; color: #e3ecff; }
        .kr-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .8rem; margin-top: 1rem; }
        .kr-kpi { padding: .9rem 1.1rem; border: 1px solid var(--line); border-top: 4px solid var(--acc); border-radius: 1rem; background: #fff; min-width: 0; }
        .kr-kpi.is-good { border-top-color: var(--good); } .kr-kpi.is-weak { border-top-color: var(--bad); } .kr-kpi.is-tip { border-top-color: var(--warn); }
        .kr-kpi small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: var(--mut); }
        .kr-kpi b { display: block; margin-top: .25rem; overflow: hidden; font-size: 1.1rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .kr-kpi b em { margin-left: .35rem; font-style: normal; font-size: .95rem; color: var(--mut); }
        .kr-kpi p { margin-top: .2rem; overflow: hidden; font-size: .76rem; color: var(--mut); text-overflow: ellipsis; white-space: nowrap; }
        .kr-tabs { display: flex; gap: .3rem; margin-top: 1rem; padding: .3rem; border: 1px solid var(--line); border-radius: 1rem; background: #fff; overflow-x: auto; scrollbar-width: none; }
        .kr-tabs::-webkit-scrollbar { display: none; }
        .kr-tab { flex: 1 0 auto; padding: .6rem 1.1rem; border: 0; border-radius: .75rem; background: transparent; font-size: .88rem; font-weight: 700; color: var(--mut); white-space: nowrap; }
        .kr-tab:hover { background: var(--soft); color: var(--ink); }
        .kr-tab[aria-selected="true"] { background: var(--ink); color: #fff; }
        .kr-pane { margin-top: .9rem; }
        .kr-card { padding: 1.15rem 1.3rem; border: 1px solid var(--line); border-radius: 1.1rem; background: #fff; }
        .kr-card > h3 { font-size: 1rem; font-weight: 800; }
        .kr-two { display: grid; grid-template-columns: 1fr 1.05fr; align-items: stretch; gap: .9rem; }
        .kr-radar { display: block; width: 100%; max-width: 22rem; margin: .4rem auto 0; }
        .kr-radar .grid { fill: none; stroke: #d5dbe6; stroke-width: 1; }
        .kr-radar .axis { stroke: #e2e6ec; stroke-width: 1; }
        .kr-radar .shape { fill: rgb(59 108 212 / .22); stroke: var(--acc); stroke-width: 2.2; stroke-linejoin: round; }
        .kr-radar .dot { fill: var(--acc); }
        .kr-radar text { font-size: 10.5px; font-weight: 700; fill: var(--ink); }
        .kr-dims { display: flex; flex-direction: column; justify-content: space-between; gap: .55rem; margin-top: .9rem; }
        .kr-d { display: grid; grid-template-columns: 5.6rem 1fr 2.6rem; align-items: center; gap: .8rem; font-size: .86rem; }
        .kr-d b { font-weight: 700; }
        .kr-d em { font-style: normal; font-weight: 800; font-size: .85rem; text-align: right; font-variant-numeric: tabular-nums; color: #3a4a64; }
        .kr-bar { display: block; height: .55rem; border-radius: 99px; background: #edf0f5; overflow: hidden; }
        .kr-bar i { display: block; width: 0; height: 100%; border-radius: 99px; background: #c3cddd; transition: width .9s cubic-bezier(.2, .8, .2, 1); }
        .kr-bar.is-hot i { background: linear-gradient(90deg, var(--acc), var(--amb)); }
        .kr-insight { margin-top: .9rem; padding: .85rem 1.1rem; border-left: 4px solid var(--acc); border-radius: .3rem .9rem .9rem .3rem; background: #f8faff; font-size: .86rem; line-height: 1.6; }
        .kr-careers { display: grid; gap: .8rem; }
        .kr-c { display: flex; flex-direction: column; padding: 1.05rem 1.2rem; border: 1.5px solid var(--line); border-radius: 1.1rem; background: #fff; color: inherit; text-decoration: none; transition: border-color .15s, transform .15s; }
        .kr-c:hover { border-color: #b9c8e8; transform: translateY(-2px); }
        .kr-c.is-top { border-color: var(--acc); box-shadow: 0 0 0 3px rgb(59 108 212 / .12); }
        .kr-c small { font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--mut); }
        .kr-c strong { margin-top: .2rem; font-size: 1.02rem; font-weight: 800; line-height: 1.25; }
        .kr-c-pct { display: flex; align-items: center; gap: .7rem; margin-top: .8rem; }
        .kr-c-pct b { font-size: 1.4rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .kr-c-pct .kr-bar { flex: 1; }
        .kr-c p { margin-top: .7rem; font-size: .82rem; line-height: 1.5; color: #33415a; }
        .kr-c .go { margin-top: auto; padding-top: .8rem; font-size: .82rem; font-weight: 800; color: var(--acc); }
        .kr-all { display: inline-block; margin-top: .9rem; font-size: .86rem; font-weight: 700; color: var(--acc); }
        .kr-cols { display: grid; grid-template-columns: repeat(3, 1fr); align-items: stretch; gap: .9rem; }
        .kr-col { padding: 1.1rem 1.25rem; border: 1px solid var(--line); border-top: 4px solid var(--acc); border-radius: 1.1rem; background: #fff; }
        .kr-col.is-good { border-top-color: var(--good); } .kr-col.is-weak { border-top-color: var(--bad); } .kr-col.is-tip { border-top-color: var(--warn); }
        .kr-col > h3 { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); }
        .kr-col.is-good > h3 { color: var(--good); } .kr-col.is-weak > h3 { color: var(--bad); } .kr-col.is-tip > h3 { color: var(--warn); }
        .kr-col article { margin-top: .75rem; }
        .kr-col article + article { margin-top: .9rem; padding-top: .9rem; border-top: 1px dashed var(--line); }
        .kr-col h4 { font-size: .92rem; font-weight: 800; }
        .kr-col h4 em { margin-left: .3rem; font-style: normal; font-weight: 700; color: var(--mut); }
        .kr-col p { margin-top: .25rem; font-size: .84rem; line-height: 1.55; color: #33415a; }
        .kr-steps { display: grid; gap: .8rem; margin: 0; padding: 0; list-style: none; counter-reset: st; }
        .kr-steps li { counter-increment: st; padding: 1rem 1.15rem; border: 1px solid var(--line); border-radius: 1.1rem; background: #fff; font-size: .86rem; line-height: 1.55; }
        .kr-steps li::before { content: counter(st); display: grid; place-items: center; width: 1.7rem; height: 1.7rem; margin-bottom: .6rem; border-radius: 99px; background: var(--amb); font-size: .8rem; font-weight: 800; color: var(--ink); }
        .kr-evi { margin-top: .9rem; }
        .kr-evi-l { display: grid; gap: .7rem; margin: .8rem 0 0; padding: 0; list-style: none; }
        .kr-evi-l li { padding: .75rem .9rem; border-radius: .85rem; background: var(--soft); font-size: .8rem; line-height: 1.5; color: #33415a; }
        .kr-note { margin-top: 1.2rem; text-align: center; font-size: .8rem; color: var(--mut); }
        @media (max-width: 860px) {
            .kr-hero { grid-template-columns: 1fr; padding: 1.5rem 1.3rem; }
            .kr-badge { order: -1; justify-self: start; justify-items: start; }
            .kr-ring { width: 6.5rem; height: 6.5rem; } .kr-ring b { font-size: 1.5rem; }
            .kr-kpis { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: .3rem; }
            .kr-kpis .kr-kpi { flex: 0 0 15rem; scroll-snap-align: start; }
            .kr-two, .kr-cols, .kr-steps, .kr-evi-l { grid-template-columns: 1fr !important; }
            .kr-careers { display: flex !important; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: .3rem; }
            .kr-careers .kr-c { flex: 0 0 16rem; scroll-snap-align: start; }
            .kr-d { grid-template-columns: 5rem 1fr 2.4rem; gap: .6rem; }
        }

        .qz-result { scroll-margin-top: calc(var(--hh, 3.6rem) + .75rem); }
        .kr-tab .sm { display: none; }
        @media (max-width: 860px) {
            .kr-hero { gap: 1rem; padding: 1.25rem 1.2rem 1.4rem; border-radius: 1.3rem; }
            .kr-hero h2 { font-size: 1.5rem; }
            .kr-lead { max-width: none; font-size: .88rem; -webkit-line-clamp: 3; }
            .kr-cta { flex-direction: column; gap: .55rem; margin-top: 1rem; }
            .kr-cta .btn-primary, .kr-cta .kr-again { display: inline-flex; align-items: center; justify-content: center; width: 100%; min-height: 3rem; text-align: center; }
            .kr-div { max-width: none; }
            .kr-kpis { display: grid; grid-template-columns: 1fr; gap: .55rem; overflow: visible; padding: 0; }
            .kr-kpis .kr-kpi { flex: none; padding: .8rem 1rem; border-top: 1px solid var(--line) !important; border-left: 4px solid var(--acc); }
            .kr-kpi.is-good { border-left-color: var(--good); } .kr-kpi.is-weak { border-left-color: var(--bad); } .kr-kpi.is-tip { border-left-color: var(--warn); }
            .kr-kpi b { font-size: 1rem; }
            .kr-kpi p { white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.4; }
            .kr-tabs { display: grid; grid-template-columns: repeat(4, 1fr); gap: .2rem; overflow: visible; padding: .25rem; }
            .kr-tab { padding: .65rem .2rem; font-size: .8rem; text-align: center; }
            .kr-tab .lg { display: none; } .kr-tab .sm { display: inline; }
            .kr-card { padding: 1rem 1.05rem; }
            .kr-careers { display: grid !important; grid-template-columns: 1fr !important; overflow: visible; padding: 0; }
            .kr-careers .kr-c { flex: none; }
            .kr-d { grid-template-columns: 4.6rem 1fr 2.4rem; }
            .kr-note { padding: 0 .5rem; }
        }
    </style>

    <section class="qz">
        <div class="qz-wrap kq-wrap">
            <div class="kq" data-qz-card>
                <header class="kq-head">
                    <div class="kq-top">
                        <div class="kq-id">
                            <x-owl class="kq-owl" :animated="true" />
                            <div>
                                <p class="kq-eyebrow">Tes arah karier &middot; 20 pertanyaan keahlian</p>
                                <h1 class="font-display kq-title">Karier mana yang paling cocok untukmu?</h1>
                            </div>
                        </div>
                        <div class="kq-count" aria-live="polite"><b data-qz-n>1</b><span>/ {{ count($questions) }}</span></div>
                    </div>
                    <div class="kq-segs" data-qz-segs role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($questions) }}" aria-valuenow="1">
                        @foreach ($questions as $_)<i></i>@endforeach
                    </div>
                </header>
                <div class="kq-body">
                    <div class="kq-stage" data-qz-stage aria-live="polite"></div>
                    <div class="kq-foot">
                        <button type="button" class="kq-back" data-qz-back><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>Kembali</button>
                        <span class="kq-hint">Pilih reaksi yang paling jujur &middot; tekan 1 sampai 4</span>
                    </div>
                </div>
            </div>

            <div class="qz-result" data-qz-result hidden aria-live="polite"></div>
            <noscript><p class="qz-noscript">Tes ini butuh JavaScript. Aktifkan JavaScript lalu muat ulang halaman.</p></noscript>
        </div>
    </section>

    <script>
        (function () { var h = document.querySelector('.site-header'); function f() { if (h) document.documentElement.style.setProperty('--hh', Math.ceil(h.getBoundingClientRect().height) + 'px'); } f(); window.addEventListener('resize', f); })();
    </script>

    <script>
        (function () {
            const qs = @json($questions);
            const URL_QUIZ = @json(route('careers.analyze'));
            const URL_SIM = @json(route('exam.index').'#karier');
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            const post = (url, body) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) })
                .then((r) => r.ok ? r.json() : Promise.reject(r.status));

            /* ================= Tes arah karier ================= */
            const card = document.querySelector('[data-qz-card]'), root = card.parentElement;
            const stage = root.querySelector('[data-qz-stage]'), segsBox = root.querySelector('[data-qz-segs]'), num = root.querySelector('[data-qz-n]');
            const back = root.querySelector('[data-qz-back]'), res = root.querySelector('[data-qz-result]');
            const segs = [].slice.call(segsBox.children);
            let idx = 0, answers = [], locked = false;

            function paint() {
                segs.forEach((s, k) => { s.classList.toggle('is-done', k < idx); s.classList.toggle('is-cur', k === idx); });
                segsBox.setAttribute('aria-valuenow', idx + 1);
                num.textContent = idx + 1;
                back.disabled = idx === 0;
            }
            function show(dir) {
                const q = qs[idx], picked = answers[idx];
                stage.innerHTML = '<div class="kq-q ' + (dir === 'l' ? 'in-l' : '') + '"><span class="kq-topic">' + esc(q.topic) + '</span><h2 class="kq-qt">' + esc(q.text) + '</h2><div class="kq-opts" role="radiogroup">' +
                    q.options.map((o, k) => '<button type="button" class="kq-opt' + (picked === k ? ' is-on' : '') + '" role="radio" aria-checked="' + (picked === k) + '" data-k="' + k + '"><i class="let">' + 'ABCD'[k] + '</i><span>' + esc(o) + '</span></button>').join('') + '</div></div>';
                paint();
            }
            function choose(k) {
                if (locked) return; locked = true;
                answers[idx] = k;
                [].forEach.call(stage.querySelectorAll('.kq-opt'), (b) => b.classList.toggle('is-on', +b.dataset.k === k));
                setTimeout(() => {
                    locked = false;
                    if (idx < qs.length - 1) { idx++; show('r'); } else { finish(); }
                }, reduce ? 0 : 280);
            }
            stage.addEventListener('click', (e) => { const b = e.target.closest('.kq-opt'); if (b) choose(+b.dataset.k); });
            back.addEventListener('click', () => { if (idx > 0 && !locked) { idx--; show('l'); } });
            addEventListener('keydown', (e) => {
                if (card.hidden || !/^[1-4]$/.test(e.key) || e.ctrlKey || e.metaKey || e.altKey) return;
                if (/^(INPUT|TEXTAREA|SELECT)$/.test((document.activeElement || {}).tagName)) return;
                if (qs[idx] && qs[idx].options[+e.key - 1] !== undefined) choose(+e.key - 1);
            });

            function finish() {
                stage.innerHTML = '<div class="kq-calc"><i aria-hidden="true"></i>Menghitung profil keahlianmu...</div>';
                segs.forEach((s) => { s.classList.remove('is-cur'); s.classList.add('is-done'); });
                post(URL_QUIZ, { answers: answers }).then(function (d) {
                    setTimeout(() => { card.hidden = true; result(d); }, reduce ? 0 : 600);
                }).catch(function () {
                    idx = qs.length - 1; locked = false; show();
                    stage.insertAdjacentHTML('afterbegin', '<p style="margin-bottom:.8rem;color:#b42318;font-weight:600">Gagal mengirim jawaban, coba pilih lagi.</p>');
                });
            }

            function ring(p) {
                const C = 2 * Math.PI * 42;
                return '<div class="kr-ring"><svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="42" class="bg"/><circle cx="50" cy="50" r="42" class="fg" stroke-dasharray="' + C.toFixed(1) + '" stroke-dashoffset="' + C.toFixed(1) + '" data-p="' + p + '" data-c="' + C.toFixed(1) + '"/></svg><div><b class="font-display">' + p + '%</b><small>cocok</small></div></div>';
            }
            const first = (t) => { const m = String(t || '').match(/^.*?[.!?](?=\s|$)/); return m ? m[0] : String(t || ''); };
            const cap1 = (t) => String(t || '').replace(/^./, (c) => c.toUpperCase());
            function hero(d) {
                const t = d.top;
                return '<section class="kr-hero"><div><p class="kr-eyebrow">Hasil tes arah karier</p><h2 class="font-display">Kamu paling cocok jadi <em>' + esc(t.name) + '</em></h2>' +
                    '<p class="kr-lead">' + esc(t.summary) + '</p><div class="kr-cta"><a class="btn-primary" href="' + esc(t.url) + '">Coba simulasi gratis &rarr;</a><button type="button" class="kr-again" data-qz-again>Ulangi tes</button></div></div>' +
                    '<div class="kr-badge">' + ring(t.pct) + '<span class="kr-div">' + esc(t.division) + '</span></div></section>';
            }
            function kpis(d) {
                const hi = d.profile[0], lo = d.profile[d.profile.length - 1];
                const third = d.contrast ? { cls: 'is-tip', k: 'Kurang cocok', n: d.contrast.name, v: d.contrast.pct + '%', p: 'Butuh usaha lebih besar' } : { cls: 'is-tip', k: 'Pilihan kedua', n: (d.careers[1] || d.top).name, v: (d.careers[1] || d.top).pct + '%', p: 'Karier terdekat berikutnya' };
                const card = (cls, k, n, v, p) => '<article class="kr-kpi ' + cls + '"><small>' + k + '</small><b title="' + esc(n) + '">' + esc(n) + '<em>' + v + '</em></b><p>' + esc(p) + '</p></article>';
                return '<div class="kr-kpis">' + card('is-good', 'Gaya terkuat', hi.label, hi.pct + '%', hi.desc) + card('is-weak', 'Perlu dilatih', lo.label, lo.pct + '%', lo.desc) + card(third.cls, third.k, third.n, third.v, third.p) + '</div>';
            }
            function radar(profile) {
                const n = profile.length, cx = 150, cy = 118, R = 78;
                const pt = (r, k) => [cx + r * Math.cos(-Math.PI / 2 + k * 2 * Math.PI / n), cy + r * Math.sin(-Math.PI / 2 + k * 2 * Math.PI / n)];
                const poly = (r) => Array.from({ length: n }, (_, k) => pt(r, k).map((v) => v.toFixed(1)).join(',')).join(' ');
                let g = '<svg class="kr-radar" viewBox="-20 0 340 236" role="img" aria-label="Radar gaya kerja">';
                [.33, .66, 1].forEach((m) => { g += '<polygon class="grid" points="' + poly(R * m) + '"/>'; });
                for (let k = 0; k < n; k++) { const p = pt(R, k); g += '<line class="axis" x1="' + cx + '" y1="' + cy + '" x2="' + p[0].toFixed(1) + '" y2="' + p[1].toFixed(1) + '"/>'; }
                g += '<polygon class="shape" points="' + profile.map((d, k) => pt(R * Math.max(.06, d.pct / 100), k).map((v) => v.toFixed(1)).join(',')).join(' ') + '"/>';
                profile.forEach((d, k) => { const p = pt(R * Math.max(.06, d.pct / 100), k); g += '<circle class="dot" cx="' + p[0].toFixed(1) + '" cy="' + p[1].toFixed(1) + '" r="3"/>'; });
                profile.forEach((d, k) => { const p = pt(R + 15, k); const a = p[0] < cx - 6 ? 'end' : (p[0] > cx + 6 ? 'start' : 'middle'); g += '<text x="' + p[0].toFixed(1) + '" y="' + (p[1] + 4).toFixed(1) + '" text-anchor="' + a + '">' + esc(d.label) + '</text>'; });
                return g + '</svg>';
            }
            const dimRow = (p, i) => '<div class="kr-d" title="' + esc(p.desc) + '"><b>' + esc(p.label) + '</b><span class="kr-bar' + (i < 3 ? ' is-hot' : '') + '"><i data-w="' + p.pct + '"></i></span><em>' + p.pct + '%</em></div>';
            function stylePane(d) {
                return '<div class="kr-two"><section class="kr-card"><h3 class="font-display">Peta gaya kerjamu</h3>' + radar(d.profile) + '</section>' +
                    '<section class="kr-card"><h3 class="font-display">Skor tiap gaya <small style="font-weight:600;color:var(--mut)">dari yang terkuat</small></h3><div class="kr-dims">' + d.profile.map(dimRow).join('') + '</div></section></div>' +
                    '<p class="kr-insight">' + esc(d.insight) + '</p>';
            }
            function careerPane(d) {
                const list = d.careers.slice(0, 3);
                return '<div class="kr-careers" style="grid-template-columns:repeat(' + list.length + ',1fr)">' + list.map((c, i) =>
                    '<a class="kr-c' + (i === 0 ? ' is-top' : '') + '" href="' + esc(c.url) + '"><small>#' + (i + 1) + ' &middot; ' + esc(c.division) + '</small><strong>' + esc(c.name) + '</strong>' +
                    '<span class="kr-c-pct"><b>' + c.pct + '%</b><span class="kr-bar' + (i === 0 ? ' is-hot' : '') + '"><i data-w="' + c.pct + '"></i></span></span>' +
                    '<p>' + esc(first((c.reasons || [])[0] || c.summary)) + '</p><span class="go">Coba simulasi &rarr;</span></a>').join('') + '</div>' +
                    '<a class="kr-all" href="' + URL_SIM + '">Lihat semua karier &rarr;</a>';
            }
            function notePane(d) {
                const strong = d.profile.slice(0, 2), weak = d.profile.slice(-2).reverse(), best = d.careers[0];
                const dim = (p, x) => '<article><h4>' + esc(p.label) + '<em>' + p.pct + '%</em></h4><p>' + esc(cap1(x)) + '</p></article>';
                const col = (cls, title, items) => '<section class="kr-col ' + cls + '"><h3>' + title + '</h3>' + items.join('') + '</section>';
                const fit = [
                    '<article><h4>Cocok: ' + esc(best.name) + '<em>' + best.pct + '%</em></h4><p>' + esc(first((best.reasons || [])[0] || best.summary)) + '</p></article>',
                    d.contrast ? '<article><h4>Kurang cocok: ' + esc(d.contrast.name) + '<em>' + d.contrast.pct + '%</em></h4><p>' + esc(first(d.contrast.why)) + '</p></article>'
                        : '<article><h4>Pilihan lain</h4><p>Lihat tab Karier cocok untuk membandingkan.</p></article>'
                ];
                return '<div class="kr-cols">' +
                    col('is-good', 'Kekuatan', strong.map((p) => dim(p, p.desc + '.'))) +
                    col('is-weak', 'Perlu dilatih', weak.map((p) => dim(p, 'Untuk menguatkannya, ' + p.tip + '.'))) +
                    col('is-tip', 'Kecocokan', fit) + '</div>';
            }
            function stepPane(d) {
                const best = d.careers[0], steps = best.steps.slice(0, 4), evi = (best.evidence || []).slice(0, 3);
                return '<ol class="kr-steps" style="grid-template-columns:repeat(' + steps.length + ',1fr)">' + steps.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ol>' +
                    (evi.length ? '<section class="kr-card kr-evi"><h3 class="font-display">Dari jawabanmu</h3><ul class="kr-evi-l" style="grid-template-columns:repeat(' + evi.length + ',1fr)">' + evi.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul></section>' : '');
            }
            function result(d) {
                const tab = (id, label, on, short) => '<button type="button" class="kr-tab" role="tab" data-g="main" data-t="' + id + '" aria-selected="' + on + '"><span class="lg">' + label + '</span><span class="sm">' + (short || label) + '</span></button>';
                res.innerHTML = '<div class="kr">' + hero(d) + kpis(d) +
                    '<div class="kr-tabs" role="tablist" aria-label="Isi hasil">' + tab('gaya', 'Gaya kerja', true, 'Gaya') + tab('karier', 'Karier cocok', false, 'Karier') + tab('catatan', 'Catatan', false) + tab('langkah', 'Langkah', false) + '</div>' +
                    '<div class="kr-pane" data-g="main" data-p="gaya">' + stylePane(d) + '</div>' +
                    '<div class="kr-pane" data-g="main" data-p="karier" hidden>' + careerPane(d) + '</div>' +
                    '<div class="kr-pane" data-g="main" data-p="catatan" hidden>' + notePane(d) + '</div>' +
                    '<div class="kr-pane" data-g="main" data-p="langkah" hidden>' + stepPane(d) + '</div>' +
                    '<p class="kr-note">Ini panduan awal berdasarkan jawabanmu, bukan vonis. Coba simulasinya untuk merasakan bidangnya.</p></div>';
                res.hidden = false;
                const fill = () => [].forEach.call(res.querySelectorAll('.kr-bar i[data-w]'), (i) => { if (!i.offsetParent) return; i.style.width = i.dataset.w + '%'; });
                res.onclick = function (e) {
                    const b = e.target.closest('[data-t]'); if (!b) return;
                    const g = b.getAttribute('data-g'), id = b.getAttribute('data-t');
                    [].forEach.call(res.querySelectorAll('[data-t][data-g="' + g + '"]'), (x) => x.setAttribute('aria-selected', String(x === b)));
                    [].forEach.call(res.querySelectorAll('[data-p][data-g="' + g + '"]'), (p) => { p.hidden = p.getAttribute('data-p') !== id; });
                    requestAnimationFrame(fill);
                };
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    res.classList.add('is-on');
                    const fg = res.querySelector('.kr-ring .fg'); if (fg) fg.style.strokeDashoffset = (+fg.dataset.c * (1 - fg.dataset.p / 100)).toFixed(1);
                    fill();
                }));
                res.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
                res.querySelector('[data-qz-again]').addEventListener('click', () => {
                    answers = []; idx = 0; res.hidden = true; res.classList.remove('is-on'); card.hidden = false; show(); card.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
                });
            }
            show();
        })();
    </script>
@endsection
