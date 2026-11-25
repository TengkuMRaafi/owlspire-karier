import './ui.js';
import './rapi.js';
import './billing.js';
import './feedback.js';
import './unsaved.js';
import './offline.js';

// Halaman sensitif (login, ujian, tagihan) yang dipulihkan dari cache Back/Forward dimuat ulang agar status sesi dicek server.
window.addEventListener('pageshow', (e) => {
    if (e.persisted && document.querySelector('meta[name="app-secure"]')) location.reload();
});
import './panel.js';
import './settings.js';
import './career-show.js';
import './cv.js';
import './quota-lock.js';

// Tombol "Lihat/Sembunyikan" pada kolom kata sandi.
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    const input = document.getElementById(btn.dataset.togglePassword);
    if (!input) return;
    btn.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Sembunyikan' : 'Lihat';
        btn.setAttribute('aria-pressed', String(show));
    });
});

// Elemen [data-reveal] muncul halus saat masuk layar.
const revealEls = document.querySelectorAll('[data-reveal]');
if (revealEls.length) {
    if (!('IntersectionObserver' in window)) {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    } else {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach((el) => io.observe(el));
    }
}

// Konfirmasi aksi berbahaya (<form data-confirm>) ada di feedback.js.

// Filter cepat di halaman hasil ujian: <button data-filter="salah"> + <li data-status="salah">
document.querySelectorAll('[data-filter]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const f = btn.dataset.filter;
        document.querySelectorAll('[data-filter]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
        document.querySelectorAll('[data-status]').forEach((li) => {
            li.hidden = f !== 'semua' && li.dataset.status !== f;
        });
    });
});

// QRIS dinamis: gambar QR dari string QRIS, dimuat hanya di halaman tagihan.
const qrCanvas = document.querySelector('canvas[data-qr]');
if (qrCanvas) {
    import('qrcode').then(({ default: QRCode }) => {
        QRCode.toCanvas(qrCanvas, qrCanvas.dataset.qr, { width: 280, margin: 1 });
    });
}

