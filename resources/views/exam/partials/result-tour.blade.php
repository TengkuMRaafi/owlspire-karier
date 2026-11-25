{{-- Tur pengenalan halaman hasil: elemen disorot satu per satu, hanya untuk kunjungan pertama. Gaya dan skrip ditulis di sini agar langsung jalan. Prefix tg- (bukan tr-) karena tr- sudah dipakai quiz.css. --}}
<style>
    .tg { position: fixed; inset: 0; z-index: 9990; pointer-events: none; }
    .tg[hidden] { display: none; }
    .tg-shield { position: absolute; inset: 0; pointer-events: auto; }
    .tg-spot { position: absolute; border-radius: 1rem; box-shadow: 0 0 0 9999px rgb(11 18 32 / .72), 0 0 0 3px #f5c542, 0 0 28px 6px rgb(245 197 66 / .35); transition: top .45s cubic-bezier(.2, .8, .2, 1), left .45s cubic-bezier(.2, .8, .2, 1), width .45s cubic-bezier(.2, .8, .2, 1), height .45s cubic-bezier(.2, .8, .2, 1); animation: tg-pulse 2.2s ease-in-out infinite; pointer-events: none; }
    .tg-card { position: absolute; width: min(21rem, calc(100vw - 1.5rem)); padding: 1.1rem 1.2rem 1rem; border-radius: 1.2rem; background: #fff; color: #1b2638; box-shadow: 0 28px 60px -20px rgb(0 0 0 / .6); pointer-events: auto; transition: top .45s cubic-bezier(.2, .8, .2, 1), left .45s cubic-bezier(.2, .8, .2, 1), opacity .2s; }
    .tg-card.is-swap { opacity: 0; }
    .tg-step { font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--color-accent-600, #3b6cd4); }
    .tg-h { margin: .3rem 0 0; font-size: 1.05rem; font-weight: 800; line-height: 1.25; }
    .tg-p { margin: .4rem 0 0; font-size: .86rem; line-height: 1.55; color: #3d4b63; }
    .tg-dots { display: flex; gap: .3rem; margin-top: .85rem; }
    .tg-dots i { width: .4rem; height: .4rem; border-radius: 99px; background: #d3dbe8; transition: width .25s, background .25s; }
    .tg-dots i.is-on { width: 1.2rem; background: var(--color-accent-600, #3b6cd4); }
    .tg-act { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-top: .9rem; }
    .tg-skip { padding: .4rem .2rem; border: 0; background: none; font: inherit; font-size: .82rem; font-weight: 700; color: #667794; cursor: pointer; }
    .tg-skip:hover { color: #1b2638; }
    .tg-nav { display: flex; gap: .45rem; }
    .tg-btn { padding: .55rem 1.05rem; border: 1.5px solid #c5cfde; border-radius: .8rem; background: #fff; font: inherit; font-size: .86rem; font-weight: 700; color: #1b2638; cursor: pointer; }
    .tg-btn:hover { background: #f3f5f8; }
    .tg-btn.is-main { border-color: var(--color-accent-600, #3b6cd4); background: var(--color-accent-600, #3b6cd4); color: #fff; }
    .tg-btn.is-main:hover { background: var(--color-accent-700, #2f58b3); }
    .tg-btn[hidden] { display: none; }
    .tg-replay { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1.1rem; border: 1.5px solid rgb(255 255 255 / .28); border-radius: .9rem; background: rgb(255 255 255 / .06); font: inherit; font-size: .86rem; font-weight: 700; color: #fff; cursor: pointer; transition: background .15s, border-color .15s; }
    .tg-replay:hover { background: rgb(255 255 255 / .14); border-color: rgb(255 255 255 / .5); }
    .tg-replay svg { width: .95rem; height: .95rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    @keyframes tg-pulse { 50% { box-shadow: 0 0 0 9999px rgb(11 18 32 / .72), 0 0 0 5px #f5c542, 0 0 38px 10px rgb(245 197 66 / .45); } }
    @media (prefers-reduced-motion: reduce) { .tg-spot, .tg-card { transition: none; animation: none; } }
</style>
<div class="tg" id="tg" hidden role="dialog" aria-modal="true" aria-labelledby="tg-h">
    <div class="tg-shield" data-tr-skip></div>
    <div class="tg-spot" id="tg-spot"></div>
    <div class="tg-card" id="tg-card">
        <p class="tg-step" id="tg-step"></p>
        <h3 class="tg-h" id="tg-h"></h3>
        <p class="tg-p" id="tg-p"></p>
        <div class="tg-dots" id="tg-dots" aria-hidden="true"></div>
        <div class="tg-act">
            <button type="button" class="tg-skip" data-tr-skip>Lewati</button>
            <div class="tg-nav">
                <button type="button" class="tg-btn" id="tg-prev">Kembali</button>
                <button type="button" class="tg-btn is-main" id="tg-next">Lanjut</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        var KEY = 'owlspire_tour_hasil_v1';
        var box = document.getElementById('tg'); if (!box) return;
        var spot = document.getElementById('tg-spot'), card = document.getElementById('tg-card');
        var elStep = document.getElementById('tg-step'), elH = document.getElementById('tg-h'), elP = document.getElementById('tg-p');
        var elDots = document.getElementById('tg-dots'), btnPrev = document.getElementById('tg-prev'), btnNext = document.getElementById('tg-next');
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var steps = [
            { sel: '.rh-score', t: 'Skor totalmu', p: 'Ini nilai akhirmu beserta persentase dari skor maksimal. Bar benar, salah, dan kosong di sebelah kirinya menunjukkan sebaran jawabanmu.' },
            { sel: '.rv', t: 'Lolos passing grade atau belum', p: 'Kartu ini membandingkan skormu dengan ambang lulus tiap bagian, lengkap dengan selisih poin yang masih kurang.' },
            { sel: '.rs-tabs', t: 'Tiga tab hasil', p: 'Ringkasan memuat skor tiap bagian, Detail berisi grafik dan analisis, Pembahasan membahas soal satu per satu. Angka merah di Pembahasan adalah jumlah soal yang perlu kamu lihat.' },
            { sel: '.rs-pane:not([hidden]) .rs-c', t: 'Skor tiap bagian', p: 'Garis hitam adalah ambang lulus, garis kuning rata-rata peserta lain. Tombol “Minta Owlspire jelaskan” menerangkan kenapa skormu segitu.' },
            { sel: '.rs-pane:not([hidden]) .rs-prio', t: 'Prioritas belajar', p: 'Urutan bagian yang paling banyak menghilangkan poinmu. Mulai dari nomor satu supaya hasil latihan paling terasa.' },
            { sel: '.rr', t: 'Peringkat kamu', p: 'Posisimu dibanding peserta lain. Angka asli terbuka untuk Premium, sedangkan akun biasa melihat contoh yang diburamkan.' },
            { sel: '.rs-next', t: 'Langkah berikutnya', p: 'Buka modul belajar untuk menguatkan bagian yang lemah, atau langsung ulangi ujian penuh dengan batas waktu dan passing grade seperti aslinya.' },
            { sel: '#oc-fab', t: 'Tanya Owlspire', p: 'Bingung dengan hasilmu? Ketuk burung hantu ini untuk bertanya, misalnya “Kenapa skorku segini?” atau minta rencana belajar 7 hari.' },
            { sel: '.rh-replay-btn', t: 'Ingin melihat panduan lagi?', p: 'Tombol Panduan ini selalu ada di bagian atas halaman hasil. Selamat berlatih!' }
        ];
        var list = [], idx = 0, open = false, lastFocus = null;

        function visible(el) { return el && el.getClientRects().length > 0; }
        function build() {
            list = steps.filter(function (s) { var el = document.querySelector(s.sel); return visible(el); });
            elDots.innerHTML = list.map(function () { return '<i></i>'; }).join('');
        }
        function place(el) {
            var r = el.getBoundingClientRect(), pad = 8, vw = window.innerWidth, vh = window.innerHeight;
            spot.style.top = (r.top - pad) + 'px'; spot.style.left = (r.left - pad) + 'px';
            spot.style.width = (r.width + pad * 2) + 'px'; spot.style.height = (r.height + pad * 2) + 'px';
            var cw = card.offsetWidth, ch = card.offsetHeight, gap = 14, top, left;
            var below = vh - r.bottom - pad, above = r.top - pad;
            if (below >= ch + gap) { top = r.bottom + pad + gap; }
            else if (above >= ch + gap) { top = r.top - pad - gap - ch; }
            else { top = vw < 640 ? (r.top > vh / 2 ? 12 : vh - ch - 12) : Math.max(12, vh - ch - 16); }
            left = r.left + r.width / 2 - cw / 2;
            left = Math.max(12, Math.min(left, vw - cw - 12));
            card.style.top = Math.max(8, top) + 'px'; card.style.left = left + 'px';
        }
        function show(i, dir) {
            idx = i; var s = list[i], el = document.querySelector(s.sel);
            card.classList.add('is-swap');
            // Pakai posisi tab Ringkasan bila pane lain sedang terbuka
            el.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
            setTimeout(function () {
                elStep.textContent = 'Langkah ' + (i + 1) + ' dari ' + list.length;
                elH.textContent = s.t; elP.textContent = s.p;
                [].forEach.call(elDots.children, function (d, k) { d.classList.toggle('is-on', k === i); });
                btnPrev.hidden = i === 0;
                btnNext.textContent = i === list.length - 1 ? 'Selesai' : 'Lanjut';
                place(el); card.classList.remove('is-swap');
            }, reduce ? 0 : 380);
        }
        function reflow() { if (open && list[idx]) { var el = document.querySelector(list[idx].sel); if (visible(el)) place(el); } }
        function seen() { try { localStorage.setItem(KEY, '1'); } catch (e) {} }
        function close() {
            open = false; box.hidden = true; seen(); document.removeEventListener('keydown', onKey);
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        }
        function start() {
            // Mulai dari tab Ringkasan supaya semua target ada
            var first = document.querySelector('.rs-tab[data-t="ringkasan"]');
            if (first && first.getAttribute('aria-selected') !== 'true') { first.click(); }
            build(); if (!list.length) return;
            lastFocus = document.activeElement; open = true; box.hidden = false;
            document.addEventListener('keydown', onKey); show(0); btnNext.focus({ preventScroll: true });
        }
        function next() { if (idx >= list.length - 1) { close(); } else { show(idx + 1); } }
        function prev() { if (idx > 0) { show(idx - 1); } }
        function onKey(e) {
            if (e.key === 'Escape') { close(); }
            else if (e.key === 'ArrowRight') { next(); }
            else if (e.key === 'ArrowLeft') { prev(); }
            else if (e.key === 'Tab') { var f = [btnPrev, btnNext, box.querySelector('.tg-skip')].filter(function (b) { return !b.hidden; }); var a = document.activeElement, k = f.indexOf(a); e.preventDefault(); f[(k + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus(); }
        }
        btnNext.addEventListener('click', next); btnPrev.addEventListener('click', prev);
        [].forEach.call(box.querySelectorAll('[data-tr-skip]'), function (b) { b.addEventListener('click', close); });
        window.addEventListener('resize', reflow); window.addEventListener('scroll', reflow, { passive: true });
        [].forEach.call(document.querySelectorAll('[data-tour-open]'), function (b) { b.addEventListener('click', start); });

        var done = false; try { done = localStorage.getItem(KEY) === '1'; } catch (e) {}
        if (!done) { setTimeout(start, 1200); }
    })();
</script>
