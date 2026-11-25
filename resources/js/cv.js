// Analisis CV dan Perbaikan CV: nama berkas terpilih, seret-lepas, panel "sedang diproses" yang menggantikan
// formulir saat dikirim, dan tombol salin di pratinjau bertanda panah.

/** Sembunyikan formulir dan hasil lama, tampilkan panel proses, lalu centang langkahnya satu per satu. */
function startLoading(kind, role) {
    const panels = [...document.querySelectorAll('[data-cv-loading]')];
    const panel = panels.find((p) => p.dataset.kind === kind) || panels[0];
    if (!panel) return;

    document.querySelectorAll('[data-cv-wrap], [data-cv-result]').forEach((el) => { el.hidden = true; });

    const title = panel.querySelector('[data-cvl-title]');
    if (title && title.dataset.tpl && role) title.textContent = title.dataset.tpl.replace('{role}', role);

    const steps = [...panel.querySelectorAll('[data-cvl-step]')];
    steps.forEach((s) => s.classList.remove('is-on', 'is-done'));
    if (steps[0]) steps[0].classList.add('is-on');
    let i = 0;
    window.clearInterval(panel._t);
    panel._t = window.setInterval(() => {
        if (i >= steps.length - 1) { window.clearInterval(panel._t); return; }
        steps[i].classList.replace('is-on', 'is-done');
        i += 1;
        steps[i].classList.add('is-on');
    }, 1800);

    panel.hidden = false;
    panel.scrollIntoView({ block: 'start', behavior: 'smooth' });
}

/** Tombol Kembali di browser memulihkan halaman dari cache: kembalikan formulir, sembunyikan panel proses. */
window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('[data-cv-loading]').forEach((p) => { p.hidden = true; window.clearInterval(p._t); });
    document.querySelectorAll('[data-cv-wrap], [data-cv-result]').forEach((el) => { el.hidden = false; });
    document.querySelectorAll('[data-cv-submit]').forEach((b) => { b.disabled = false; });
});

// Salin contoh tulisan ulang dari catatan di pratinjau.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cv-copy]');
    if (!btn) return;
    const pre = btn.closest('.cvp-rw')?.querySelector('.cvp-rw-t');
    if (!pre || !navigator.clipboard?.writeText) return;
    navigator.clipboard.writeText(pre.textContent).then(() => {
        const old = btn.textContent;
        btn.textContent = 'Tersalin';
        setTimeout(() => { btn.textContent = old; }, 1600);
    }, () => {});
});

// --- Analisis CV ---
const form = document.querySelector('[data-cv-form]');
if (form) {
    const drop = form.querySelector('[data-cv-drop]');
    const input = form.querySelector('[data-cv-input]');
    const chip = form.querySelector('[data-cv-file]');
    const name = form.querySelector('[data-cv-name]');
    const size = form.querySelector('[data-cv-size]');
    const clear = form.querySelector('[data-cv-clear]');
    const text = form.querySelector('[data-cv-text]');

    const show = () => {
        const f = input.files?.[0];
        chip.hidden = !f;
        if (f) {
            name.textContent = f.name;
            size.textContent = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB';
        }
    };

    input.addEventListener('change', show);
    clear.addEventListener('click', () => { input.value = ''; show(); });

    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove('is-over')));

    form.addEventListener('submit', () => {
        // Tanpa berkas dan teks terlalu pendek: biarkan server menampilkan pesan galatnya, formulir tetap terlihat.
        const hasFile = !!input.files?.[0];
        if (!hasFile && (text?.value.trim().length ?? 0) < 40) return;
        startLoading('analysis');
    });
}

// --- Perbaikan CV dan surat lamaran ---
const tool = document.querySelector('[data-ct-form]');
if (tool) {
    tool.addEventListener('submit', (e) => {
        const kind = e.submitter?.dataset.ctKind || 'improve';
        const role = tool.querySelector('[name="role"]')?.value.trim() || '';
        startLoading(kind, role);
    });
}
