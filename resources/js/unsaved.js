import { confirmBox } from './feedback.js';

// Peringatan "perubahan belum disimpan" untuk form POST berisi isian (admin & user).
// Kecualikan form dengan atribut data-no-unsaved (chat, upload, pembayaran, dll).
const KEY = 'unsaved:action';
const forms = new Map(); // form -> snapshot awal
let leaving = false;      // true saat submit/keluar sudah dikonfirmasi: jangan cegat lagi
let sentinel = false;

const path = (u) => { try { return new URL(u, location.href).pathname; } catch { return ''; } };
const isTracked = (f) =>
    (f.getAttribute('method') || 'get').toLowerCase() === 'post'
    && !f.hasAttribute('data-no-unsaved')
    && !f.closest('[data-no-unsaved]')
    && /^(?!.*\/(login|register|logout))/.test(path(f.action))
    && f.querySelector('input:not([type=hidden],[type=submit],[type=button],[type=file],[type=password]),textarea,select');

const snap = (f) => JSON.stringify([...new FormData(f)].filter(([k, v]) => k !== '_token' && typeof v === 'string'));
const dirtyForms = () => [...forms].filter(([f, s]) => f.isConnected && snap(f) !== s).map(([f]) => f);
const isDirty = () => !leaving && dirtyForms().length > 0;

function hint() {
    const dirty = new Set(dirtyForms());
    forms.forEach((_, f) => {
        let el = f.querySelector('[data-unsaved-hint]');
        if (dirty.has(f) && !el) {
            const btn = f.querySelector('[type=submit]');
            if (!btn) return;
            el = document.createElement('small');
            el.dataset.unsavedHint = '';
            el.textContent = 'Ada perubahan yang belum disimpan';
            el.style.cssText = 'color:#b45309;font-weight:600;margin-right:.75rem';
            btn.parentNode.insertBefore(el, btn);
        } else if (!dirty.has(f) && el) el.remove();
    });
    if (dirty.size && !sentinel) { history.pushState({ unsaved: 1 }, ''); sentinel = true; }
}

const ask = () => confirmBox({
    title: 'Perubahan belum disimpan',
    message: 'Data yang kamu isi akan hilang kalau keluar sekarang.',
    ok: 'Keluar tanpa menyimpan', cancel: 'Tetap di halaman', tone: 'warning',
});

function init() {
    const saved = sessionStorage.getItem(KEY);
    sessionStorage.removeItem(KEY);
    document.querySelectorAll('form').forEach((f) => {
        if (!isTracked(f)) return;
        // Validasi server menolak: kembali ke form yang sama -> tetap dianggap belum tersimpan.
        forms.set(f, saved && saved === path(f.action) ? '[]' : snap(f));
        f.addEventListener('submit', () => { sessionStorage.setItem(KEY, path(f.action)); leaving = true; setTimeout(() => { leaving = false; }, 4000); });
    });
    if (!forms.size) return;
    hint();
    ['input', 'change'].forEach((ev) => document.addEventListener(ev, hint));

    document.addEventListener('click', async (e) => {
        if (!isDirty() || e.defaultPrevented) return;
        const back = e.target.closest('[data-back],[onclick*="history.back"]');
        const a = e.target.closest('a[href]');
        if (!back && !(a && !a.target && !a.hasAttribute('download') && !/^(#|javascript:|mailto:|tel:)/.test(a.getAttribute('href')) && new URL(a.href).origin === location.origin && !(a.pathname === location.pathname && a.hash))) return;
        e.preventDefault(); e.stopImmediatePropagation();
        if (await ask()) { leaving = true; back ? history.go(-(sentinel ? 2 : 1)) : (location.href = a.href); }
    }, true);

    window.addEventListener('popstate', async () => {
        if (!sentinel || leaving) return;
        sentinel = false;
        if (!dirtyForms().length) return history.back();
        history.pushState({ unsaved: 1 }, ''); sentinel = true;
        if (await ask()) { leaving = true; history.go(-2); }
    });

    window.addEventListener('beforeunload', (e) => { if (isDirty()) { e.preventDefault(); e.returnValue = ''; } });
}

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
