{{-- Pencarian/filter/pagination tanpa muat ulang halaman. --}}
<script>
    (function () {
        var box = document.querySelector('[data-live-results]');
        var form = document.querySelector('[data-live-form]');
        if (!box || !form || !window.fetch) return;
        var fields = [].slice.call(form.querySelectorAll('[name]'));
        var dds = [].slice.call(form.querySelectorAll('[data-dd]'));
        var ctrl, timer;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Ganti isi hasil. Kalau kontainer bertanda data-smooth: tinggi dianimasikan halus
        // (tanpa scrollbar) dan kartu yang sudah tampil tidak dianimasikan ulang.
        function swap(html) {
            var smooth = box.hasAttribute('data-smooth') && !reduce;
            var h0 = box.offsetHeight;
            var had = {};
            [].slice.call(box.querySelectorAll('[data-id]')).forEach(function (el) { had[el.dataset.id] = 1; });

            box.innerHTML = html;

            if (!smooth) return;
            var n = 0;
            [].slice.call(box.querySelectorAll('[data-id]')).forEach(function (el) {
                if (had[el.dataset.id]) el.classList.add('is-kept');
                else el.style.setProperty('--i', n++);
            });

            var h1 = box.offsetHeight;
            if (Math.abs(h1 - h0) < 2) return;
            box.style.overflow = 'hidden';
            box.style.height = h0 + 'px';
            void box.offsetHeight;
            box.style.transition = 'height .65s cubic-bezier(.2,.8,.2,1), opacity .25s ease';
            box.style.height = h1 + 'px';
            var done = function () {
                box.style.height = box.style.overflow = box.style.transition = '';
                box.removeEventListener('transitionend', onEnd);
            };
            var onEnd = function (e) { if (e.target === box && e.propertyName === 'height') done(); };
            box.addEventListener('transitionend', onEnd);
            setTimeout(done, 800);
        }

        function urlFor() {
            var p = new URLSearchParams();
            fields.forEach(function (f) { var v = f.value.trim(); if (v) p.set(f.name, v); });
            var s = p.toString();
            return form.getAttribute('action').split('#')[0] + (s ? '?' + s : '');
        }
        function load(url, mode) {
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            box.classList.add('is-busy');
            fetch(url, { headers: { 'X-Partial': 'results', 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then(function (html) {
                    swap(html);
                    box.classList.remove('is-busy');
                    if (mode === 'push') history.pushState(null, '', url);
                    else if (mode === 'replace') history.replaceState(null, '', url);
                })
                .catch(function (e) {
                    if (e.name === 'AbortError') return;
                    // Internet putus: biarkan banner koneksi yang bicara, jangan pindah ke halaman error browser.
                    if (navigator.onLine === false) { box.classList.remove('is-busy'); return; }
                    window.location.href = url;
                });
        }
        function now(mode) { clearTimeout(timer); load(urlFor(), mode); }

        /* ---- dropdown kustom ---- */
        function syncDd(dd) {
            var v = dd.querySelector('[data-dd-value]').value, found = false;
            [].slice.call(dd.querySelectorAll('[role=option]')).forEach(function (o) {
                var on = o.dataset.v === v; if (on) found = true;
                o.setAttribute('aria-selected', String(on));
                if (on) dd.querySelector('[data-dd-label]').textContent = o.textContent;
            });
            if (!found) {
                var first = dd.querySelector('[role=option]');
                first.setAttribute('aria-selected', 'true');
                dd.querySelector('[data-dd-label]').textContent = first.textContent;
            }
        }
        dds.forEach(function (dd) {
            var val = dd.querySelector('[data-dd-value]'), btn = dd.querySelector('[data-dd-btn]');
            var list = dd.querySelector('[data-dd-list]'), opts = [].slice.call(list.querySelectorAll('[role=option]')), hi = -1;

            function reflow(e) {
                if ((e && e.target === list) || !dd.classList.contains('is-open')) return;
                var r = btn.getBoundingClientRect();
                if (r.bottom < 0 || r.top > window.innerHeight) { open(false); return; }
                if (window.uiDdPlace) window.uiDdPlace(btn, list);
            }
            function open(on) {
                dd.classList.toggle('is-open', on);
                btn.setAttribute('aria-expanded', String(on));
                if (on) {
                    if (window.uiDdPlace) window.uiDdPlace(btn, list);
                    window.addEventListener('scroll', reflow, { capture: true, passive: true });
                    window.addEventListener('resize', reflow);
                } else {
                    window.removeEventListener('scroll', reflow, { capture: true });
                    window.removeEventListener('resize', reflow);
                }
                if (on) { hi = Math.max(0, opts.findIndex(function (o) { return o.getAttribute('aria-selected') === 'true'; })); mark(); }
            }
            function mark() {
                opts.forEach(function (o, i) { o.classList.toggle('is-hi', i === hi); });
                if (opts[hi]) opts[hi].scrollIntoView({ block: 'nearest' });
            }
            function pick(v) { val.value = v; syncDd(dd); open(false); now('push'); }

            btn.addEventListener('click', function () { open(!dd.classList.contains('is-open')); });
            list.addEventListener('click', function (e) {
                var o = e.target.closest('[role=option]');
                if (!o) return;
                pick(o.dataset.v); btn.focus();
            });
            document.addEventListener('click', function (e) { if (!dd.contains(e.target)) open(false); });
            btn.addEventListener('keydown', function (e) {
                var isOpen = dd.classList.contains('is-open');
                if (e.key === 'Escape') { open(false); return; }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!isOpen) { open(true); return; }
                    hi = (hi + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length; mark();
                } else if ((e.key === 'Enter' || e.key === ' ') && isOpen && hi >= 0) {
                    e.preventDefault(); pick(opts[hi].dataset.v);
                }
            });
        });

        fields.forEach(function (f) {
            if (f.type === 'hidden') return;
            f.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { load(urlFor(), 'replace'); }, 280); });
        });
        form.addEventListener('submit', function (e) { e.preventDefault(); now('push'); });

        box.addEventListener('click', function (e) {
            var a = e.target.closest('a[data-live-link]');
            if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
            e.preventDefault();
            if (a.hasAttribute('data-reset')) { fields.forEach(function (f) { f.value = ''; }); dds.forEach(syncDd); }
            load(a.href, 'push');
            if (a.matches('.ui-pg') || a.hasAttribute('data-scroll')) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        window.addEventListener('popstate', function () {
            var u = new URL(location.href);
            fields.forEach(function (f) { f.value = u.searchParams.get(f.name) || ''; });
            dds.forEach(syncDd);
            load(location.pathname + location.search, null);
        });
    })();
</script>
