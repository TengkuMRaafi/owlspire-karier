// Pemilih tanggal dan jam kustom. Menggantikan <input type="date|time|datetime-local"> bawaan browser.
// Input aslinya tetap di DOM (disembunyikan) dan menyimpan nilai yang dikirim ke server dengan format yang sama:
// date = 2026-11-16, time = 08:30, datetime-local = 2026-11-16T08:30. Atribut min, max, required, disabled ikut dihormati.
// Tanpa JavaScript, input bawaan browser tetap berfungsi.

const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const MONTHS_S = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const DOW = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const ICON = {
    cal: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3.5"/><path d="M8 3v4M16 3v4M3.5 10.5h17"/></svg>',
    clock: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>',
    prev: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14.5 6-6 6 6 6"/></svg>',
    next: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9.5 6 6 6-6 6"/></svg>',
    x: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7l10 10M17 7 7 17"/></svg>',
};

const pad = (n) => String(n).padStart(2, '0');
const ymd = (d) => `${d.y}-${pad(d.m + 1)}-${pad(d.d)}`;
const readDate = (s) => { const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? { y: +m[1], m: +m[2] - 1, d: +m[3] } : null; };
const readTime = (s) => { const m = /(?:^|[T\s])(\d{1,2}):(\d{2})/.exec(s || ''); return m ? { h: Math.min(23, +m[1]), n: Math.min(59, +m[2]) } : null; };
const todayD = () => { const t = new Date(); return { y: t.getFullYear(), m: t.getMonth(), d: t.getDate() }; };
const h = (tag, cls, html) => { const e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; };
const btnEl = (cls, html, label) => { const b = h('button', cls, html); b.type = 'button'; if (label) b.setAttribute('aria-label', label); return b; };

let current = null; // hanya satu pemilih terbuka pada satu waktu

