/*
 * Pratinjau CV asli. Saat berkas PDF dipilih di formulir Analisis CV, halaman PDF digambar di browser (pdf.js)
 * menjadi gambar JPEG, dan posisi tiap baris teks dicatat. Keduanya ikut terkirim bersama formulir supaya server
 * bisa menampilkan CV asli di kiri dengan panah ke bagian yang dimaksud. Bila gagal, analisis tetap jalan tanpa gambar.
 */
(function () {
    'use strict';

    var MAX_PAGES = 3;
    var TARGET_WIDTH = 1500; // cukup tajam saat di-zoom 3x di pratinjau

    /** Kelompokkan potongan teks pdf.js menjadi baris. Koordinat sudah dinormalkan 0..1 (x, y = kiri-atas). */
    function groupLines(items) {
        var list = items.filter(function (it) { return it.str.replace(/\s+/g, '') !== ''; });
        list.forEach(function (it) { it.cy = it.y + it.h / 2; });
        list.sort(function (a, b) { return a.cy - b.cy || a.x - b.x; });

        var rows = [];
        list.forEach(function (it) {
            var row = rows[rows.length - 1];
            if (row && Math.abs(it.cy - row.cy) <= 0.5 * Math.min(it.h, row.h)) {
                row.items.push(it);
                row.cy = (row.cy * (row.items.length - 1) + it.cy) / row.items.length;
                row.h = Math.max(row.h, it.h);
            } else {
                rows.push({ items: [it], cy: it.cy, h: it.h });
            }
        });

        var lines = [];
        rows.forEach(function (row) {
            row.items.sort(function (a, b) { return a.x - b.x; });
            var cur = null;
            row.items.forEach(function (it) {
                var gap = cur ? it.x - (cur.x + cur.w) : 0;
                // Celah lebar di baris yang sama = kolom lain, jadi dipisah jadi baris sendiri.
                if (cur && gap <= 2.5 * Math.max(it.h, cur.h)) {
                    var space = gap > 0.12 * Math.max(it.h, cur.h) && !/\s$/.test(cur.t) && !/^\s/.test(it.str) ? ' ' : '';
                    var right = Math.max(cur.x + cur.w, it.x + it.w);
                    var top = Math.min(cur.y, it.y);
                    var bottom = Math.max(cur.y + cur.h, it.y + it.h);
                    cur.t += space + it.str;
                    cur.w = right - cur.x;
                    cur.y = top;
                    cur.h = bottom - top;
                    cur.fs = Math.max(cur.fs, it.h);
                } else {
                    if (cur) lines.push(cur);
                    cur = { t: it.str, x: it.x, y: it.y, w: it.w, h: it.h, fs: it.h };
                }
            });
            if (cur) lines.push(cur);
        });

        return lines
            .map(function (l) {
                return { t: l.t.replace(/\s+/g, ' ').trim(), x: r4(l.x), y: r4(l.y), w: r4(l.w), h: r4(l.fs) };
            })
            .filter(function (l) { return l.t !== ''; });
    }

    function r4(n) { return Math.round(n * 10000) / 10000; }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { groupLines: groupLines };
        return;
    }

    var form = document.querySelector('[data-cv-form]');
    if (!form) return;
    var input = form.querySelector('[data-cv-input]');
    var base = form.getAttribute('data-cv-pdfjs');
    if (!input || !base) return;
    if (base.slice(-1) !== '/') base += '/'; // asset() membuang garis miring akhir; tanpa ini URL jadi /vendor/pdfjspdf.min.js (404)

    var job = null;
    var submitted = false;
    var token = 0;

    function reset() {
        form.querySelectorAll('[data-cv-visual]').forEach(function (el) { el.remove(); });
    }

    function hidden(name, value) {
        var el = document.createElement('input');
        el.type = 'hidden';
        el.name = name;
        el.value = value;
        el.setAttribute('data-cv-visual', '');
        form.appendChild(el);
    }

    async function prepare(file, mine) {
        var pdfjs = await import(base + 'pdf.min.js');
        pdfjs.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.js';
        var doc = await pdfjs.getDocument({
            data: await file.arrayBuffer(),
            standardFontDataUrl: base + 'standard_fonts/',
            isEvalSupported: false,
        }).promise;

        var count = Math.min(doc.numPages, MAX_PAGES);
        var pages = [];
        var images = [];
        for (var p = 1; p <= count; p++) {
            var page = await doc.getPage(p);
            var first = page.getViewport({ scale: 1 });
            var scale = Math.min(4, TARGET_WIDTH / first.width);
            var vp = page.getViewport({ scale: scale });

            var canvas = document.createElement('canvas');
            canvas.width = Math.floor(vp.width);
            canvas.height = Math.floor(vp.height);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp, background: 'rgb(255,255,255)' }).promise;
            var url = canvas.toDataURL('image/jpeg', 0.84);
            if (url.length > 1500000) url = canvas.toDataURL('image/jpeg', 0.68);
            if (url.length > 1500000) url = canvas.toDataURL('image/jpeg', 0.5);
            images.push(url);

            var tc = await page.getTextContent();
            var items = [];
            tc.items.forEach(function (it) {
                if (typeof it.str !== 'string' || !it.transform) return;
                var t = pdfjs.Util.transform(vp.transform, it.transform);
                var fs = Math.hypot(t[2], t[3]);
                if (!isFinite(fs) || fs <= 0) return;
                items.push({
                    str: it.str,
                    x: t[4] / vp.width,
                    y: (t[5] - fs) / vp.height,
                    w: (it.width * vp.scale) / vp.width,
                    h: fs / vp.height,
                });
            });
            pages.push({ lines: groupLines(items) });
            page.cleanup();
        }
        doc.destroy();

        if (mine !== token) return;
        reset();
        hidden('cv_layout', JSON.stringify({ total: doc.numPages, pages: pages }));
        images.forEach(function (u) { hidden('cv_pages[]', u); });
    }

    input.addEventListener('change', function () {
        token += 1;
        var mine = token;
        reset();
        job = null;
        var f = input.files && input.files[0];
        if (!f || !/\.pdf$/i.test(f.name)) return;
        job = Promise.race([
            prepare(f, mine),
            new Promise(function (resolve) { setTimeout(resolve, 25000); }),
        ]).catch(function () { /* tanpa gambar: analisis teks tetap jalan */ });
    });

    form.addEventListener('submit', function (e) {
        if (!job || submitted) return;
        e.preventDefault();
        submitted = true;
        job.then(function () { form.submit(); }, function () { form.submit(); });
    }, true);

    // Tombol Kembali memulihkan halaman dari cache: izinkan kirim ulang.
    window.addEventListener('pageshow', function (e) { if (e.persisted) submitted = false; });
})();
