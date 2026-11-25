// Halaman detail karier: tinggi navbar untuk sidebar lengket, dan salin tautan.
if (document.querySelector('.kd-side')) {
    const header = document.querySelector('.site-header');
    const setTop = () => document.documentElement.style.setProperty('--kd-top', `${header ? header.offsetHeight : 0}px`);
    setTop();
    window.addEventListener('resize', setTop, { passive: true });
}

document.querySelectorAll('[data-kd-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const label = btn.querySelector('[data-kd-copy-label]');
        try {
            await navigator.clipboard.writeText(btn.dataset.kdCopy);
            if (label) label.textContent = 'Tersalin';
        } catch {
            if (label) label.textContent = 'Salin manual dari bilah alamat';
        }
        setTimeout(() => { if (label) label.textContent = 'Salin tautan'; }, 2200);
    });
});
