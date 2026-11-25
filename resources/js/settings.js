// Pengaturan (akun dan admin): kolom grup di atas dengan dropdown; satu bagian tampil sekaligus, dipilih lewat menu atau #hash.
const root = document.querySelector('[data-set]');

if (root?.classList.contains('is-tabs')) {
    const tabs = [...root.querySelectorAll('[data-set-tab]')];
    const panels = [...root.querySelectorAll('.ac-set-sec')];
    const cols = [...root.querySelectorAll('[data-set-col]')];

    const closeAll = (except = null) => cols.forEach((c) => {
        if (c === except) return;
        c.classList.remove('is-open');
        c.querySelector('[data-set-colbtn]')?.setAttribute('aria-expanded', 'false');
        c.querySelector('.ac-set-menu')?.setAttribute('hidden', '');
    });

    cols.forEach((c) => {
        const btn = c.querySelector('[data-set-colbtn]');
        const menu = c.querySelector('.ac-set-menu');
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = menu.hasAttribute('hidden');
            closeAll(c);
            menu.toggleAttribute('hidden', !open);
            c.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', (e) => { if (!e.target.closest('[data-set-col]')) closeAll(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });

    const show = (id) => {
        const panel = panels.find((p) => p.id === id) ?? panels[0];
        panels.forEach((p) => p.classList.toggle('is-on', p === panel));
        tabs.forEach((t) => {
            if (t.dataset.setTab === panel.id) t.setAttribute('aria-current', 'page'); else t.removeAttribute('aria-current');
        });
        cols.forEach((c) => {
            const active = c.querySelector(`[data-set-tab="${panel.id}"]`);
            c.classList.toggle('is-active', !!active);
            const cur = c.querySelector('[data-set-cur]');
            if (cur) cur.textContent = active ? (active.querySelector('span')?.textContent ?? '') : cur.dataset.setDef;
        });
        root.querySelectorAll('.is-single').forEach((c) => c.classList.toggle('is-active', !!c.querySelector(`[data-set-tab="${panel.id}"]`)));
    };

    const fromHash = () => { try { return decodeURIComponent(location.hash.slice(1)); } catch (e) { return ''; } };

    tabs.forEach((t) => t.addEventListener('click', (e) => {
        e.preventDefault();
        history.replaceState(null, '', '#' + t.dataset.setTab);
        closeAll();
        show(t.dataset.setTab);
    }));
    window.addEventListener('hashchange', () => show(fromHash()));
    show(fromHash());
}
