// Perataan teks kiri-kanan yang rapi: paragraf rata kiri-kanan, kata dipenggal per suku kata (bahasa Indonesia)
// supaya jarak antarkata tidak melebar. Browser tidak punya kamus pemenggalan bahasa Indonesia, jadi dikerjakan di sini.
// Teks asli di server tidak berubah; yang disisipkan hanya soft hyphen (U+00AD) yang tidak terlihat kecuali di ujung baris.

const SHY = '\u00AD';
const MIN_LEFT = 2;   // minimal huruf sebelum tanda pisah
const MIN_RIGHT = 3;  // minimal huruf sesudah tanda pisah
const MIN_WORD = 6;   // kata lebih pendek tidak dipenggal

const VOWELS = new Set(['a', 'e', 'i', 'o', 'u']);
const DIPHTHONGS = new Set(['ai', 'au', 'oi', 'ei']);
const DIGRAPHS = new Set(['kh', 'ng', 'ny', 'sy']);

// Istilah asing yang sering muncul di antarmuka: tidak dipenggal supaya tidak terpotong salah (pas-sing, ma-chine, ...)
const FOREIGN = new Set([
    'passing', 'grade', 'machine', 'learning', 'engineer', 'software', 'developer', 'analyst', 'premium', 'dashboard',
    'checklist', 'resume', 'profile', 'online', 'offline', 'interview', 'recruiter', 'portfolio', 'feedback', 'default',
    'database', 'backend', 'frontend', 'fullstack', 'designer', 'manager', 'marketing', 'product', 'support', 'streak',
    'telegram', 'whatsapp', 'google', 'linkedin', 'upgrade', 'download', 'upload', 'keyword', 'ranking', 'timer',
]);
const FOREIGN_PATTERN = /(ck|ph|th|sh|gh|tion|ou|ow|ew|ay|ey|oy|ly$|ed$|er$|ght|que|wh|kn|ing$)/;

/** Pecah satu kata huruf kecil menjadi suku kata. */
export function syllables(word) {
    const units = [];
    for (let i = 0; i < word.length; i++) {
        const two = word.slice(i, i + 2);
        if (DIGRAPHS.has(two)) { // kh, ng, ny, sy dihitung satu konsonan ("ngg" = ng + g)
            units.push(two);
            i++;
        } else {
            units.push(word[i]);
        }
    }

    const vowelAt = [];
    units.forEach((u, idx) => { if (u.length === 1 && VOWELS.has(u)) vowelAt.push(idx); });
    if (vowelAt.length < 2) return [word];

    const cuts = []; // indeks unit tempat suku kata baru dimulai
    for (let k = 0; k < vowelAt.length - 1; k++) {
        const a = vowelAt[k];
        const b = vowelAt[k + 1];
        const between = b - a - 1;
        if (between === 0) {
            if (DIPHTHONGS.has(units[a] + units[b])) continue; // diftong tidak dipisah
            cuts.push(b);
        } else if (between === 1) {
            cuts.push(b - 1);
        } else {
            cuts.push(a + 2); // setelah konsonan pertama
        }
    }

    const out = [];
    let start = 0;
    for (const c of cuts) {
        out.push(units.slice(start, c).join(''));
        start = c;
    }
    out.push(units.slice(start).join(''));
    return out.filter(Boolean);
}

