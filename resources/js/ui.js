import './picker.js';
// Perilaku bersama komponen ui-: dropdown kustom dan alert yang bisa ditutup.

// Dropdown kustom. Dropdown di dalam daftar langsung (data-live-form) dan pencarian Karier
// (data-career-search) sudah dikelola skripnya sendiri, jadi dilewati di sini.
// Posisi panel dropdown: selalu fixed, dihitung dari tombolnya. Lebar pas dengan tombol (di desktop melebar bila teks panjang,
// maksimal 22rem; di HP selalu sama dengan tombol), tidak pernah keluar layar, membuka ke atas bila ruang di bawah kurang, dan tinggi menyesuaikan sisa ruang.
const ddPlace = (btn, list) => {
    const m = 8; const gap = 6;
    const r = btn.getBoundingClientRect();
    const vw = document.documentElement.clientWidth; const vh = window.innerHeight;
    // HP (<= 640px): panel selalu persis selebar tombolnya, teks panjang turun baris. Desktop boleh melebar sampai 22rem.
    const phone = vw <= 640;
    Object.assign(list.style, {
        position: 'fixed', right: 'auto', bottom: 'auto', top: '0px', left: '0px',
        width: phone ? r.width + 'px' : 'max-content', minWidth: r.width + 'px',
        maxWidth: phone ? r.width + 'px' : Math.min(vw - m * 2, Math.max(r.width, 352)) + 'px', maxHeight: '',
    });
    const w = list.offsetWidth;
    const natural = list.scrollHeight + 2;
    const below = vh - r.bottom - m - gap;
    const above = r.top - m - gap;
    const up = natural > below && above > below;
    const room = Math.max(120, up ? above : below);
    const h = Math.min(natural, 288, room);
    list.style.maxHeight = Math.min(288, room) + 'px';
    const wantLeft = Math.max(m, Math.min(r.left, vw - w - m));
    const wantTop = up ? r.top - gap - h : r.bottom + gap;
    list.style.left = wantLeft + 'px';
    list.style.top = wantTop + 'px';
    list.style.transformOrigin = up ? 'bottom' : 'top';
    // Bila ada elemen induk bertransform/filter, `fixed` mengikuti induk itu, bukan layar: geser sesuai selisihnya.
    const got = list.getBoundingClientRect();
    if (Math.abs(got.left - wantLeft) > 1 || Math.abs(got.top - wantTop) > 1) {
        list.style.left = (wantLeft + wantLeft - got.left) + 'px';
        list.style.top = (wantTop + wantTop - got.top) + 'px';
    }
};
window.uiDdPlace = ddPlace;

document.querySelectorAll('[data-dd]').forEach((dd) => {
    if (dd.closest('[data-live-form], [data-career-search]')) return;

    const val = dd.querySelector('[data-dd-value]');
    const btn = dd.querySelector('[data-dd-btn]');
    const label = dd.querySelector('[data-dd-label]');
    const list = dd.querySelector('[data-dd-list]');
    const opts = [...list.querySelectorAll('[role=option]')];
    let hi = -1;

    const reflow = (e) => {
        if (e?.target === list || !dd.classList.contains('is-open')) return;
        const r = btn.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) { open(false); return; }
        ddPlace(btn, list);
    };

    const open = (on) => {
        dd.classList.toggle('is-open', on);
        btn.setAttribute('aria-expanded', String(on));
        if (on) {
            ddPlace(btn, list);
            window.addEventListener('scroll', reflow, { capture: true, passive: true });
            window.addEventListener('resize', reflow);
        } else {
            window.removeEventListener('scroll', reflow, { capture: true });
            window.removeEventListener('resize', reflow);
        }
        if (on) {
            hi = Math.max(0, opts.findIndex((o) => o.getAttribute('aria-selected') === 'true'));
            mark();
        }
    };
    const mark = () => {
        opts.forEach((o, i) => o.classList.toggle('is-hi', i === hi));
        opts[hi]?.scrollIntoView({ block: 'nearest' });
    };
    const pick = (v) => {
        val.value = v;
        opts.forEach((o) => {
            const on = o.dataset.v === v;
            o.setAttribute('aria-selected', String(on));
            if (on) label.textContent = o.textContent;
        });
        open(false);
        val.dispatchEvent(new Event('change', { bubbles: true }));
        if (dd.hasAttribute('data-dd-submit')) dd.closest('form')?.requestSubmit();
    };

    btn.addEventListener('click', () => open(!dd.classList.contains('is-open')));
    list.addEventListener('click', (e) => {
        const o = e.target.closest('[role=option]');
        if (!o) return;
        pick(o.dataset.v);
        btn.focus();
    });
    document.addEventListener('click', (e) => { if (!dd.contains(e.target)) open(false); });
    btn.addEventListener('keydown', (e) => {
        const isOpen = dd.classList.contains('is-open');
        if (e.key === 'Escape') { open(false); return; }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) { open(true); return; }
            hi = (hi + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length;
            mark();
        } else if ((e.key === 'Enter' || e.key === ' ') && isOpen && hi >= 0) {
            e.preventDefault();
            pick(opts[hi].dataset.v);
        }
    });
});

