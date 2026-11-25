<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <meta name="robots" content="noindex">
    <title>{{ $attempt->type->name }} | {{ config('platform.brand.name') }}</title>
    <style>
        /* Ruang simulasi: putih, polos, fokus ke soal. Satu aksen biru, kuning hanya untuk ragu-ragu. */
        .xr { --ink: #162033; --mut: #5b6b82; --line: #e3e8f0; --line2: #dde3ee; --acc: #2f5fd0; --acc7: #2448a8; --acc50: #f2f6ff; --amb: #f6c445; }
        .xr button { font: inherit; cursor: pointer; }
        .xr-top { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid var(--line); }
        .xr-top-in { display: flex; align-items: center; gap: 1rem; max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; height: 4rem; }
        .xr-title { min-width: 0; flex: 1; }
        .xr-title b { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .95rem; font-weight: 700; color: var(--ink); }
        .xr-title span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: .05rem; font-size: .76rem; color: var(--mut); }
        .xr-title .xr-tt > span > span { display: inline; }
        .xr-pill { display: none; }
        .xr-clock { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 99px; background: #f1f4fa; color: var(--ink); transition: background .2s, color .2s; }
        .xr-clock svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: var(--acc); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .xr-clock small { display: none; }
        .xr-clock b { display: block; font-size: 1rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .xr-clock.is-crit { background: #e5484d; color: #fff; animation: xr-pulse 1s ease-in-out infinite; }
        .xr-clock.is-crit svg { stroke: #fff; }
        @keyframes xr-pulse { 50% { opacity: .75; } }
        .xr-end { height: 2.75rem; padding: 0 1.2rem; border: 1.5px solid var(--ink); border-radius: .8rem; background: #fff; font-size: .88rem; font-weight: 700; color: var(--ink); transition: background .15s, color .15s; }
        .xr-end:hover { background: var(--ink); color: #fff; }
        .xr-prog { height: .25rem; background: var(--line); }
        .xr-prog i { display: block; width: 0; height: 100%; background: var(--acc); transition: width .35s; }

        .xr-grid { display: grid; gap: 1.5rem; max-width: 72rem; margin: 0 auto; padding: 2rem 1.5rem 3rem; }
        @media (min-width: 1024px) { .xr-grid { grid-template-columns: minmax(0, 1fr) 19rem; } .xr-side { position: sticky; top: 6rem; align-self: start; } }
        .xr-card { padding: 2rem; border: 1px solid var(--line); border-radius: 1.25rem; background: #fff; }
        .xr-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; }
        .xr-seg { display: inline-flex; align-items: center; padding: .35rem .8rem; border-radius: 99px; background: #eaf0fd; font-size: .75rem; font-weight: 700; color: var(--acc7); }
        .xr-count { font-size: .82rem; font-weight: 600; color: var(--mut); }
        .xr-qwrap { margin-top: 1.25rem; }
        .xr-no { display: none; }
        .xr-q { margin: 0; font-size: 1.3rem; font-weight: 600; line-height: 1.55; color: var(--ink); white-space: pre-line; }
        .xr-qimg { margin: 1rem 0 0; } .xr-qimg[hidden] { display: none; }
        .xr-qimg img { display: block; max-width: 100%; max-height: 22rem; width: auto; height: auto; margin-inline: auto; border: 1px solid var(--l, #e2e6ec); border-radius: .9rem; background: #fff; object-fit: contain; }
        .xr-opts { display: grid; gap: .65rem; margin-top: 1.5rem; }
        .xr-opt { display: flex; align-items: center; gap: .9rem; min-height: 3.5rem; padding: .65rem 1rem; border: 1.5px solid var(--line2); border-radius: .9rem; background: #fff; cursor: pointer; transition: border-color .15s, background .15s; }
        .xr-opt:hover { border-color: #9bb4ea; }
        .xr-opt:has(input:checked) { border-color: var(--acc); background: var(--acc50); }
        .xr-opt:has(input:focus-visible) { outline: 2px solid var(--acc); outline-offset: 2px; }
        .xr-let { display: grid; flex: none; place-items: center; width: 2rem; height: 2rem; border-radius: 50%; background: #eef1f6; font-size: .8rem; font-weight: 800; color: #3d4c63; transition: background .15s, color .15s; }
        .xr-opt:has(input:checked) .xr-let { background: var(--acc); color: #fff; }
        .xr-opt-t { font-size: 1rem; line-height: 1.5; color: var(--ink); }
        .xr-foot { display: flex; align-items: center; justify-content: space-between; gap: .7rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #edf0f5; }
        .xr-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; height: 3rem; padding: 0 1.4rem; border: 1.5px solid var(--line2); border-radius: .8rem; background: #fff; font-size: .92rem; font-weight: 700; color: var(--ink); transition: border-color .15s, background .15s; }
        .xr-btn svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
        .xr-btn:hover:not(:disabled) { border-color: var(--ink); }
        .xr-btn:disabled { opacity: .4; cursor: default; }
        .xr-btn.is-main { border-color: var(--acc); background: var(--acc); color: #fff; }
        .xr-btn.is-main:hover:not(:disabled) { background: var(--acc7); border-color: var(--acc7); }
        .xr-btn[aria-pressed="true"] { border-color: #e9c24a; background: #fffaf0; color: #6b4e00; }
        .xr-tip { margin: .9rem 0 0; text-align: center; font-size: .75rem; color: var(--mut); }
        .xr-nav { padding: 1.4rem; }
        .xr-nav h2 { margin: 0; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--acc7); }
        .xr-sum { display: flex; align-items: baseline; gap: .4rem; margin-top: .5rem; font-size: .84rem; color: var(--mut); }
        .xr-sum b { font-size: 1.6rem; line-height: 1; color: var(--ink); }
        .xr-qgrid { display: grid; grid-template-columns: repeat(5, 1fr); gap: .45rem; margin-top: 1rem; }
        .xr-pg { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .8rem; }
        .xr-pg[hidden] { display: none; }
        .xr-pg span { font-size: .76rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--mut); }
        .xr-pg button { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; padding: 0; border: 1.5px solid var(--line2); border-radius: .65rem; background: #fff; color: var(--ink); }
        .xr-pg button:disabled { opacity: .35; }
        .xr-pg svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
        .xr-dot { display: grid; place-items: center; aspect-ratio: 1; border: 1.5px solid var(--line2); border-radius: .65rem; background: #fff; font-size: .8rem; font-weight: 700; font-variant-numeric: tabular-nums; color: #3d4c63; transition: border-color .15s; }
        .xr-dot:hover { border-color: #9bb4ea; }
        .xr-dot.is-done { border-color: var(--acc); background: var(--acc); color: #fff; }
        .xr-dot.is-flag { border-color: var(--amb); background: var(--amb); color: var(--ink); }
        .xr-dot.is-cur { outline: 2px solid var(--ink); outline-offset: 2px; }
        .xr-legend { display: flex; flex-wrap: wrap; gap: .4rem 1rem; margin: 1rem 0 0; padding: 0; list-style: none; font-size: .76rem; color: var(--mut); }
        .xr-legend li { display: flex; align-items: center; gap: .4rem; }
        .xr-legend i { width: .7rem; height: .7rem; border-radius: .25rem; border: 1.5px solid #c9d2e1; background: #fff; }
        .xr-legend .a i { background: var(--acc); border-color: var(--acc); } .xr-legend .b i { background: var(--amb); border-color: var(--amb); }
        .xr-finish { width: 100%; height: 3rem; margin-top: 1.1rem; border: 0; border-radius: .8rem; background: var(--ink); font-size: .92rem; font-weight: 700; color: #fff; transition: background .15s; }
        .xr-finish:hover { background: var(--acc); }
        .xr-save { margin: .8rem 0 0; text-align: center; font-size: .76rem; font-weight: 600; color: var(--mut); }
        .xr-dlg { width: min(92vw, 27rem); margin: auto; padding: 0; border: 1px solid var(--line); border-radius: 1.5rem; background: #fff; color: var(--ink); overflow: visible; box-shadow: 0 28px 60px -24px rgb(22 32 51 / .35), 0 2px 6px rgb(22 32 51 / .06); }
        .xr-dlg[open] { animation: xr-dlg-in .3s cubic-bezier(.2, .9, .3, 1.1); }
        .xr-dlg[open]::backdrop { animation: xr-fade .25s ease-out; }
        .xr-dlg::backdrop { background: rgb(255 255 255 / .55); backdrop-filter: blur(5px); }
        .xr-dlg-in { padding: 2rem 1.75rem 1.6rem; text-align: center; }
        .xr-dlg-ic { display: grid; place-items: center; width: 4rem; height: 4rem; margin: -4.1rem auto 1rem; border: 4px solid #fff; border-radius: 50%; background: var(--acc); color: #fff; box-shadow: 0 10px 22px -8px rgb(47 95 208 / .6); }
        .xr-dlg-ic svg { width: 1.8rem; height: 1.8rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
        .xr-dlg.is-warn .xr-dlg-ic { background: var(--amb); color: #5b4300; box-shadow: 0 10px 22px -8px rgb(246 196 69 / .7); }
        .xr-dlg h2 { margin: 0; font-size: 1.4rem; font-weight: 700; letter-spacing: -.01em; color: var(--ink); }
        .xr-dlg p { margin: .5rem 0 0; font-size: .9rem; line-height: 1.6; color: #4a5a73; }
        .xr-dlg-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin-top: 1.25rem; }
        .xr-dlg-stats div { padding: .7rem .4rem; border: 1px solid var(--line); border-radius: .9rem; background: #f7f9fd; }
        .xr-dlg-stats b { display: block; font-size: 1.4rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; color: var(--ink); }
        .xr-dlg-stats small { display: block; margin-top: .2rem; font-size: .7rem; font-weight: 700; color: var(--mut); }
        .xr-dlg-stats .is-a b { color: var(--acc); } .xr-dlg-stats .is-f b { color: #a87a00; } .xr-dlg-stats .is-u b { color: #c2410c; }
        .xr-dlg-act { display: grid; gap: .6rem; margin-top: 1.4rem; }
        .xr-dlg-act .xr-btn { width: 100%; height: 3.1rem; }
        .xr-dlg-act .is-main { order: -1; box-shadow: 0 10px 20px -12px rgb(47 95 208 / .8); }
        @keyframes xr-dlg-in { from { opacity: 0; transform: translateY(14px) scale(.96); } to { opacity: 1; transform: none; } }
        @keyframes xr-fade { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 640px) {
            .xr-top-in { padding: 0 1rem; height: 3.75rem; gap: .6rem; }
            .xr-end { padding: 0 .4rem; border: 0; background: none; color: var(--acc); }
            .xr-end:hover { background: none; color: var(--acc7); }
            .xr-grid { padding: 1.25rem 1rem 7rem; }
            .xr-card { padding: 1.25rem 1.1rem; }
            .xr-q { font-size: 1.15rem; }
            .xr-tip { display: none; }
            .xr-foot { position: fixed; left: 0; right: 0; bottom: 0; z-index: 25; margin: 0; padding: .75rem 1rem 1rem; border-top: 1px solid var(--line); background: #fff; }
            .xr-foot .xr-btn { height: 3.25rem; border-radius: .9rem; }
            .xr-foot #btn-prev, .xr-foot #btn-flag { flex: none; width: 3.25rem; padding: 0; }
            .xr-foot #btn-prev .xr-lbl, .xr-foot #btn-flag .xr-lbl { display: none; }
            .xr-foot #btn-next { flex: 1; }
        }
        @media (prefers-reduced-motion: reduce) { .xr-opt, .xr-dot, .xr-btn, .xr-finish, .xr-end, .xr-prog i { transition: none; } .xr-dlg[open], .xr-dlg[open]::backdrop { animation: none; } .xr-clock.is-crit { animation: none; } }

        /* ---------- HP/tablet: gaya simpel, daftar soal lewat panah di header ---------- */
        .xr .xr-title { padding: 0; border: 0; background: none; font: inherit; text-align: left; cursor: default; display: flex; align-items: center; gap: .5rem; }
        .xr-title > .xr-tt { display: block; min-width: 0; flex: 1; }
        .xr-ch, .xr-x, .xr-bd { display: none; }
        .xr-nav { position: relative; }
        @media (max-width: 1023px) {
            body.xr { background: #fff; }
            .xr .xr-title { cursor: pointer; }
            .xr-ch { display: block; flex: none; width: 1.7rem; height: 1.7rem; padding: .35rem; border-radius: 99px; background: #eaf0fd; fill: none; stroke: var(--acc); stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; box-sizing: border-box; }
            .xr-title:active .xr-ch { background: #d8e4fb; }
            .xr-title > .xr-tt > span { font-weight: 600; }
            .xr-title #progress-text b { color: var(--acc); }
            .xr-grid { grid-template-columns: 1fr; }
            main > .xr-card { padding: 0; border: 0; border-radius: 0; background: none; }
            .xr-bd { display: block; position: fixed; inset: 0; z-index: 85; background: rgb(22 32 51 / .45); opacity: 0; pointer-events: none; transition: opacity .2s; }
            body.is-sheet .xr-bd { opacity: 1; pointer-events: auto; }
            .xr-side { position: fixed; left: 0; right: 0; bottom: 0; z-index: 90; max-height: 82vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom, 0px); border-radius: 1.4rem 1.4rem 0 0; background: #fff; box-shadow: 0 -20px 50px -18px rgb(22 32 51 / .5); transform: translateY(105%); visibility: hidden; transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility 0s .28s; }
            body.is-sheet .xr-side { transform: none; visibility: visible; transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility 0s; }
            .xr-side .xr-card { border: 0; border-radius: 0; padding: 1.5rem 1.25rem 1.2rem; }
            .xr-side .xr-card::before { content: ''; position: absolute; top: .55rem; left: 50%; width: 2.4rem; height: .28rem; margin-left: -1.2rem; border-radius: 99px; background: #d5dbe6; }
            .xr-qgrid { gap: .5rem; }
            .xr-dot { font-size: .95rem; }
            .xr-x { display: grid; place-items: center; position: absolute; top: 1.1rem; right: 1rem; width: 2.2rem; height: 2.2rem; padding: 0; border: 0; border-radius: 99px; background: #f1f4fa; color: var(--ink); }
            .xr-x svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; }
            .xr-side .xr-save { margin: 0; padding: 0 1.25rem 1.2rem; }
        }
        @media (max-width: 640px) {
            .xr-grid { padding: 1rem 1rem 7rem; } .xr-q { font-size: 1.12rem; }
            .xr-title .xr-mode { display: none; }
            .xr-top-in { gap: .5rem; }
            .xr-clock { padding: .4rem .75rem; gap: .4rem; } .xr-clock b { font-size: .95rem; }
            .xr-end { flex: none; }
        }
        @media (prefers-reduced-motion: reduce) { .xr-side, .xr-bd { transition: none !important; } }
    </style>
</head>
<body @class(['xr min-h-screen bg-surface font-sans text-graphite-700 antialiased', 'xr-lg' => $prefs['large_text']])>
    {{-- Bar atas: timer selalu terlihat --}}
    <header class="xr-top">
        <div class="xr-top-in">
            <span class="xr-logo hidden sm:inline-flex"><x-logo /></span>
            <button type="button" class="xr-title" id="btn-nav" aria-haspopup="dialog" aria-controls="xr-side" aria-label="Lihat daftar soal">
                <span class="xr-tt">
                    <b>{{ $attempt->type->name }}</b>
                    <span><span class="xr-mode">{{ $attempt->mode === 'full' ? 'Ujian penuh' : 'Mode coba' }} &middot; </span><span id="progress-text" style="display:inline">Soal 1/{{ $items->count() }} &middot; <b style="display:inline;font-size:inherit;font-weight:800">0</b> terjawab</span></span>
                </span>
                <svg class="xr-ch" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
            </button>
            <div class="xr-clock" role="timer" aria-live="off">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9.5 2.5h5"/></svg>
                <div><small>Sisa waktu</small><b id="timer">--:--</b></div>
            </div>
            <button type="button" id="btn-finish-top" class="xr-end">Selesai</button>
        </div>
        <div class="xr-prog"><i id="progress-bar"></i></div>
    </header>

    <div class="xr-bd" id="nav-bd"></div>
    <div class="xr-grid">
        <main>
            <div class="xr-card">
                <div class="xr-meta">
                    <span class="xr-seg" id="q-segment">Segmen</span>
                    <span class="xr-count">Soal <span id="q-number" class="tabular-nums">1</span> dari {{ $items->count() }}</span>
                </div>
                <div class="xr-qwrap">
                    <span class="xr-no" id="q-no" aria-hidden="true">01</span>
                    <p id="q-body" class="xr-q"></p>
                </div>
                <figure class="xr-qimg" id="q-img-wrap" hidden><img id="q-img" alt="Gambar soal"></figure>
                <div id="q-options" class="xr-opts" role="radiogroup" aria-labelledby="q-body"></div>

                <div class="xr-foot">
                    <button type="button" id="btn-prev" class="xr-btn" aria-label="Sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg><span class="xr-lbl">Sebelumnya</span></button>
                    <button type="button" id="btn-flag" class="xr-btn" aria-pressed="false"></button>
                    <button type="button" id="btn-next" class="xr-btn is-main"><span>Berikutnya</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                </div>
            </div>
            <p class="xr-tip">Pintasan: tombol A&ndash;E memilih jawaban, panah kiri/kanan berpindah soal, F menandai ragu-ragu.</p>
        </main>

        <aside class="xr-side" id="xr-side">
            <div class="xr-card xr-nav">
                <button type="button" class="xr-x" id="btn-nav-x" aria-label="Tutup daftar soal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
                <h2>Navigasi soal</h2>
                <p class="xr-sum"><b id="nav-count">0</b> dari {{ $items->count() }} soal terjawab</p>
                <div id="q-grid" class="xr-qgrid"></div>
                <div class="xr-pg" id="q-pg" hidden>
                    <button type="button" id="q-pg-prev" aria-label="20 soal sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
                    <span id="q-pg-t"></span>
                    <button type="button" id="q-pg-next" aria-label="20 soal berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></button>
                </div>
                <ul class="xr-legend">
                    <li class="a"><i></i> Terjawab</li>
                    <li class="b"><i></i> Ragu-ragu</li>
                    <li><i></i> Belum dijawab</li>
                </ul>
                <button type="button" id="btn-finish" class="xr-finish">Selesaikan ujian</button>
            </div>
            <p id="save-state" class="xr-save" aria-live="polite">Jawaban tersimpan otomatis.</p>
        </aside>
    </div>

    {{-- Konfirmasi selesai --}}
    <dialog id="dlg-finish" class="xr-dlg" aria-labelledby="dlg-title">
        <div class="xr-dlg-in">
            <span class="xr-dlg-ic" aria-hidden="true">
                <svg id="dlg-ic-ok" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                <svg id="dlg-ic-warn" viewBox="0 0 24 24" hidden><path d="M12 8v5M12 16.5v.01"/></svg>
            </span>
            <h2 id="dlg-title" class="font-display">Selesaikan ujian?</h2>
            <p id="dlg-summary"></p>
            <div class="xr-dlg-stats">
                <div class="is-a"><b id="dlg-n-a">0</b><small>Terjawab</small></div>
                <div class="is-f"><b id="dlg-n-f">0</b><small>Ragu-ragu</small></div>
                <div class="is-u"><b id="dlg-n-u">0</b><small>Kosong</small></div>
            </div>
            <div class="xr-dlg-act">
                <button type="button" id="dlg-ok" class="xr-btn is-main">Ya, selesaikan</button>
                <button type="button" id="dlg-cancel" class="xr-btn">Kembali mengerjakan</button>
            </div>
        </div>
    </dialog>

    <form id="finish-form" method="POST" action="{{ route('exam.finish', $attempt) }}" hidden>@csrf</form>

    <script>
    (() => {
        const items = @json($items);
        const prefs = @json($prefs); // preferensi ruang ujian dari halaman Pengaturan
        const answerUrl = @json(route('exam.answer', $attempt));
        const finishUrl = @json(route('exam.finish', $attempt));
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        // Sesi hanya 15 menit tanpa aktivitas: ping tiap 5 menit agar peserta yang lama berpikir tidak kena 419.
        setInterval(() => fetch('{{ route('exam.ping') }}', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf } }).catch(() => {}), 300000);
        let remaining = {{ (int) $remaining }};
        let endAt = performance.now() + remaining * 1000; // pakai jam monotonic: tidak terpengaruh jam perangkat
        let current = Math.max(0, items.findIndex(i => !i.selected));
        if (current < 0) current = 0;
        const pending = new Map(); // question_id -> payload yang belum terkirim
        let finishing = false;

        const $ = (id) => document.getElementById(id);
        const el = { body: $('q-body'), imgWrap: $('q-img-wrap'), img: $('q-img'), seg: $('q-segment'), num: $('q-number'), opts: $('q-options'), grid: $('q-grid'),
                     timer: $('timer'), prog: $('progress-text'), bar: $('progress-bar'), flag: $('btn-flag'), state: $('save-state'), no: $('q-no') };

        const answered = () => items.filter(i => i.selected).length;
        const fmt = (s) => { const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60;
            return (h ? String(h).padStart(2, '0') + ':' : '') + String(m).padStart(2, '0') + ':' + String(x).padStart(2, '0'); };

        // Navigasi soal: 5 kotak ke samping x 4 baris = 20 soal per halaman, sisanya lewat tombol panah
        const PER = 20, pages = Math.ceil(items.length / PER);
        let gridPage = 0;
        function buildGrid() {
            el.grid.innerHTML = '';
            const from = gridPage * PER, to = Math.min(items.length, from + PER);
            for (let i = from; i < to; i++) {
                const b = document.createElement('button');
                b.type = 'button'; b.dataset.i = i; b.textContent = i + 1;
                b.className = 'xr-dot';
                b.addEventListener('click', () => { go(i); closeSheet(); });
                el.grid.appendChild(b);
            }
            const pg = $('q-pg');
            pg.hidden = pages < 2;
            $('q-pg-t').textContent = (from + 1) + '\u2013' + to + ' dari ' + items.length;
            $('q-pg-prev').disabled = gridPage === 0;
            $('q-pg-next').disabled = gridPage >= pages - 1;
        }
        function paintGrid() {
            [...el.grid.children].forEach((b) => {
                const i = +b.dataset.i, it = items[i];
                b.setAttribute('aria-current', String(i === current));
                b.className = 'xr-dot' + (it.flagged ? ' is-flag' : it.selected ? ' is-done' : '') + (i === current ? ' is-cur' : '');
                b.setAttribute('aria-label', 'Soal ' + (i + 1) + (it.selected ? ', terjawab' : ', belum dijawab') + (it.flagged ? ', ragu-ragu' : ''));
            });
            const n = answered();
            el.prog.innerHTML = 'Soal ' + (current + 1) + '/' + items.length + ' &middot; <b style="display:inline;font-size:inherit;font-weight:800">' + n + '</b> terjawab';
            const nc = $('nav-count'); if (nc) nc.textContent = n;
            el.bar.style.width = (n / items.length * 100) + '%';
        }
        function render() {
            const it = items[current];
            el.seg.textContent = it.segment;
            el.num.textContent = current + 1;
            el.body.textContent = it.body;
            if (it.image) { el.img.src = it.image; el.imgWrap.hidden = false; } else { el.img.removeAttribute('src'); el.imgWrap.hidden = true; }
            el.opts.innerHTML = '';
            Object.entries(it.options).forEach(([key, text]) => {
                const label = document.createElement('label');
                label.className = 'xr-opt';
                const input = document.createElement('input');
                input.type = 'radio'; input.name = 'opt'; input.value = key; input.className = 'sr-only'; input.checked = it.selected === key;
                input.addEventListener('change', () => choose(key));
                const chip = document.createElement('span');
                chip.className = 'xr-let';
                chip.textContent = key;
                const t = document.createElement('span');
                t.className = 'xr-opt-t'; t.textContent = text;
                label.append(input, chip, t);
                el.opts.appendChild(label);
            });
            el.flag.setAttribute('aria-pressed', String(it.flagged));
            { const t = it.flagged ? 'Hapus tanda ragu-ragu' : 'Tandai ragu-ragu'; el.flag.setAttribute('aria-label', t); el.flag.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4M5 4h12l-2 4 2 4H5"/></svg><span class="xr-lbl">' + t + '</span>'; }
            el.no.textContent = String(current + 1).padStart(2, '0');
            $('btn-prev').disabled = current === 0;
            $('btn-next').disabled = current === items.length - 1;
            // halaman kotak mengikuti soal aktif hanya saat berpindah soal (bukan saat panah halaman ditekan)
            const want = Math.floor(current / PER);
            if (want !== gridPage) { gridPage = want; buildGrid(); }
            paintGrid();
        }
        const mobileMq = matchMedia('(max-width: 1023px)');
        function openSheet() { if (!mobileMq.matches) return; document.body.classList.add('is-sheet'); $('btn-nav-x').focus({ preventScroll: true }); }
        function closeSheet() { document.body.classList.remove('is-sheet'); }
        function go(i) { current = Math.min(items.length - 1, Math.max(0, i)); render(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

        function choose(key) {
            const it = items[current]; it.selected = key; queue(it); paintGrid();
            if (prefs.auto_next && current < items.length - 1) { const from = current; setTimeout(() => { if (current === from) go(from + 1); }, 300); }
        }
        function toggleFlag() { const it = items[current]; it.flagged = !it.flagged; queue(it); render(); }

        function queue(it) { pending.set(it.id, { question_id: it.id, selected: it.selected, flagged: it.flagged }); flush(); }

        let flushing = false;
        async function flush() {
            if (flushing) return; flushing = true;
            try {
                while (pending.size) {
                    const [id, payload] = pending.entries().next().value;
                    el.state.textContent = 'Menyimpan...';
                    let res;
                    try {
                        res = await fetch(answerUrl, { method: 'POST', credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify(payload) });
                    } catch (e) { el.state.textContent = 'Koneksi terputus. Mencoba lagi...'; await new Promise(r => setTimeout(r, 3000)); continue; }
                    if (res.status === 419) { el.state.textContent = 'Sesi kedaluwarsa. Muat ulang halaman.'; break; }
                    if (!res.ok && res.status !== 422) { el.state.textContent = 'Gagal menyimpan. Mencoba lagi...'; await new Promise(r => setTimeout(r, 3000)); continue; }
                    const data = await res.json().catch(() => ({}));
                    if (pending.get(id) === payload) pending.delete(id);
                    if (data.finished && data.redirect) { finishing = true; window.onbeforeunload = null; location.replace(data.redirect); return; }
                    if (typeof data.remaining === 'number') endAt = performance.now() + data.remaining * 1000; // sinkron ulang dengan server
                    el.state.textContent = 'Tersimpan.';
                }
            } finally { flushing = false; }
        }

        async function submitFinal() {
            if (finishing) return; finishing = true;
            el.state.textContent = 'Menyelesaikan...';
            const guard = Date.now();
            while (pending.size && Date.now() - guard < 8000) { await flush(); await new Promise(r => setTimeout(r, 200)); }
            window.onbeforeunload = null;
            try {
                const r = await fetch(finishUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = r.ok ? await r.json() : null;
                if (d && d.redirect) { location.replace(d.redirect); return; } // replace: ruang soal keluar dari riwayat Back
            } catch (e) { /* jatuh ke kirim form biasa */ }
            $('finish-form').submit();
        }

        function openFinish() {
            const n = answered(), flagged = items.filter(i => i.flagged).length;
            if (prefs.confirm_finish === 'unanswered' && n === items.length && !flagged) { submitFinal(); return; }
            $('dlg-summary').textContent = (items.length - n) > 0
                ? (items.length - n) + ' soal belum dijawab' + (flagged ? ' dan ' + flagged + ' ditandai ragu-ragu' : '') + '. Setelah selesai, jawaban tidak bisa diubah.'
                : 'Semua soal sudah dijawab' + (flagged ? ', ' + flagged + ' ditandai ragu-ragu' : '') + '. Setelah selesai, jawaban tidak bisa diubah.';
            const left = items.length - n;
            $('dlg-n-a').textContent = n; $('dlg-n-f').textContent = flagged; $('dlg-n-u').textContent = left;
            const warn = left > 0 || flagged > 0;
            $('dlg-finish').classList.toggle('is-warn', warn);
            $('dlg-ic-ok').hidden = warn; $('dlg-ic-warn').hidden = !warn;
            $('dlg-finish').showModal();
        }

        function tick() {
            const left = Math.max(0, Math.round((endAt - performance.now()) / 1000));
            el.timer.textContent = fmt(left);
            const clock = el.timer.closest('.xr-clock');
            clock.classList.toggle('is-crit', left <= 60);
            clock.classList.toggle('is-hidden', prefs.timer === 'last5' && left > 300);
            if (left <= 0 && !finishing) { submitFinal(); }
        }

        $('btn-prev').addEventListener('click', () => go(current - 1));
        $('btn-next').addEventListener('click', () => go(current + 1));
        el.flag.addEventListener('click', toggleFlag);
        $('btn-finish').addEventListener('click', () => { closeSheet(); openFinish(); });
        $('btn-nav').addEventListener('click', openSheet);
        $('btn-nav-x').addEventListener('click', closeSheet);
        $('nav-bd').addEventListener('click', closeSheet);
        mobileMq.addEventListener('change', closeSheet);
        $('btn-finish-top').addEventListener('click', openFinish);
        $('dlg-cancel').addEventListener('click', () => $('dlg-finish').close());
        $('dlg-ok').addEventListener('click', () => { $('dlg-finish').close(); submitFinal(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.classList.contains('is-sheet')) { closeSheet(); return; }
            if (e.target.closest('dialog') || e.ctrlKey || e.metaKey || e.altKey) return;
            const k = e.key.toUpperCase();
            if (k === 'ARROWRIGHT') go(current + 1);
            else if (k === 'ARROWLEFT') go(current - 1);
            else if (k === 'F') toggleFlag();
            else if (/^[A-E]$/.test(k) && items[current].options[k]) { choose(k); render(); }
        });
        window.onbeforeunload = (e) => { if (pending.size) { e.preventDefault(); e.returnValue = ''; } };

        $('q-pg-prev').addEventListener('click', () => { gridPage = Math.max(0, gridPage - 1); buildGrid(); paintGrid(); });
        $('q-pg-next').addEventListener('click', () => { gridPage = Math.min(pages - 1, gridPage + 1); buildGrid(); paintGrid(); });
        buildGrid(); render(); tick(); setInterval(tick, 1000);
    })();
    </script>
</body>
</html>