/** Sisipkan soft hyphen pada satu kata (hanya huruf a-z, boleh diawali huruf besar). */
export function hyphenateWord(word) {
    if (word.length < MIN_WORD) return word;
    if (!/^[A-Za-z]+$/.test(word)) return word;
    if (word === word.toUpperCase()) return word; // singkatan: TKD, CPNS, LLM
    const lower = word.toLowerCase();
    if (FOREIGN.has(lower) || FOREIGN_PATTERN.test(lower)) return word;
    if (/y/.test(lower.replace(/ny|sy/g, ''))) {
        // "y" selain di ny/sy/awal suku kata biasanya kata asing; hanya izinkan y sebelum vokal
        if (/y(?![aeiou])/.test(lower.replace(/ny|sy/g, ''))) return word;
    }

    const parts = syllables(lower);
    if (parts.length < 2) return word;

    let out = '';
    let pos = 0;
    for (let i = 0; i < parts.length; i++) {
        const len = parts[i].length;
        out += word.slice(pos, pos + len);
        pos += len;
        if (i < parts.length - 1) {
            const left = pos;
            const right = word.length - pos;
            if (left >= MIN_LEFT && right >= MIN_RIGHT) out += SHY;
        }
    }
    return out;
}

export function hyphenateText(text) {
    return text.replace(/[A-Za-z]{6,}/g, (w, offset, full) => {
        // jangan sentuh bagian dari alamat, surel, atau token dengan angka/garis bawah/@ yang menempel
        const before = full[offset - 1] ?? '';
        const after = full[offset + w.length] ?? '';
        if (/[\d_@/.\\]/.test(before) || /[\d_@/\\]/.test(after)) return w;
        return hyphenateWord(w);
    });
}

// ---------------------------------------------------------------------------------------------------------
// Bagian peramban
// ---------------------------------------------------------------------------------------------------------

const SKIP_SELECTOR = 'script, style, noscript, textarea, input, select, option, pre, code, kbd, samp, svg, canvas, h1, h2, h3, h4, h5, h6, [contenteditable=""], [contenteditable="true"], [data-no-rapi], .no-rapi';
const MIN_WORDS = 5;
const MIN_CHARS = 30;
const MAX_HYPHEN_RUN = 2;     // paling banyak 2 baris berturut-turut berakhiran tanda pisah
const LETTER_SHARE = 0.75;    // bagian sisa lebar yang dibagi ke jarak antarhuruf; sisanya ke spasi (justify)
const LETTER_CAP = 0.06;      // batas tambahan jarak antarhuruf (em)

const verdict = new WeakMap(); // blok -> boleh dirapikan atau tidak
const fitted = new WeakMap();  // blok -> { plain, src, width }

function blockOf(node) {
    let el = node.parentElement;
    while (el && el !== document.body) {
        const d = getComputedStyle(el).display;
        if (d !== 'inline' && d !== 'contents' && d !== 'ruby') return el;
        el = el.parentElement;
    }
    return el;
}

function eligible(block) {
    if (verdict.has(block)) return verdict.get(block);
    let ok = true;
    if (block.closest(SKIP_SELECTOR)) ok = false;
    if (ok) {
        const cs = getComputedStyle(block);
        const text = (block.textContent || '').replace(/\u00AD/g, '').replace(/\s+/g, ' ').trim();
        const words = text ? text.split(' ').length : 0;
        if (words < MIN_WORDS || text.length < MIN_CHARS) ok = false;
        else if (!['start', 'left', 'justify', 'match-parent'].includes(cs.textAlign)) ok = false; // tengah/kanan dibiarkan
        else if (cs.whiteSpace !== 'normal') ok = false;
        else if (parseInt(cs.fontWeight, 10) >= 700) ok = false; // judul tebal
        else if (['flex', 'inline-flex', 'grid', 'inline-grid'].includes(cs.display)) ok = false;
    }
    verdict.set(block, ok);
    return ok;
}

function textOnly(block) {
    return Array.from(block.childNodes).every((n) => n.nodeType === 3 || (n.nodeType === 1 && n.dataset && n.dataset.rl !== undefined));
}

// ---- pengukuran baris ------------------------------------------------------------------------------------

