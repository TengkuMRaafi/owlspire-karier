/*
 * Pratinjau CV asli. Gambar halaman CV diberi titik bernomor di bagian yang dinilai. Klik titik (atau barisnya di daftar):
 * gambar zoom ke bagian itu dan popup catatan muncul. Tinggi tetap, tidak memanjang karena catatan.
 * Tanpa skrip ini: gambar tampil biasa dan catatan terbuka di daftar.
 */
(function () {
    'use strict';

    function clamp(v, lo, hi) { return Math.min(hi, Math.max(lo, v)); }

    function init(root) {
        var stage = root.querySelector('[data-cvd-stage]');
        var sheet = root.querySelector('[data-cvd-sheet]');
        var pop = root.querySelector('[data-cvd-pop]');
        var resetBtn = root.querySelector('[data-cvd-reset]');
        if (!stage || !sheet || !pop) return;

        var pages = [].slice.call(root.querySelectorAll('[data-cvd-page]'));
        var tabs = [].slice.call(root.querySelectorAll('[data-cvd-tab]'));
        var groups = {};
        var order = [];
        root.querySelectorAll('.cvd-grp[data-b]').forEach(function (g) { groups[g.dataset.b] = g; order.push(g.dataset.b); });

        var cur = null;
        var curBox = null;
        var page = 0;
        root.classList.add('is-js');

        function showPage(i) {
            page = i;
            pages.forEach(function (p, k) { p.hidden = k !== i; });
            tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
            stage.style.setProperty('--ar', pages[i].dataset.ar);
            stage.style.setProperty('--arn', pages[i].dataset.arn);
        }

        function setZoom(s, tx, ty) {
            sheet.style.setProperty('--z', s);
            sheet.style.transform = 'translate(' + tx.toFixed(1) + 'px,' + ty.toFixed(1) + 'px) scale(' + s.toFixed(3) + ')';
        }

        function clearMarks() {
            root.querySelectorAll('.cvd-box.is-on, .cvd-grp.is-on').forEach(function (e) { e.classList.remove('is-on'); });
            root.classList.remove('has-open');
        }

        function close() {
            cur = null;
            curBox = null;
            pop.hidden = true;
            if (resetBtn) resetBtn.hidden = true;
            clearMarks();
            setZoom(1, 0, 0);
        }

        function buildPop(b, atTop) {
            var g = groups[b];
            var tone = /is-(good|weak|bad)/.exec(g.className);
            var idx = order.indexOf(b);
            var no = g.querySelector('.cvd-no:not(.is-plain)');
            var label = (g.getAttribute('aria-label') || '');

            pop.className = 'cvd-pop ' + (tone ? 'is-' + tone[1] : '') + (atTop ? ' at-top' : ' at-bottom');
            pop.innerHTML = '';

            var head = document.createElement('div');
            head.className = 'cvd-ph';
            head.innerHTML = (no ? '<span class="cvd-no">' + no.textContent + '</span>' : '') +
                '<b></b>' +
                '<span class="cvd-pn"><button type="button" data-cvd-prev aria-label="Sebelumnya">&#8249;</button><i>' + (idx + 1) + '/' + order.length + '</i><button type="button" data-cvd-next aria-label="Berikutnya">&#8250;</button></span>' +
                '<button type="button" class="cvd-x" data-cvd-close aria-label="Tutup">&times;</button>';
            head.querySelector('b').textContent = label;
            pop.appendChild(head);

            var body = document.createElement('div');
            body.className = 'cvd-pb';
            g.querySelectorAll('[data-note]').forEach(function (n) { body.appendChild(n.cloneNode(true)); });
            pop.appendChild(body);
            pop.hidden = false;
            body.scrollTop = 0;
        }

        function open(b, box) {
            if (!groups[b]) return;
            box = box || root.querySelector('.cvd-box[data-b="' + b + '"][data-primary]');
            cur = b;
            curBox = box || null;

            clearMarks();
            root.classList.add('has-open');
            groups[b].classList.add('is-on');
            root.querySelectorAll('.cvd-box[data-b="' + b + '"]').forEach(function (e) { e.classList.add('is-on'); });

            if (box) {
                showPage(parseInt(box.closest('[data-cvd-page]').dataset.p, 10) || 0);
                var W = stage.clientWidth;
                var H = stage.clientHeight;
                var x = parseFloat(box.style.left) / 100;
                var y = parseFloat(box.style.top) / 100;
                var w = parseFloat(box.style.width) / 100;
                var h = parseFloat(box.style.height) / 100;
                var cx = x + w / 2;
                var cy = y + h / 2;
                var atTop = cy > 0.5; // bagian di separuh bawah: popup di atas, supaya tidak menutupinya
                var targetY = atTop ? H * 0.7 : H * 0.3;
                var s = Math.max(1, Math.min(3.2, (0.78 * W) / Math.max(1, w * W), (0.34 * H) / Math.max(1, h * H)));
                var tx = clamp(W / 2 - s * cx * W, W - s * W, 0);
                var ty = clamp(targetY - s * cy * H, H - s * H, 0);
                setZoom(s, tx, ty);
                buildPop(b, atTop);
                if (resetBtn) { resetBtn.hidden = false; resetBtn.classList.toggle('at-top', !atTop); }
            } else {
                setZoom(1, 0, 0);
                buildPop(b, false);
                if (resetBtn) resetBtn.hidden = true;
            }

            var r = stage.getBoundingClientRect();
            if (r.top < 0 || r.bottom > window.innerHeight) stage.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function step(d) {
            if (cur === null) return;
            var i = (order.indexOf(cur) + d + order.length) % order.length;
            open(order[i]);
        }

        root.addEventListener('click', function (e) {
            var t = e.target;
            var row = t.closest('[data-cvd-open]');
            if (row) { open(row.getAttribute('data-cvd-open')); return; }
            var box = t.closest('.cvd-box');
            if (box) { open(box.dataset.b, box); return; }
            var tab = t.closest('[data-cvd-tab]');
            if (tab) { close(); showPage(parseInt(tab.getAttribute('data-cvd-tab'), 10)); return; }
            if (t.closest('[data-cvd-close]') || t.closest('[data-cvd-reset]')) { close(); return; }
            if (t.closest('[data-cvd-prev]')) { step(-1); return; }
            if (t.closest('[data-cvd-next]')) { step(1); return; }
            if (cur !== null && t.closest('[data-cvd-stage]') && !t.closest('.cvd-pop')) close();
        });

        document.addEventListener('keydown', function (e) {
            if (cur === null) return;
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowRight') step(1);
            else if (e.key === 'ArrowLeft') step(-1);
        });

        root.addEventListener('mouseover', function (e) {
            var g = e.target.closest && e.target.closest('.cvd-grp[data-b]');
            root.querySelectorAll('.cvd-box.is-hot').forEach(function (b) { b.classList.remove('is-hot'); });
            if (g) root.querySelectorAll('.cvd-box[data-b="' + g.dataset.b + '"]').forEach(function (b) { b.classList.add('is-hot'); });
        });
        root.addEventListener('mouseleave', function () {
            root.querySelectorAll('.cvd-box.is-hot').forEach(function (b) { b.classList.remove('is-hot'); });
        });

        if ('ResizeObserver' in window) {
            var last = stage.clientWidth;
            new ResizeObserver(function () {
                if (stage.clientWidth === last) return;
                last = stage.clientWidth;
                if (cur !== null) open(cur, curBox);
            }).observe(stage);
        }
    }

    function boot() { document.querySelectorAll('[data-cvd]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
