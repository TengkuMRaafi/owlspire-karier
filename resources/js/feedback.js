// Dialog konfirmasi dan toast Owlspire; menggantikan confirm() dan alert() bawaan browser.
const ICONS = {
    danger: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
    logout: '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
    warning: '<path d="M12 8v5M12 16.5v.01"/>',
    info: '<path d="M12 11v5M12 8v.01"/>',
    success: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    error: '<path d="M9 9l6 6m0-6-6 6"/>',
};
const svg = (k) => `<svg viewBox="0 0 24 24" aria-hidden="true">${ICONS[k] || ICONS.info}</svg>`;
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/* ---------- Dialog konfirmasi ---------- */
let dlg;
function build() {
    dlg = document.createElement('dialog');
    dlg.className = 'fb-dlg';
    dlg.setAttribute('aria-labelledby', 'fb-dlg-t');
    dlg.innerHTML = '<div class="fb-dlg-in"><span class="fb-dlg-ic" aria-hidden="true"></span><h2 id="fb-dlg-t" class="font-display"></h2><p class="fb-dlg-p"></p>'
        + '<div class="fb-dlg-act"><button type="button" class="fb-btn is-main" data-fb-ok></button><button type="button" class="fb-btn" data-fb-no></button></div></div>';
    document.body.appendChild(dlg);
}

// Tampilkan konfirmasi; hasilnya Promise<boolean>.
export function confirmBox({ title = 'Yakin?', message = '', ok = 'Ya, lanjutkan', cancel = 'Batal', tone = 'warning' } = {}) {
    if (!dlg) build();
    dlg.dataset.tone = tone;
    dlg.querySelector('.fb-dlg-ic').innerHTML = svg(tone);
    dlg.querySelector('h2').textContent = title;
    const p = dlg.querySelector('.fb-dlg-p');
    p.textContent = message; p.hidden = !message;
    const okBtn = dlg.querySelector('[data-fb-ok]'), noBtn = dlg.querySelector('[data-fb-no]');
    okBtn.textContent = ok; noBtn.textContent = cancel;

    return new Promise((resolve) => {
        const done = (v) => { dlg.removeEventListener('close', onClose); okBtn.onclick = noBtn.onclick = null; if (dlg.open) dlg.close(); resolve(v); };
        const onClose = () => done(false);
        okBtn.onclick = () => done(true);
        noBtn.onclick = () => done(false);
        dlg.addEventListener('close', onClose);
        dlg.onclick = (e) => { if (e.target === dlg) done(false); }; // klik latar = batal
        dlg.showModal();
        noBtn.focus();
    });
}

// <form data-confirm="Pesan" data-confirm-title data-confirm-ok data-confirm-tone="danger|logout|warning|info">
const DANGER = /hapus|tolak|permanen|reset|bersihkan/i;
document.addEventListener('submit', async (e) => {
    const form = e.target.closest?.('form[data-confirm]');
    if (!form || form.dataset.confirmed === '1') return;
    e.preventDefault();
    const d = form.dataset, msg = d.confirm;
    const danger = DANGER.test(msg);
    const ok = await confirmBox({
        title: d.confirmTitle || (danger ? 'Hapus data ini?' : 'Lanjutkan tindakan ini?'),
        message: msg,
        ok: d.confirmOk || (danger ? 'Ya, hapus' : 'Ya, lanjutkan'),
        cancel: d.confirmCancel || 'Batal',
        tone: d.confirmTone || (danger ? 'danger' : 'warning'),
    });
    if (!ok) return;
    form.dataset.confirmed = '1';
    form.requestSubmit(e.submitter || undefined);
});

// Kembali lewat tombol Back: izinkan konfirmasi lagi.
window.addEventListener('pageshow', () => document.querySelectorAll('form[data-confirmed]').forEach((f) => delete f.dataset.confirmed));

/* ---------- Toast ---------- */
let stack;
function ensureStack() {
    stack = document.querySelector('[data-toasts]');
    if (!stack) { stack = document.createElement('div'); stack.className = 'fb-toasts'; stack.setAttribute('data-toasts', ''); document.body.appendChild(stack); }
    return stack;
}
function arm(el) {
    const ms = Number(el.dataset.toastMs) || 6000;
    let timer, left = ms, start;
    const bar = el.querySelector('.fb-toast-bar');
    const close = () => { clearTimeout(timer); el.classList.add('is-leaving'); setTimeout(() => el.remove(), 260); };
    const run = () => { start = Date.now(); if (bar) bar.style.animation = `fb-bar ${left}ms linear forwards`; timer = setTimeout(close, left); };
    const pause = () => { clearTimeout(timer); left -= Date.now() - start; if (bar) bar.style.animationPlayState = 'paused'; };
    const resume = () => { if (bar) bar.style.animationPlayState = 'running'; run(); };
    el.querySelector('[data-toast-x]')?.addEventListener('click', close);
    el.addEventListener('mouseenter', pause); el.addEventListener('mouseleave', resume);
    if (el.dataset.toastSticky === undefined) run(); else if (bar) bar.remove();
}
document.querySelectorAll('.fb-toast').forEach(arm);

// toast('Tersimpan', 'success' | 'error' | 'warning' | 'info')
export function toast(message, type = 'info', { title = '', sticky = false } = {}) {
    const el = document.createElement('div');
    el.className = `fb-toast is-${type}`;
    el.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');
    if (sticky) el.dataset.toastSticky = '';
    el.innerHTML = `<span class="fb-toast-ic">${svg(type)}</span><div class="fb-toast-body">${title ? `<b>${esc(title)}</b>` : ''}<p>${esc(message)}</p></div>`
        + '<button type="button" class="fb-toast-x" data-toast-x aria-label="Tutup pesan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button><i class="fb-toast-bar"></i>';
    ensureStack().appendChild(el);
    arm(el);
    return el;
}

// Jaring pengaman: alert() bawaan browser dialihkan ke toast.
window.alert = (m) => { toast(String(m), 'info'); };
window.OwlUI = { confirm: confirmBox, toast };