function measureLines(block, text, scale) {
    const range = document.createRange();
    const loc = []; // posisi tiap karakter: [node teks, offset]; blok bisa berisi beberapa node (span per baris)
    const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
    while (walker.nextNode()) {
        const n = walker.currentNode;
        for (let k = 0; k < n.nodeValue.length; k++) loc.push([n, k]);
    }
    if (loc.length !== text.length) return [];
    const chars = [];
    for (let i = 0; i < text.length; i++) {
        const c = text[i];
        if (c === SHY || c === ' ' || c === '\n' || c === '\t') { chars.push(null); continue; }
        range.setStart(loc[i][0], loc[i][1]);
        range.setEnd(loc[i][0], loc[i][1] + 1);
        const q = range.getClientRects();
        chars.push(q.length ? { i, l: q[0].left / scale, r: q[0].right / scale, t: q[0].top / scale, h: q[0].height / scale } : null);
    }
    // huruf tepat sesudah tanda pisah dilaporkan peramban di baris sebelumnya: ikut baris huruf berikutnya
    for (let i = 1; i < text.length; i++) {
        if (text[i - 1] === SHY && chars[i]) {
            const nx = chars.slice(i + 1).find((x) => x);
            if (nx) {
                // posisi huruf ini bocor ke ujung baris sebelumnya: pindahkan ke awal baris baru
                const w = chars[i].r - chars[i].l;
                chars[i].t = nx.t;
                chars[i].l = nx.l - w;
                chars[i].r = nx.l;
            }
        }
    }
    const lines = [];
    let cur = null;
    for (const ch of chars) {
        if (!ch) continue;
        if (!cur || ch.t > cur.t + ch.h * 0.5) {
            cur = { s: ch.i, e: ch.i + 1, l: ch.l, r: ch.r, t: ch.t };
            lines.push(cur);
        } else {
            cur.e = ch.i + 1;
            cur.r = Math.max(cur.r, ch.r);
        }
    }
    lines.forEach((ln, k) => { ln.hy = k < lines.length - 1 && text[ln.e] === SHY; });
    return lines;
}