// Unduh kartu QRIS sebagai PNG bergaya Owlspire; digambar di browser, tanpa server.
const dlBtn = document.querySelector('[data-qr-download]');
if (dlBtn) {
    const rr = (x, a, b, w, h, r) => {
        x.beginPath(); x.moveTo(a + r, b); x.arcTo(a + w, b, a + w, b + h, r); x.arcTo(a + w, b + h, a, b + h, r);
        x.arcTo(a, b + h, a, b, r); x.arcTo(a, b, a + w, b, r); x.closePath();
    };
    const loadImg = (src, cors) => new Promise((ok, no) => {
        const i = new Image(); if (cors) i.crossOrigin = 'anonymous';
        i.onload = () => ok(i); i.onerror = no; i.src = src;
    });

    dlBtn.addEventListener('click', async () => {
        const d = dlBtn.dataset;
        const label = dlBtn.querySelector('span');
        const idle = label.textContent;
        label.textContent = 'Menyiapkan...';
        dlBtn.disabled = true;
        try {
            const W = 1080, H = 1720, CX = W / 2;
            const c = document.createElement('canvas'); c.width = W; c.height = H;
            const x = c.getContext('2d');
            await Promise.all([document.fonts.load('600 64px Poppins'), document.fonts.load('700 32px Poppins'), document.fonts.load('700 28px "Plus Jakarta Sans"')].map((p) => p.catch(() => {})));

            // QR resolusi tinggi (dari string QRIS; bila hanya ada URL gambar, dimuat dari sana)
            const qr = document.createElement('canvas');
            if (d.qrString) {
                const { default: QRCode } = await import('qrcode');
                await QRCode.toCanvas(qr, d.qrString, { width: 640, margin: 1 });
            } else {
                const img = await loadImg(d.qrUrl, true);
                qr.width = qr.height = 640;
                qr.getContext('2d').drawImage(img, 0, 0, 640, 640);
            }

            const sans = '"Plus Jakarta Sans", system-ui, sans-serif', disp = 'Poppins, "Plus Jakarta Sans", sans-serif';
            const text = (t, px, py, font, color, align = 'center') => { x.font = font; x.fillStyle = color; x.textAlign = align; x.fillText(t, px, py); };

            // Latar halaman + header gelap
            x.fillStyle = '#f3f5f8'; x.fillRect(0, 0, W, H);
            const g = x.createLinearGradient(0, 0, W, 430);
            g.addColorStop(0, '#1a1410'); g.addColorStop(.55, '#1b2638'); g.addColorStop(1, '#3a1a07');
            x.fillStyle = g; x.fillRect(0, 0, W, 430);
            const glow = x.createRadialGradient(W * .85, 140, 10, W * .85, 140, 520);
            glow.addColorStop(0, 'rgba(79,130,230,.42)'); glow.addColorStop(1, 'rgba(79,130,230,0)');
            x.fillStyle = glow; x.fillRect(0, 0, W, 430);

            // Logo + nama
            try { x.drawImage(await loadImg(d.logo), 70, 62, 66, 77); } catch (e) { /* logo opsional */ }
            x.textBaseline = 'alphabetic';
            text('Owl', 160, 120, `700 56px ${disp}`, '#ffffff', 'left');
            const owlW = x.measureText('Owl').width;
            text('spire', 160 + owlW, 120, `700 56px ${disp}`, '#7fa8e8', 'left');
            text('TAGIHAN PEMBAYARAN', W - 70, 112, `700 24px ${sans}`, '#a9c4f2', 'right');

            text(d.name, 70, 262, `600 58px ${disp}`, '#ffffff', 'left');
            text(`No. ${d.invoice}`, 70, 316, `500 28px ${sans}`, '#c5cfde', 'left');

            // Kartu putih
            x.save(); x.shadowColor = 'rgba(27,38,56,.35)'; x.shadowBlur = 50; x.shadowOffsetY = 24;
            rr(x, 60, 370, W - 120, 1250, 48); x.fillStyle = '#ffffff'; x.fill(); x.restore();
            const top = x.createLinearGradient(0, 370, 0, 800);
            top.addColorStop(0, '#eef4fd'); top.addColorStop(1, '#ffffff');
            rr(x, 60, 370, W - 120, 1250, 48); x.fillStyle = top; x.fill();

            text('TOTAL BAYAR', CX, 450, `800 26px ${sans}`, '#667794');
            text(d.amount, CX, 560, `600 100px ${disp}`, '#1b2638');
            const pill = `Bayar sebelum ${d.expiry}`;
            x.font = `600 26px ${sans}`;
            const pw = x.measureText(pill).width + 64;
            rr(x, CX - pw / 2, 596, pw, 62, 31); x.fillStyle = '#dce8fb'; x.fill();
            text(pill, CX, 636, `600 26px ${sans}`, '#254690');

            // Bingkai QR + sudut biru
            const fx = 190, fy = 710, fs = 700;
            x.save(); x.shadowColor = 'rgba(27,38,56,.3)'; x.shadowBlur = 36; x.shadowOffsetY = 14;
            rr(x, fx, fy, fs, fs, 40); x.fillStyle = '#ffffff'; x.fill(); x.restore();
            rr(x, fx, fy, fs, fs, 40); x.strokeStyle = '#e2e6ec'; x.lineWidth = 2; x.stroke();
            x.imageSmoothingEnabled = false;
            x.drawImage(qr, fx + 30, fy + 30, 640, 640);
            x.strokeStyle = '#3b6cd4'; x.lineWidth = 8; x.lineCap = 'round';
            const L = 64, m = 16;
            [[fx + m, fy + m, 1, 1], [fx + fs - m, fy + m, -1, 1], [fx + m, fy + fs - m, 1, -1], [fx + fs - m, fy + fs - m, -1, -1]].forEach(([a, b, sx, sy]) => {
                x.beginPath(); x.moveTo(a + sx * L, b); x.lineTo(a, b); x.lineTo(a, b + sy * L); x.stroke();
            });

            // Keterangan bawah
            rr(x, CX - 250, 1470, 92, 46, 10); x.fillStyle = '#1b2638'; x.fill();
            text('QRIS', CX - 204, 1503, `800 24px ${sans}`, '#ffffff');
            text('Semua bank dan e-wallet', CX - 140, 1503, `500 28px ${sans}`, '#4a5a73', 'left');
            text(`Bayar tepat ${d.amount}`, CX, 1566, `800 34px ${sans}`, '#1b2638');
            text('Jangan lebih atau kurang agar terbaca otomatis', CX, 1602, `500 24px ${sans}`, '#667794');
            text('Diproses aman oleh Midtrans  |  owlspire', CX, 1675, `600 24px ${sans}`, '#667794');

            const blob = await new Promise((ok) => c.toBlob(ok, 'image/png'));
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = `owlspire-qris-${d.invoice}.png`;
            document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 4000);
        } catch (e) {
            // Gambar QR dari luar bisa diblokir browser; buka gambarnya agar tetap bisa disimpan manual.
            if (dlBtn.dataset.qrUrl) window.open(dlBtn.dataset.qrUrl, '_blank', 'noopener');
        } finally {
            label.textContent = idle;
            dlBtn.disabled = false;
        }
    });
}

