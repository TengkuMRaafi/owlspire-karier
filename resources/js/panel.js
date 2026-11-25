// Shell panel (admin dan pengguna): ciutkan sidebar di desktop, drawer di layar kecil.
const panel = document.querySelector('[data-panel]');

if (panel) {
    const KEY = 'owlspire_pn_collapsed';
    const small = window.matchMedia('(max-width: 1023px)');
    const toggle = panel.querySelector('[data-panel-toggle]');
    const scrim = panel.querySelector('[data-panel-scrim]');

    const setOpen = (on) => {
        panel.classList.toggle('is-open', on);
        toggle?.setAttribute('aria-expanded', String(on));
    };
    const setCollapsed = (on) => {
        panel.classList.toggle('is-collapsed', on);
        toggle?.setAttribute('aria-expanded', String(!on));
        try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) { /* abaikan */ }
    };

    try { if (localStorage.getItem(KEY) === '1') panel.classList.add('is-collapsed'); } catch (e) { /* abaikan */ }

    toggle?.addEventListener('click', () => {
        if (small.matches) setOpen(!panel.classList.contains('is-open'));
        else setCollapsed(!panel.classList.contains('is-collapsed'));
    });
    scrim?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    small.addEventListener('change', () => setOpen(false));
}

// Popover topbar (notifikasi dan akun): satu terbuka sekali waktu, tutup saat klik di luar atau Escape.
const pops = document.querySelectorAll('[data-pn-pop]');
if (pops.length) {
    const closeAll = (except) => pops.forEach((p) => {
        if (p === except) return;
        p.querySelector('.pn-pop')?.setAttribute('hidden', '');
        p.querySelector('[data-pn-pop-btn]')?.setAttribute('aria-expanded', 'false');
    });
    pops.forEach((p) => {
        const btn = p.querySelector('[data-pn-pop-btn]');
        const box = p.querySelector('.pn-pop');
        btn?.addEventListener('click', () => {
            const open = box.hasAttribute('hidden');
            closeAll(p);
            box.toggleAttribute('hidden', !open);
            btn.setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', (e) => { if (![...pops].some((p) => p.contains(e.target))) closeAll(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
}

// Tema terang/gelap panel: pilihan disimpan di browser (owlspire_theme), dipulihkan oleh skrip kecil di <head>.
document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    const root = document.documentElement;
    const sync = () => btn.setAttribute('aria-pressed', String(root.getAttribute('data-theme') === 'dark'));
    sync();
    btn.addEventListener('click', () => {
        const dark = root.getAttribute('data-theme') !== 'dark';
        if (dark) root.setAttribute('data-theme', 'dark'); else root.removeAttribute('data-theme');
        try { localStorage.setItem('owlspire_theme', dark ? 'dark' : 'light'); } catch (e) { /* abaikan */ }
        sync();
    });
});