let hyphenProbe = null;
function hyphenWidth(cs) {
    if (!hyphenProbe) hyphenProbe = document.createElement('canvas').getContext('2d');
    hyphenProbe.font = `${cs.fontStyle} ${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
    return hyphenProbe.measureText('-').width;
}

function contentWidth(block, cs) {
    return block.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
}

function layoutScale(block) {
    const w = block.offsetWidth;
    if (!w) return 1;
    const s = block.getBoundingClientRect().width / w;
    return s > 0.5 && s < 2 ? s : 1;
}

// ---- merapikan satu blok teks murni ----------------------------------------------------------------------

function plainFit(block, plain) {
    const src = hyphenateText(plain);
    block.textContent = src;
    block.classList.add('rapi');
    fitted.set(block, { plain, src, width: block.clientWidth, fit: false });
}

// Nilai kerapatan huruf dasar yang dicoba per paragraf (em); dipilih yang membuat baris paling sedikit "ditarik".
const TRACKING = [-0.012, 0, 0.012];

function badness(lines, W, hyW, spaceW) {
    let cost = 0;
    let run = 0;
    lines.forEach((ln, k) => {
        if (k === lines.length - 1) return;
        const natural = ln.r - ln.l + (ln.hy ? hyW : 0);
        const slack = Math.max(0, W - natural);
        const gaps = Math.max(1, ln.gaps);
        const stretch = slack / (gaps * spaceW);
        cost += stretch * stretch;
        if (ln.hy) { cost += 0.6; run += 1; if (run > MAX_HYPHEN_RUN) cost += 6; } else run = 0;
        if (natural > W + 0.5) cost += 50;
    });
    return cost;
}

function countGaps(lines, text) {
    lines.forEach((ln) => { ln.gaps = (text.slice(ln.s, ln.e).match(/ /g) || []).length; });
}

function resetBlock(block, src, ls0) {
    block.textContent = src;
    block.style.letterSpacing = ls0;
}

function fitBlock(block) {
    const state = fitted.get(block);
    if (state.ls0 === undefined) state.ls0 = block.style.letterSpacing || '';
    let src = state.src;
    resetBlock(block, src, state.ls0); // buang span lama
    block.classList.add('rapi');
    const cs = getComputedStyle(block);
    const W = contentWidth(block, cs);
    state.width = block.clientWidth;
    state.fit = false;
    if (W < 40 || cs.webkitLineClamp !== 'none' || parseFloat(cs.textIndent) !== 0) return;

    const scale = layoutScale(block);
    const fs = parseFloat(cs.fontSize);
    const hyW = hyphenWidth(cs);
    const spaceW = fs * 0.27;
    const baseLS = state.ls0 && cs.letterSpacing !== 'normal' ? parseFloat(cs.letterSpacing) : 0;

    // 1) pilih kerapatan dasar terbaik
    let bestG = 0;
    let bestCost = Infinity;
    for (const em of TRACKING) {
        const g = baseLS + em * fs;
        block.style.letterSpacing = g.toFixed(3) + 'px';
        const ls = measureLines(block, src, scale);
        if (!ls.length) continue;
        countGaps(ls, src);
        const c = badness(ls, W, hyW, spaceW);
        if (c < bestCost) { bestCost = c; bestG = g; }
    }
    block.style.letterSpacing = bestG.toFixed(3) + 'px';

    // 2) batasi tanda pisah beruntun
    let lines = [];
    for (let pass = 0; pass < 4; pass++) {
        lines = measureLines(block, src, scale);
        let run = 0;
        let cut = -1;
        for (const ln of lines) {
            run = ln.hy ? run + 1 : 0;
            if (run > MAX_HYPHEN_RUN) { cut = ln.e; break; }
        }
        if (cut < 0) break;
        src = src.slice(0, cut) + src.slice(cut + 1); // buang tanda pisah di titik itu, lalu hitung ulang
        block.textContent = src;
    }
    state.src = src;
    if (lines.length < 2) { block.style.letterSpacing = state.ls0; return; }

    // 3) tiap baris (selain terakhir) ditarik sampai tepat memenuhi lebar
    const heightBefore = block.getBoundingClientRect().height;
    const capPx = baseLS + LETTER_CAP * fs;
    const frag = document.createDocumentFragment();
    let pos = 0;
    lines.forEach((ln, k) => {
        if (ln.s > pos) frag.appendChild(document.createTextNode(src.slice(pos, ln.s)));
        const body = src.slice(ln.s, ln.e);
        const span = document.createElement('span');
        span.dataset.rl = '';
        span.textContent = body;
        if (k < lines.length - 1) {
            const natural = ln.r - ln.l + (ln.hy ? hyW : 0);
            const extra = W - natural - 0.8;
            const n = body.replace(/\u00AD/g, '').length + (ln.hy ? 1 : 0);
            const spaces = (body.match(/ /g) || []).length;
            if (extra > 0 && n > 0) {
                const share = spaces === 0 ? 1 : LETTER_SHARE;
                const room = (capPx - bestG) * (spaces === 0 ? 1.5 : 1);
                const inc = Math.min(Math.max(room, 0), (extra * share) / n);
                if (inc > 0.01) span.style.letterSpacing = (bestG + inc).toFixed(3) + 'px';
            }
        }
        else span.style.letterSpacing = baseLS.toFixed(3) + 'px'; // baris terakhir kembali ke kerapatan normal
        frag.appendChild(span);
        pos = ln.e;
    });
    if (pos < src.length) frag.appendChild(document.createTextNode(src.slice(pos)));
    block.textContent = '';
    block.appendChild(frag);

    // pastikan peramban tidak memecah baris di tempat lain; kalau berubah, batalkan penyetelan huruf
    const heightAfter = block.getBoundingClientRect().height;
    const nowLines = measureLines(block, block.textContent, scale);
    if (Math.abs(heightAfter - heightBefore) > 1 || nowLines.length !== lines.length) {
        resetBlock(block, src, state.ls0);
        return;
    }
    state.fit = true;
}

function safeFit(block) {
    try {
        fitBlock(block);
    } catch (e) {
        const st = fitted.get(block);
        if (st) resetBlock(block, st.src, st.ls0 || ''); // gagal: biarkan versi justify + pemenggalan biasa
    }
}

let busy = false;
let observer = null;

function process(root) {
    if (busy) return;
    busy = true;
    const pre = observer ? observer.takeRecords() : [];
    try {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: (n) => (n.nodeValue && /[A-Za-z]{6,}/.test(n.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT),
        });
        const blocks = new Set();
        while (walker.nextNode()) {
            const b = blockOf(walker.currentNode);
            if (b && eligible(b)) blocks.add(b);
        }
        blocks.forEach((block) => {
            if (textOnly(block)) {
                const plain = block.textContent.replace(/\u00AD/g, '').replace(/\s+/g, ' ').trim();
                const st = fitted.get(block);
                if (st && st.plain === plain) return;
                plainFit(block, plain);
                safeFit(block);
                sizeWatch.observe(block);
            } else {
                // blok dengan tag di dalamnya (mis. <b>): cukup pemenggalan + rata kiri-kanan
                const w = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
                while (w.nextNode()) {
                    const n = w.currentNode;
                    const next = hyphenateText(n.nodeValue.replace(/\u00AD/g, ''));
                    if (next !== n.nodeValue) n.nodeValue = next;
                }
                block.classList.add('rapi');
            }
        });
    } finally {
        busy = false;
        if (observer) observer.takeRecords(); // perubahan buatan sendiri diabaikan
        if (pre.length) schedule(pre);          // perubahan orang lain yang tertunda tetap diproses
    }
}

// Lebar blok berubah (layar diputar, jendela diubah, tab dibuka): hitung ulang baris.
let sizeTimer = null;
const sizeWatch = typeof ResizeObserver !== 'undefined'
    ? new ResizeObserver((entries) => {
        const todo = entries.map((e) => e.target).filter((b) => {
            const st = fitted.get(b);
            return st && Math.abs(b.clientWidth - st.width) > 0.5 && b.clientWidth > 0;
        });
        if (!todo.length) return;
        clearTimeout(sizeTimer);
        sizeTimer = setTimeout(() => {
            quiet(() => todo.forEach((b) => { if (b.isConnected) safeFit(b); }));
        }, 80);
    })
    : { observe() {} };

let mutTimer = null;
function schedule(list) {
    list.forEach((m) => {
        const t = m.target.nodeType === 1 ? m.target : m.target.parentElement;
        if (t) verdict.delete(t);
    });
    clearTimeout(mutTimer);
    mutTimer = setTimeout(() => process(document.body), 60);
}

// Jalankan perubahan DOM sendiri tanpa memicu pengamat, tanpa membuang perubahan orang lain yang tertunda.
function quiet(fn) {
    const pre = observer ? observer.takeRecords() : [];
    fn();
    if (observer) observer.takeRecords();
    if (pre.length) schedule(pre);
}

function start() {
    // Salin teks tanpa soft hyphen supaya hasil tempel tetap bersih.
    document.addEventListener('copy', (e) => {
        const sel = window.getSelection ? window.getSelection().toString() : '';
        if (sel.includes(SHY) && e.clipboardData) {
            e.clipboardData.setData('text/plain', sel.replace(/\u00AD/g, ''));
            e.preventDefault();
        }
    });

    process(document.body);
    // Font web yang baru selesai dimuat mengubah lebar kata: hitung ulang semua blok.
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => {
            quiet(() => document.querySelectorAll('.rapi').forEach((b) => { if (fitted.has(b) && b.isConnected) safeFit(b); }));
        });
    }

    observer = new MutationObserver((list) => schedule(list));
    observer.observe(document.body, { childList: true, characterData: true, subtree: true });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}