// Hitung mundur batas bayar di halaman tagihan: <b data-countdown="ISO8601">
document.querySelectorAll('[data-countdown]').forEach((el) => {
    const end = new Date(el.dataset.countdown).getTime();
    if (Number.isNaN(end)) return;
    const box = el.closest('.iv-timer');
    const pad = (n) => String(n).padStart(2, '0');
    const tick = () => {
        const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        el.textContent = Math.floor(s / 3600) > 0
            ? `${Math.floor(s / 3600)}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`
            : `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;
        if (box) box.classList.toggle('is-low', s <= 300);
        if (s <= 0) { setTimeout(() => window.location.reload(), 1500); return; }
        setTimeout(tick, 1000);
    };
    tick();
});

// Cek status tagihan otomatis (tiap 3 detik); muat ulang halaman begitu lunas/kedaluwarsa.
// Tab yang ditinggal untuk membayar di aplikasi bank/e-wallet sering dibekukan browser, jadi cek lagi seketika saat tab dibuka kembali.
const poll = document.querySelector('[data-poll-status]');
if (poll) {
    const note = poll.querySelector('[data-poll-note]');
    const txt = poll.querySelector('[data-poll-text]');
    let timer = null, busy = false;
    const check = async () => {
        if (busy) return;
        busy = true;
        clearTimeout(timer);
        try {
            const r = await fetch(poll.dataset.pollStatus, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            const j = await r.json();
            if (j.status && j.status !== 'pending') return window.location.reload();
            if (txt) txt.textContent = j.attempt_failed ? 'Pembayaran sedang diproses' : 'Menunggu pembayaran';
            if (note) note.textContent = j.gateway_ok === false
                ? 'Belum bisa memeriksa ke Midtrans, mencoba lagi...'
                : (j.attempt_failed
                    ? 'Pembayaran terakhir belum berhasil. Kamu masih bisa memindai lagi selama QR berlaku; tagihan ditutup otomatis saat waktu habis.'
                    : 'Terakhir dicek ' + (j.checked_at || '') + '. Belum ada pembayaran masuk.');
        } catch (e) {
            if (note) note.textContent = 'Koneksi terputus, mencoba lagi...';
        }
        busy = false;
        timer = setTimeout(check, 3000);
    };
    timer = setTimeout(check, 1500);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
    window.addEventListener('focus', check);
    window.addEventListener('pageshow', check);
}

// Hitung naik angka di hero: <b data-count="78" data-suffix="%">
document.querySelectorAll('[data-count]').forEach((el) => {
    const to = Number(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = to + suffix; return; }
    const t0 = performance.now();
    const tick = (now) => {
        const k = Math.min(1, (now - t0) / 1400);
        el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))) + suffix;
        if (k < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
});

// Roadmap "Jalur Owlspire": jalur tergambar dan tokoh berjalan mengikuti scroll.
(() => {
    const root = document.querySelector('[data-journey]');
    if (!root) return;
    const NS = 'http://www.w3.org/2000/svg';
    const steps = JSON.parse(root.dataset.steps);
    const q = (s) => root.querySelector(s);
    const svg = q('[data-journey-svg]'), line = q('[data-line]'), trail = q('[data-trail]');
    const gSt = q('[data-stations]'), walker = q('[data-walker]'), conf = q('[data-conf]');
    const card = q('[data-journey-card]'), dots = q('[data-journey-dots]'), pin = q('.journey-pin');
    const layers = root.querySelectorAll('[data-layer]');
    const wide = 'M70 360 C 210 360, 230 120, 390 150 S 560 390, 650 290 S 800 90, 940 120';
    const tall = 'M70 50 C 70 140, 330 120, 330 220 S 70 310, 70 410 S 330 500, 330 600 S 250 660, 200 670';
    const mq = window.matchMedia('(max-width: 700px)');
    const lerp = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
    let L = 0, nodes = [], last = -1;

    const build = () => {
        const d = mq.matches ? tall : wide;
        svg.setAttribute('viewBox', mq.matches ? '0 0 400 720' : '0 0 1000 460');
        line.setAttribute('d', d); trail.setAttribute('d', d);
        L = line.getTotalLength();
        line.style.strokeDasharray = L;
        gSt.innerHTML = ''; dots.innerHTML = ''; nodes = []; last = -1;
        steps.forEach((s, i) => {
            const p = line.getPointAtLength((L * i) / (steps.length - 1));
            const g = document.createElementNS(NS, 'g');
            g.setAttribute('class', 'j-st'); g.setAttribute('tabindex', '0'); g.setAttribute('role', 'button'); g.setAttribute('aria-label', 'Langkah ' + (i + 1) + ': ' + s.title);
            g.innerHTML = `<circle cx="${p.x}" cy="${p.y}" r="26"/><text class="num" x="${p.x}" y="${p.y}">${i + 1}</text><use class="ico" href="#i-${s.icon}" x="${p.x - 14}" y="${p.y - 14}" width="28" height="28"/>`;
            const go = () => window.scrollTo({ top: root.offsetTop + (root.offsetHeight - innerHeight) * (i / (steps.length - 1) + 0.001), behavior: 'smooth' });
            g.addEventListener('click', go);
            g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
            gSt.appendChild(g); nodes.push(g); dots.appendChild(document.createElement('i'));
        });
        const end = line.getPointAtLength(L);
        conf.innerHTML = ''; conf.setAttribute('transform', `translate(${end.x} ${end.y})`);
        for (let k = 0; k < 14; k++) {
            const c = document.createElementNS(NS, 'circle'), a = (k / 14) * 6.28;
            c.setAttribute('r', 5); c.setAttribute('fill', k % 2 ? '#7fa8e8' : '#fff');
            c.style.setProperty('--x', Math.cos(a) * (60 + k * 3) + 'px'); c.style.setProperty('--y', Math.sin(a) * (60 + k * 3) - 30 + 'px');
            conf.appendChild(c);
        }
        update();
    };

    const update = () => {
        const hdr = document.querySelector('header.sticky');
        if (hdr) root.style.setProperty('--hdr', hdr.offsetHeight + 'px');
        const h = root.offsetHeight - pin.offsetHeight;
        const p = Math.max(0, Math.min(1, -root.getBoundingClientRect().top / h));
        line.style.strokeDashoffset = L * (1 - p);
        const pt = line.getPointAtLength(L * p);
        walker.setAttribute('transform', `translate(${pt.x} ${pt.y - Math.abs(Math.sin((p * L) / 9)) * 5})`);
        layers[0].style.transform = `translateX(${-p * 90}px)`; layers[1].style.transform = `translateX(${-p * 180}px)`;
        pin.style.background = `linear-gradient(180deg, rgb(${lerp([20, 24, 34], [30, 58, 84], p)}), rgb(${lerp([110, 60, 30], [70, 130, 170], p)}))`;
        const idx = Math.min(steps.length - 1, Math.floor(p * (steps.length - 1) + 0.002));
        if (idx === last) return;
        last = idx;
        nodes.forEach((n, i) => { n.classList.toggle('on', i <= idx); n.classList.toggle('cur', i === idx); });
        [...dots.children].forEach((d, i) => d.classList.toggle('on', i <= idx));
        const s = steps[idx];
        card.innerHTML = `<div><h3></h3><p></p></div><a class="btn-primary"></a>`;
        card.querySelector('h3').textContent = `${idx + 1}. ${s.title}` + (s.premium ? ' (Premium)' : '');
        card.querySelector('p').textContent = s.desc;
        const a = card.querySelector('a'); a.textContent = s.cta; a.href = s.url;
        pin.classList.toggle('win', idx === steps.length - 1);
    };

    addEventListener('scroll', update, { passive: true });
    addEventListener('resize', update);
    mq.addEventListener('change', build);
    build();
})();

// Hemat CPU/baterai: bagian landing yang sudah di luar layar menjeda animasi CSS-nya (kelas .is-off, aturannya di base/lite.css).
// Roadmap (.rm) mengatur kelas ini sendiri karena juga menghentikan loop gambarnya.
(() => {
    if (!('IntersectionObserver' in window)) return;
    const targets = document.querySelectorAll('.hx, .sb, .mq, .duo, .dr, .pk, .fq, #pilih-simulasi');
    if (!targets.length) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => e.target.classList.toggle('is-off', !e.isIntersecting));
    }, { rootMargin: '160px 0px' });
    targets.forEach((t) => io.observe(t));
})();

// Pagination di HP: ketik nomor halaman lalu "Buka". Daftar yang dimuat langsung (data-live) tidak reload halaman.
document.addEventListener('submit', (e) => {
    const f = e.target instanceof Element ? e.target.closest('form[data-pg-jump]') : null;
    if (!f) return;
    e.preventDefault();
    const input = f.querySelector('input');
    const max = Number(input.max) || 1;
    let n = parseInt(input.value, 10);
    if (!n || n < 1) n = 1;
    if (n > max) n = max;
    input.value = n;
    const u = new URL(f.dataset.url, window.location.origin);
    u.searchParams.set('page', String(n));
    if (f.hasAttribute('data-live')) {
        const a = document.createElement('a');
        a.href = u.href; a.className = 'ui-pg'; a.hidden = true; a.setAttribute('data-live-link', '');
        f.closest('nav').appendChild(a);
        a.click();
        a.remove();
    } else {
        window.location.href = u.href;
    }
});
