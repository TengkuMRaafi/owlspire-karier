/* Isian jabatan (Analisis CV ATS): kotak ketik dengan daftar saran bergaya web ini, menggantikan datalist bawaan browser.
   Boleh mengetik jabatan lain. Panah atas/bawah memilih, Enter mengisi, Esc menutup. Tanpa JS, isian tetap berupa kotak teks biasa. */
(function () {
    'use strict';
    document.querySelectorAll('[data-cv-role]').forEach(function (wrap) {
        var input = wrap.querySelector('input');
        var list = wrap.querySelector('.cv-role-list');
        var chev = wrap.querySelector('.cv-role-chev');
        var options = [];
        try { options = JSON.parse(wrap.getAttribute('data-options') || '[]'); } catch (e) { options = []; }
        if (!input || !list || !options.length) return;

        var items = [];
        var active = -1;

        function close() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            wrap.classList.remove('is-open');
            active = -1;
        }

        function pick(text) {
            input.value = text;
            close();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }

        function mark(i) {
            items.forEach(function (li, n) { li.classList.toggle('is-active', n === i); li.setAttribute('aria-selected', n === i ? 'true' : 'false'); });
            active = i;
            if (items[i]) {
                input.setAttribute('aria-activedescendant', items[i].id);
                items[i].scrollIntoView({ block: 'nearest' });
            }
        }

        function render(all) {
            var q = all ? '' : input.value.trim().toLowerCase();
            var shown = options.filter(function (o) { return !q || o.toLowerCase().indexOf(q) !== -1; });
            list.replaceChildren();
            items = shown.map(function (o, n) {
                var li = document.createElement('li');
                li.id = 'cv-role-opt-' + n;
                li.setAttribute('role', 'option');
                li.textContent = o;
                if (o.toLowerCase() === input.value.trim().toLowerCase()) li.classList.add('is-chosen');
                li.addEventListener('mousedown', function (e) { e.preventDefault(); pick(o); });
                list.appendChild(li);
                return li;
            });
            if (!items.length) { close(); return; }
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            wrap.classList.add('is-open');
            active = -1;
        }

        input.addEventListener('focus', function () { render(true); });
        input.addEventListener('input', function () { render(false); });
        input.addEventListener('blur', close);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (list.hidden) render(true);
                if (!items.length) return;
                mark(e.key === 'ArrowDown' ? (active + 1) % items.length : (active <= 0 ? items.length - 1 : active - 1));
            } else if (e.key === 'Enter' && !list.hidden && active >= 0) {
                e.preventDefault();
                pick(items[active].textContent);
            } else if (e.key === 'Escape' && !list.hidden) {
                e.preventDefault();
                close();
            }
        });
        if (chev) {
            chev.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (list.hidden) { input.focus(); render(true); } else { close(); }
            });
        }
    });
})();
