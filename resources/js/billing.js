// Perilaku halaman pembayaran dan promo: hitung mundur dan animasi spanduk promo, pratinjau harga diskon (admin), ringkasan pesanan.

const rp = (n) => 'Rp ' + Math.max(0, Math.round(n)).toLocaleString('id-ID');
const pad = (n) => String(n).padStart(2, '0');

// <time data-promo-countdown="epoch-ms">: hitung mundur tiap detik. Habis: sembunyikan spanduk promo atau tulis "berakhir".
const counters = [...document.querySelectorAll('[data-promo-countdown]')];
if (counters.length) {
    const tick = () => {
        const now = Date.now();
        counters.forEach((el) => {
            const left = Number(el.dataset.promoCountdown) - now;
            if (left <= 0) {
                el.textContent = el.dataset.promoDone || 'berakhir';
                el.closest('[data-promo-bar]')?.setAttribute('hidden', '');
                return;
            }
            const s = Math.floor(left / 1000);
            const d = Math.floor(s / 86400);
            const hms = `${pad(Math.floor((s % 86400) / 3600))}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
            el.textContent = d > 0 ? `${d} hari ${hms}` : hms;
        });
    };
    tick();
    setInterval(tick, 1000);
}

// Spanduk promo menempel tepat di bawah navbar: tinggi navbar (bisa berubah di HP) disalin ke --site-hdr.
const siteHdr = document.querySelector('.site-header');
if (siteHdr && document.querySelector('.pb-bar:not(.is-inset)')) {
    const sync = () => document.documentElement.style.setProperty('--site-hdr', `${siteHdr.offsetHeight}px`);
    sync();
    window.addEventListener('resize', sync);
    if ('ResizeObserver' in window) new ResizeObserver(sync).observe(siteHdr);
}

// Angka diskon di spanduk berhitung naik dari 0 saat tampil (dilewati bila pengguna memilih gerakan dikurangi).
document.querySelectorAll('[data-pb-count]').forEach((el) => {
    const full = el.textContent.trim();
    const m = /^\D*(\d+)\D*$/.exec(full);
    if (!m || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const target = Number(m[1]);
    if (target <= 0 || target > 100000) return;
    el.style.minWidth = `${el.offsetWidth}px`;
    const t0 = performance.now();
    const step = (t) => {
        const p = Math.min(1, (t - t0) / 1100);
        el.textContent = p < 1 ? full.replace(m[1], String(Math.round(target * (1 - (1 - p) ** 3)))) : full;
        if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
});

// Form paket admin: pratinjau harga akhir dari jenis dan besar diskon.
document.querySelectorAll('[data-disc-form]').forEach((form) => {
    const price = form.querySelector('[data-disc-price]');
    const type = form.querySelector('[name=discount_type], [name$="[discount_type]"]');
    const value = form.querySelector('[data-disc-value]');
    const box = form.querySelector('[data-disc-preview]');
    if (!price || !type || !value || !box) return;

    const from = box.querySelector('[data-disc-from]');
    const to = box.querySelector('[data-disc-to]');
    const save = box.querySelector('[data-disc-save]');
    const MIN = 1000;

    const render = () => {
        const p = Number(price.value) || 0;
        const v = Number(value.value) || 0;
        const kind = type.value;
        value.placeholder = kind === 'amount' ? 'mis. 10000' : 'mis. 30';
        value.max = kind === 'percent' ? 90 : '';
        let cut = kind === 'percent' ? Math.floor(p * Math.min(v, 100) / 100) : kind === 'amount' ? v : 0;
        cut = Math.max(0, Math.min(cut, p - MIN));
        box.hidden = !kind || !v || cut <= 0;
        if (box.hidden) return;
        from.textContent = rp(p);
        to.textContent = rp(p - cut);
        save.textContent = `hemat ${rp(cut)} (${Math.floor(cut / p * 100)}%)`;
    };
    ['input', 'change'].forEach((ev) => form.addEventListener(ev, render));
    render();
});

// Halaman Harga: kartu Premium (dropdown lama langganan) dan Tiket (dropdown simulasi + jumlah).
// Ringkasan pesanan dan tujuan kirim form (billing.plan / billing.buy) mengikuti pilihan.
const sum = document.querySelector('[data-sum]');
if (sum) {
    const q = (s) => sum.querySelector(s);
    const form = sum.closest('form');
    const plans = JSON.parse(form.dataset.plans || '{}');
    const unit = Number(form.dataset.ticketPrice) || 0;
    const radios = [...form.querySelectorAll('input[name=choice]')];
    const premOpts = form.querySelector('[data-premium-opts]');
    const tktOpts = form.querySelector('[data-tkt-opts]');
    const val = (box, name) => box && box.querySelector('[name=' + name + ']');
    const lbl = (box, name) => { const i = val(box, name); const dd = i && i.closest('[data-dd]'); return dd ? dd.querySelector('[data-dd-label]').textContent.trim() : ''; };
    const card = (s) => form.querySelector(s);
    const set = (el, t) => { if (el) el.textContent = t; };
    const render = () => {
        const on = radios.find((r) => r.checked);
        if (!on) return;
        const ticket = on.dataset.kind === 'ticket';
        if (premOpts) premOpts.hidden = ticket;
        if (tktOpts) tktOpts.hidden = !ticket;
        form.action = ticket ? form.dataset.actionTicket : form.dataset.actionPlan;
        q('[data-sum-sim-row]').hidden = !ticket;
        q('[data-sum-qty-row]').hidden = !ticket;

        const pl = plans[(val(premOpts, 'plan_id') || {}).value];
        // Kartu Premium selalu menampilkan paket yang sedang dipilih di dropdown.
        if (pl) {
            set(card('[data-card-price]'), rp(pl.total));
            set(card('[data-card-sub]'), pl.sub);
            const old = card('[data-card-old]'); const save = card('[data-card-save]');
            old.hidden = save.hidden = pl.discount <= 0;
            set(old, rp(pl.normal)); set(save, 'Hemat ' + pl.percent + '%');
        }

        let name, normal, cut = 0, total, label = 'Diskon';
        if (ticket) {
            const qty = Math.max(1, Number((val(tktOpts, 'quantity') || {}).value) || 1);
            name = 'Tiket simulasi'; normal = total = unit * qty;
            set(q('[data-sum-sim]'), lbl(tktOpts, 'exam_type_id'));
            set(q('[data-sum-qty]'), qty + ' tiket');
        } else if (pl) {
            name = pl.name; normal = pl.normal; cut = pl.discount; total = pl.total; label = pl.label;
        } else return;
        set(q('[data-sum-name]'), name);
        set(q('[data-sum-normal]'), rp(normal));
        set(q('[data-sum-total]'), rp(total));
        q('[data-sum-cut-row]').hidden = cut <= 0;
        set(q('[data-sum-cut]'), '-' + rp(cut));
        set(q('[data-sum-label]'), label);
    };
    radios.forEach((r) => r.addEventListener('change', render));
    // Dropdown kustom memicu change pada input tersembunyinya; label baru terpasang sesudahnya.
    form.querySelectorAll('[data-dd-value]').forEach((i) => i.addEventListener('change', () => setTimeout(render, 0)));
    render();
}

// Tombol Salin di kartu sandi acak: <button data-copy="#id-input">. Cadangan execCommand untuk browser tanpa clipboard API (mis. http).
document.querySelectorAll('[data-copy]').forEach((btn) => {
    const label = btn.querySelector('span');
    const idle = label ? label.textContent : '';
    btn.addEventListener('click', async () => {
        const input = document.querySelector(btn.dataset.copy);
        if (!input) return;
        let ok = false;
        try {
            await navigator.clipboard.writeText(input.value);
            ok = true;
        } catch (e) {
            input.focus();
            input.select();
            try { ok = document.execCommand('copy'); } catch (e2) { ok = false; }
        }
        if (label) label.textContent = ok ? 'Tersalin' : 'Salin manual';
        btn.classList.toggle('is-done', ok);
        setTimeout(() => { if (label) label.textContent = idle; btn.classList.remove('is-done'); }, 2000);
    });
});
