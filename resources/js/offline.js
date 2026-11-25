// Banner koneksi: muncul saat internet pengguna putus, berganti kabar baik saat tersambung lagi.
(() => {
    const art = `<svg viewBox="0 0 64 56" focusable="false" aria-hidden="true"><g transform="translate(2 0) scale(.4)"><g class="of-owl"><path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136 14 114Q8 108 8 100V72Q8 60 18 54L4 22 36 32Z" fill="#4a7fdc"/><path d="M60 4 84 32 60 52 36 32Z" fill="#3b6cd4"/><path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136Z" fill="#a9c4f2" opacity=".55"/><path d="M4 22 36 32 22 52Q14 54 14 60Z" fill="#2f58b3"/><path d="M116 22 84 32 98 52Q106 54 106 60Z" fill="#2f58b3"/><path d="M60 46V16M50 26 60 14 70 26" fill="none" stroke="#2f58b3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 56Q36 52 54 64V70Q34 62 14 62Z" fill="#2f58b3"/><path d="M106 56Q84 52 66 64V70Q86 62 106 62Z" fill="#2f58b3"/><g class="of-sad"><circle cx="36" cy="76" r="17" fill="#fff"/><circle cx="84" cy="76" r="17" fill="#fff"/><circle cx="38" cy="80" r="7" fill="#1f2a3d"/><circle cx="82" cy="80" r="7" fill="#1f2a3d"/><path d="M18 68 52 55M102 68 68 55" fill="none" stroke="#1f3a7a" stroke-width="4.6" stroke-linecap="round"/></g><g class="of-happy"><circle cx="36" cy="76" r="17" fill="#fff"/><circle cx="84" cy="76" r="17" fill="#fff"/><circle cx="36" cy="76" r="10.5" fill="#f0b429"/><circle cx="84" cy="76" r="10.5" fill="#f0b429"/><circle cx="36" cy="76" r="5.8" fill="#1f2a3d"/><circle cx="84" cy="76" r="5.8" fill="#1f2a3d"/></g><path d="M54 88H66L60 100Z" fill="#f5b942"/><path d="M48 112 60 102 72 112M48 120 60 110 72 120" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></g></g><g class="of-wifi" fill="none" stroke="#7fa8e8" stroke-width="3" stroke-linecap="round"><path class="of-a1" d="M46 20Q56 12 64 20"/><path class="of-a2" d="M49 28Q56 22 62 28"/><circle class="of-a3" cx="55" cy="36" r="1.6" fill="#7fa8e8"/><path class="of-x" d="M44 12 62 40" stroke="#ff6b5e" stroke-width="3.4"/></g></svg>`;
    let el = null;
    let hideTimer = 0;

    const build = () => {
        el = document.createElement('div');
        el.className = 'of';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.innerHTML = `<span class="of-art" aria-hidden="true">${art}</span><span class="of-t"><b></b><span></span></span>`;
        document.body.appendChild(el);
    };

    const show = (back) => {
        if (!el) build();
        clearTimeout(hideTimer);
        el.classList.toggle('is-back', back);
        el.querySelector('b').textContent = back ? 'Tersambung kembali' : 'Koneksimu terputus';
        el.querySelector('.of-t span').textContent = back ? 'Silakan lanjutkan.' : 'Tenang, kami tunggu sampai internetmu tersambung lagi.';
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-on')));
        if (back) hideTimer = setTimeout(() => el.classList.remove('is-on'), 3200);
    };

    window.addEventListener('offline', () => show(false));
    window.addEventListener('online', () => { if (el && el.classList.contains('is-on')) show(true); });
    if (navigator.onLine === false) show(false);
})();
