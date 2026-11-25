// Kunci jatah harian (<x-quota-lock>): hitung mundur ke isi ulang, kunci langsung tanpa muat ulang, dan buka sendiri saat jatah kembali.
const DAY_MS = 24 * 3600 * 1000;
const pad = (n) => String(n).padStart(2, '0');

function localTime(date) {
    try {
        return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace(':', '.');
    } catch (e) {
        return '';
    }
}

function setup(root) {
    const q = (sel) => root.querySelector(sel);
    const veil = q('[data-ql-veil]');
    const body = q('[data-ql-body]');
    const card = q('[data-ql-card]');
    if (!veil || !body) return;

    const resetAt = Date.parse(root.dataset.reset);
    if (Number.isNaN(resetAt)) return;
    // Jam perangkat bisa meleset: selisih dengan jam server dipakai supaya hitung mundur tetap tepat.
    const skew = Date.parse(root.dataset.now) - Date.now() || 0;
    const now = () => Date.now() + skew;

    const ring = q('[data-ql-ring]');
    const circ = 276.46;
    const [h, m, s] = ['[data-ql-h]', '[data-ql-m]', '[data-ql-s]'].map(q);
    let timer = null;
    let reloaded = false;

    const at = q('[data-ql-at]');
    if (at && localTime(new Date(resetAt))) at.textContent = localTime(new Date(resetAt));

    function ready() {
        veil.classList.add('is-ready');
        q('[data-ql-ready]').hidden = false;
        q('[data-ql-reload]').hidden = false;
        clearInterval(timer);
        timer = null;
        if (!reloaded) {
            reloaded = true;
            setTimeout(() => location.reload(), 1400);
        }
    }

    function tick() {
        const left = resetAt - now();
        if (left <= 0) {
            if (h) h.textContent = m.textContent = s.textContent = '00';
            if (ring) ring.style.strokeDashoffset = '0';
            ready();
            return;
        }
        const sec = Math.floor(left / 1000);
        h.textContent = pad(Math.floor(sec / 3600));
        m.textContent = pad(Math.floor((sec % 3600) / 60));
        s.textContent = pad(sec % 60);
        // Cincin terisi seiring waktu mendekati isi ulang.
        if (ring) ring.style.strokeDashoffset = String(circ * (left / DAY_MS));
    }

    function start() {
        if (timer) return;
        tick();
        timer = setInterval(tick, 1000);
    }

    function lock(detail) {
        detail = detail || {};
        if (detail.used != null) q('[data-ql-used]').textContent = detail.used;
        if (detail.limit != null) q('[data-ql-limit]').textContent = detail.limit;
        root.classList.add('is-locked');
        body.setAttribute('inert', '');
        body.setAttribute('aria-hidden', 'true');
        veil.hidden = false;
        start();
        // Fokus pindah ke kartu supaya pembaca layar dan papan ketik langsung tahu bagian ini terkunci.
        if (card) card.focus({ preventScroll: true });
    }

    function unlock() {
        root.classList.remove('is-locked');
        body.removeAttribute('inert');
        body.removeAttribute('aria-hidden');
        veil.hidden = true;
        clearInterval(timer);
        timer = null;
    }

    root.addEventListener('quota:lock', (e) => lock(e.detail));
    root.addEventListener('quota:unlock', unlock);
    q('[data-ql-reload]')?.addEventListener('click', () => location.reload());
    document.addEventListener('visibilitychange', () => { if (!document.hidden && timer) tick(); });

    if (!veil.hidden) start();
}

document.querySelectorAll('[data-quota-lock]').forEach(setup);