const enhance = (input) => {
    if (input.dataset.pkReady) return;
    input.dataset.pkReady = '1';

    const kind = input.type; // date | time | datetime-local
    const hasDate = kind !== 'time';
    const hasTime = kind !== 'date';
    const required = input.required;
    const minD = hasDate ? readDate(input.min) : null;
    const maxD = hasDate ? readDate(input.max) : null;
    const minuteStep = Math.max(1, Math.round((Number(input.step) || 60) / 60));
    const placeholder = kind === 'date' ? 'Pilih tanggal' : kind === 'time' ? 'Pilih jam' : 'Pilih tanggal dan jam';

    const st = { date: hasDate ? readDate(input.value) : null, time: hasTime ? readTime(input.value) : null };
    let view = st.date ? { y: st.date.y, m: st.date.m } : (({ y, m }) => ({ y, m }))(todayD());
    let focusD = null;

    // ---------- rangka ----------
    const wrap = h('div', 'ui-pk' + (input.classList.contains('is-sm') ? ' is-sm' : ''));
    wrap.dataset.pk = kind;
    if (input.style.cssText) { wrap.style.cssText = input.style.cssText; input.style.cssText = ''; }
    const id = input.id;
    input.before(wrap);
    input.removeAttribute('id');
    input.type = 'text';
    input.className = 'ui-pk-input';
    input.tabIndex = -1;
    input.setAttribute('aria-hidden', 'true');
    input.autocomplete = 'off';
    input.addEventListener('keydown', (e) => e.preventDefault());

    const btn = btnEl('ui-pk-btn', `${kind === 'time' ? ICON.clock : ICON.cal}<span class="ui-pk-text"></span>`);
    btn.setAttribute('aria-haspopup', 'dialog');
    btn.setAttribute('aria-expanded', 'false');
    if (id) btn.id = id;
    const text = btn.querySelector('.ui-pk-text');
    const clear = btnEl('ui-pk-x', ICON.x, 'Hapus pilihan');
    clear.hidden = true;
    if (input.disabled) btn.disabled = true;

    const pop = h('div', 'ui-pk-pop');
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', placeholder);
    wrap.append(input, btn, clear, pop);

    // ---------- tampilan nilai ----------
    const fmtDate = (d) => `${d.d} ${MONTHS_S[d.m]} ${d.y}`;
    const fmtTime = (t) => `${pad(t.h)}:${pad(t.n)}`;
    const value = () => {
        if (kind === 'date') return st.date ? ymd(st.date) : '';
        if (kind === 'time') return st.time ? fmtTime(st.time) : '';
        return st.date && st.time ? `${ymd(st.date)}T${fmtTime(st.time)}` : '';
    };
    const paint = () => {
        const v = input.value;
        let label = '';
        if (v) label = kind === 'date' ? fmtDate(st.date) : kind === 'time' ? fmtTime(st.time) : `${fmtDate(st.date)}, ${fmtTime(st.time)}`;
        text.textContent = label || placeholder;
        wrap.classList.toggle('is-empty', !label);
        wrap.classList.toggle('is-invalid', false);
        clear.hidden = !label || required || input.disabled;
    };
    const commit = (quiet) => {
        input.value = value();
        paint();
        if (quiet) return;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const outOfRange = (d) => {
        const s = ymd(d);
        return (minD && s < ymd(minD)) || (maxD && s > ymd(maxD));
    };

    // ---------- kalender ----------
    let cal; let calTitle; let grid; let calPrev; let calNext;
    const renderCal = (focus) => {
        const first = new Date(view.y, view.m, 1);
        const lead = (first.getDay() + 6) % 7; // pekan dimulai Senin
        const days = new Date(view.y, view.m + 1, 0).getDate();
        const t = todayD();
        const sameMonth = (d) => d && d.y === view.y && d.m === view.m;
        const tab = sameMonth(focusD) ? focusD.d : sameMonth(st.date) ? st.date.d : sameMonth(t) ? t.d : 1;

        calTitle.textContent = `${MONTHS[view.m]} ${view.y}`;
        grid.replaceChildren();
        for (let i = 0; i < lead; i++) grid.append(h('span', 'ui-pk-gap'));
        for (let d = 1; d <= days; d++) {
            const cell = { y: view.y, m: view.m, d };
            const b = btnEl('ui-pk-day', String(d));
            b.dataset.d = d;
            b.tabIndex = d === tab ? 0 : -1;
            b.setAttribute('aria-label', `${d} ${MONTHS[view.m]} ${view.y}`);
            if (ymd(cell) === ymd(t)) b.classList.add('is-today');
            if (st.date && ymd(cell) === ymd(st.date)) { b.classList.add('is-on'); b.setAttribute('aria-pressed', 'true'); }
            if (outOfRange(cell)) b.disabled = true;
            grid.append(b);
        }
        if (focus) grid.querySelector('.ui-pk-day[tabindex="0"]')?.focus({ preventScroll: true });
    };
    const moveFocus = (delta) => {
        const cur = focusD || st.date || todayD();
        const nd = new Date(cur.y, cur.m, cur.d + delta);
        focusD = { y: nd.getFullYear(), m: nd.getMonth(), d: nd.getDate() };
        view = { y: focusD.y, m: focusD.m };
        renderCal(true);
    };
    const shiftMonth = (n) => {
        const nd = new Date(view.y, view.m + n, 1);
        view = { y: nd.getFullYear(), m: nd.getMonth() };
        focusD = null;
        renderCal(false);
    };
    const pickDay = (d) => {
        const cell = { y: view.y, m: view.m, d };
        if (outOfRange(cell)) return;
        st.date = cell;
        focusD = cell;
        if (hasTime && !st.time) st.time = { h: 8, n: 0 };
        commit();
        if (kind === 'date') { close(true); return; }
        renderCal(false);
        syncTime();
        showPane('time');
    };

    if (hasDate) {
        cal = h('div', 'ui-pk-cal');
        const head = h('div', 'ui-pk-head');
        calPrev = btnEl('ui-pk-nav', ICON.prev, 'Bulan sebelumnya');
        calNext = btnEl('ui-pk-nav', ICON.next, 'Bulan berikutnya');
        calTitle = h('b', 'ui-pk-title');
        calTitle.setAttribute('aria-live', 'polite');
        head.append(calPrev, calTitle, calNext);
        const dow = h('div', 'ui-pk-dow');
        DOW.forEach((n) => dow.append(h('span', '', n)));
        grid = h('div', 'ui-pk-grid');
        cal.append(head, dow, grid);

        calPrev.addEventListener('click', () => shiftMonth(-1));
        calNext.addEventListener('click', () => shiftMonth(1));
        grid.addEventListener('click', (e) => { const b = e.target.closest('.ui-pk-day'); if (b) pickDay(+b.dataset.d); });
        grid.addEventListener('keydown', (e) => {
            const map = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
            if (e.key in map) { e.preventDefault(); moveFocus(map[e.key]); }
            else if (e.key === 'PageDown' || e.key === 'PageUp') { e.preventDefault(); shiftMonth(e.key === 'PageDown' ? 1 : -1); grid.querySelector('.ui-pk-day[tabindex="0"]')?.focus({ preventScroll: true }); }
        });
    }

    // ---------- jam ----------
    let tm; let hourList; let minList;
    const buildList = (count, step, kindName, label) => {
        const col = h('div', 'ui-pk-col');
        col.append(h('span', 'ui-pk-col-h', label));
        const list = h('div', 'ui-pk-list');
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', label);
        list.tabIndex = 0;
        for (let v = 0; v < count; v += step) {
            const o = h('div', 'ui-pk-opt', pad(v));
            o.setAttribute('role', 'option');
            o.dataset.v = v;
            list.append(o);
        }
        list.dataset.kind = kindName;
        col.append(list);
        return { col, list };
    };
    const centre = (list, smooth) => {
        const o = list.querySelector('.is-on');
        if (!o) return;
        const top = o.offsetTop - list.clientHeight / 2 + o.offsetHeight / 2;
        if (smooth && typeof list.scrollTo === 'function') list.scrollTo({ top, behavior: 'smooth' });
        else list.scrollTop = top;
    };
    function syncTime(smooth) {
        if (!hasTime) return;
        [[hourList, st.time?.h], [minList, st.time?.n]].forEach(([list, v]) => {
            list.querySelectorAll('.ui-pk-opt').forEach((o) => {
                const on = v != null && +o.dataset.v === v;
                o.classList.toggle('is-on', on);
                o.setAttribute('aria-selected', String(on));
            });
            centre(list, smooth);
        });
    }
    const setTime = (patch) => {
        const base = st.time || { h: 8, n: 0 };
        st.time = { ...base, ...patch };
        if (hasDate && !st.date) { st.date = todayD(); view = { y: st.date.y, m: st.date.m }; renderCal(false); }
        commit();
        syncTime(true);
    };

    if (hasTime) {
        tm = h('div', 'ui-pk-time');
        const hl = buildList(24, 1, 'h', 'Jam');
        const ml = buildList(60, minuteStep, 'n', 'Menit');
        hourList = hl.list; minList = ml.list;
        tm.append(hl.col, h('span', 'ui-pk-colon', ':'), ml.col);
        [hourList, minList].forEach((list) => {
            const key = list.dataset.kind;
            list.addEventListener('click', (e) => { const o = e.target.closest('.ui-pk-opt'); if (o) setTime({ [key]: +o.dataset.v }); });
            list.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
                e.preventDefault();
                const vals = [...list.querySelectorAll('.ui-pk-opt')].map((o) => +o.dataset.v);
                const cur = st.time ? st.time[key] : vals[0];
                const i = Math.max(0, vals.indexOf(cur));
                setTime({ [key]: vals[(i + (e.key === 'ArrowDown' ? 1 : -1) + vals.length) % vals.length] });
            });
        });
    }

    // ---------- tab (hanya tanggal + jam), kaki panel ----------
    let tabs = null;
    function showPane(name) {
        if (cal) cal.hidden = name !== 'date';
        if (tm) tm.hidden = name !== 'time';
        if (tabs) tabs.forEach((t) => { const on = t.dataset.pane === name; t.setAttribute('aria-selected', String(on)); t.classList.toggle('is-on', on); });
        if (name === 'time') syncTime();
        place();
    }
    if (kind === 'datetime-local') {
        const bar = h('div', 'ui-pk-tabs');
        bar.setAttribute('role', 'tablist');
        tabs = [['date', 'Tanggal'], ['time', 'Jam']].map(([n, l]) => { const t = btnEl('ui-pk-tab', l); t.dataset.pane = n; t.setAttribute('role', 'tab'); t.addEventListener('click', () => showPane(n)); bar.append(t); return t; });
        pop.append(bar);
    }
    if (cal) pop.append(cal);
    if (tm) pop.append(tm);

    const foot = h('div', 'ui-pk-foot');
    const now = btnEl('ui-pk-link', kind === 'time' ? 'Sekarang' : 'Hari ini');
    const wipe = btnEl('ui-pk-link is-quiet', 'Hapus');
    const done = btnEl('ui-pk-done', 'Selesai');
    foot.append(now, ...(required ? [] : [wipe]), ...(kind === 'date' ? [] : [done]));
    pop.append(foot);

    now.addEventListener('click', () => {
        const t = new Date();
        if (hasDate) {
            const d = todayD();
            if (outOfRange(d)) return;
            st.date = d; view = { y: d.y, m: d.m }; focusD = d;
        }
        if (hasTime) st.time = { h: t.getHours(), n: Math.floor(t.getMinutes() / minuteStep) * minuteStep };
        commit();
        if (hasDate) renderCal(false);
        syncTime(true);
        if (kind === 'date') close(true);
    });
    wipe.addEventListener('click', () => { st.date = null; st.time = null; commit(); if (hasDate) renderCal(false); syncTime(); close(true); });
    done.addEventListener('click', () => close(true));
    clear.addEventListener('click', () => { st.date = null; st.time = null; commit(); if (hasDate) renderCal(false); syncTime(); btn.focus(); });

    // ---------- posisi dan buka/tutup ----------
    // Panel fixed, dihitung dari tombol: tidak keluar layar, membuka ke atas bila ruang bawah kurang.
    function place() {
        if (!wrap.classList.contains('is-open')) return;
        const m = 8; const gap = 6;
        const r = btn.getBoundingClientRect();
        const vw = document.documentElement.clientWidth; const vh = window.innerHeight;
        pop.style.maxHeight = '';
        pop.style.top = '0px'; pop.style.left = '0px';
        const w = pop.offsetWidth; const ph = pop.offsetHeight;
        const below = vh - r.bottom - m - gap; const above = r.top - m - gap;
        const up = ph > below && above > below;
        const room = Math.max(180, up ? above : below);
        const hh = Math.min(ph, room);
        pop.style.maxHeight = room + 'px';
        pop.style.left = Math.max(m, Math.min(r.left, vw - w - m)) + 'px';
        pop.style.top = (up ? r.top - gap - hh : r.bottom + gap) + 'px';
        pop.style.transformOrigin = up ? 'bottom' : 'top';
    }
    const reflow = (e) => {
        if (e?.target && pop.contains(e.target)) return;
        const r = btn.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) { close(false); return; }
        place();
    };
    function open() {
        if (btn.disabled || wrap.classList.contains('is-open')) return;
        current?.close(false);
        wrap.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        if (hasDate) {
            view = st.date ? { y: st.date.y, m: st.date.m } : (({ y, m }) => ({ y, m }))(minD && ymd(minD) > ymd(todayD()) ? minD : todayD());
            focusD = null;
            renderCal(false);
        }
        showPane(hasDate ? 'date' : 'time');
        place();
        window.addEventListener('scroll', reflow, { capture: true, passive: true });
        window.addEventListener('resize', reflow);
        current = api;
        if (hasDate) grid.querySelector('.ui-pk-day[tabindex="0"]')?.focus({ preventScroll: true });
        else hourList.focus({ preventScroll: true });
    }
    function close(refocus) {
        if (!wrap.classList.contains('is-open')) return;
        wrap.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        window.removeEventListener('scroll', reflow, { capture: true });
        window.removeEventListener('resize', reflow);
        if (current === api) current = null;
        if (refocus) btn.focus({ preventScroll: true });
    }
    const api = { wrap, close };

    btn.addEventListener('click', () => (wrap.classList.contains('is-open') ? close(false) : open()));
    btn.addEventListener('keydown', (e) => { if (e.key === 'ArrowDown' && !wrap.classList.contains('is-open')) { e.preventDefault(); open(); } });
    // Tab keluar dari pemilih menutup panel.
    wrap.addEventListener('focusout', (e) => { if (e.relatedTarget && !wrap.contains(e.relatedTarget)) close(false); });
    wrap.addEventListener('keydown', (e) => { if (e.key === 'Escape' && wrap.classList.contains('is-open')) { e.stopPropagation(); close(true); } });

    // Validasi bawaan form (required): tandai tombol, lalu arahkan fokus ke tombol.
    input.addEventListener('invalid', () => wrap.classList.add('is-invalid'));
    input.addEventListener('focus', () => btn.focus({ preventScroll: false }));
    input.form?.addEventListener('reset', () => setTimeout(() => {
        st.date = hasDate ? readDate(input.value) : null;
        st.time = hasTime ? readTime(input.value) : null;
        paint();
    }, 0));

    paint();
};

document.addEventListener('click', (e) => { if (current && !current.wrap.contains(e.target)) current.close(false); });

document.querySelectorAll('input[type=date], input[type=time], input[type=datetime-local]').forEach(enhance);