// Alert: tombol tutup dan sembunyi otomatis (data-autohide="ms").
const closeAlert = (el) => {
    el.classList.add('is-leaving');
    setTimeout(() => el.remove(), 260);
};
document.querySelectorAll('[data-alert-close]').forEach((b) => b.addEventListener('click', () => closeAlert(b.closest('.ui-alert'))));
document.querySelectorAll('.ui-alert[data-autohide]').forEach((el) => setTimeout(() => closeAlert(el), Number(el.dataset.autohide) || 7000));

// Menu utama di layar kecil: geser otomatis supaya menu yang sedang aktif terlihat.
(() => {
    const nav = document.querySelector('.nav-links');
    const on = nav?.querySelector('.is-active');
    if (!nav || !on || nav.scrollWidth <= nav.clientWidth) return;
    const n = nav.getBoundingClientRect();
    const a = on.getBoundingClientRect();
    nav.scrollLeft += a.left - n.left - (n.width - a.width) / 2;
})();


// Menu HP: hamburger membuka dropdown. Tutup dengan Esc, klik area gelap, pilih tautan, atau saat layar melebar.
(() => {
    const btn = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-nav-menu]');
    if (!btn || !menu) return;
    const set = (open) => {
        btn.setAttribute('aria-expanded', String(open));
        btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        menu.hidden = !open;
    };
    btn.addEventListener('click', () => set(menu.hidden));
    menu.addEventListener('click', (e) => { if (e.target === menu || e.target.closest('a')) set(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) { set(false); btn.focus(); } });
    matchMedia('(min-width: 768px)').addEventListener('change', (e) => { if (e.matches) set(false); });
    addEventListener('pageshow', () => set(false));
})();

// Tabel jadi kartu di HP: judul kolom disalin ke tiap sel sebagai data-label (dipakai CSS responsive.css).
// Dijalankan ulang bila isi daftar langsung (data-live-results) diganti lewat AJAX.
const labelTables = () => {
    document.querySelectorAll('table.ui-table').forEach((t) => {
        const heads = [...(t.tHead?.rows[0]?.cells ?? [])].map((c) => c.textContent.trim());
        if (!heads.length) return;
        [...t.tBodies].forEach((b) => [...b.rows].forEach((r) => {
            if (r.cells.length !== heads.length) return;
            [...r.cells].forEach((c, i) => { if (heads[i] && !c.hasAttribute('data-label')) c.setAttribute('data-label', heads[i]); });
        }));
    });
};
labelTables();
let labelTimer;
new MutationObserver(() => { cancelAnimationFrame(labelTimer); labelTimer = requestAnimationFrame(labelTables); })
    .observe(document.body, { childList: true, subtree: true });
