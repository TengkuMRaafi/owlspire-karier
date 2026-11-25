{{-- ROADMAP interaktif "Jalur Owlspire". --}}
@php
    $rmCareers = $careers->take(6)->pluck('name')->values();
    if ($rmCareers->isEmpty()) { $rmCareers = collect(['Data Analyst', 'UI/UX Designer', 'Software Engineer', 'Digital Marketer', 'Akuntan', 'HR Specialist']); }
    $rmSteps = collect([
        ['compass', 'radar', 'Kenali dirimu', 'Tes 20 pertanyaan keahlian. Dapat karier yang cocok beserta alasannya.', 'careers.quiz', 'Mulai tes', false, '10 menit', 'Gratis',
            'Jawab situasi kerja nyata, bukan pertanyaan umum. Hasilnya dipetakan ke tujuh dimensi kerja dan dicocokkan dengan profil tiap karier.',
            ['20 pertanyaan keahlian yang spesifik, tanpa perlu akun', 'Profil tujuh dimensi: analitis, teknis, kreatif, sosial, lapangan, bisnis, ketelitian', 'Karier teratas terbuka gratis; alasan dan profil lengkap untuk Premium']],
        ['target', 'ring', 'Pilih karier, lalu uji', $careers->count().' karier dengan simulasi gratis: 15 soal, timer, dan pembahasan.', '#pilih-simulasi', 'Pilih simulasi', false, '15 menit', 'Gratis',
            'Pilih karier yang kamu incar, lalu langsung kerjakan simulasinya. Timer dihitung server seperti ujian sungguhan, dan selesai kamu dapat skor beserta pembahasan tiap soal.',
            ['Ringkasan peran dan skill tiap karier, pasang satu sebagai target', 'Timer server: menutup tab tidak menghentikan waktu', 'Pembahasan lengkap, saring hanya yang salah']],
        ['book', 'bars', 'Tutup celahnya', 'Video dan materi gratis sesuai skill yang kurang.', 'learning.index', 'Buka jalur belajar', false, 'Fleksibel', 'Gratis + Premium',
            'Hasil simulasi menunjukkan skill mana yang masih di bawah target. Jalur belajar langsung menunjuk materi untuk menutupnya.',
            ['Video YouTube pilihan dengan penjelasan di tiap topik', 'Dua topik pertama tiap jalur gratis', 'Progres belajar ikut menaikkan skor kesiapan']],
        ['briefcase', 'pipe', 'Lamar kerja', 'Lowongan yang cocok. Simpan dan lacak lamaranmu.', 'jobs.index', 'Lihat lowongan', false, 'Segera hadir', 'Segera',
            'Lowongan dicocokkan dengan skill dan karier targetmu. Simpan yang menarik, lamar, lalu pantau tiap tahapnya di satu tempat.',
            ['Lowongan terurut berdasarkan kecocokan skill', 'Simpan dan lacak status lamaran', 'Fitur sedang disiapkan']],
    ])->map(fn ($s) => [
        'icon' => $s[0], 'viz' => $s[1], 'title' => $s[2], 'desc' => $s[3], 'url' => str_starts_with($s[4], '#') ? $s[4] : (Route::has($s[4]) ? route($s[4]) : '#'),
        'cta' => $s[5], 'premium' => $s[6], 'time' => $s[7], 'tier' => $s[8], 'long' => $s[9], 'points' => $s[10],
    ])->values();
@endphp

<section id="perjalanan" class="rm" data-rm data-steps='@json($rmSteps)' data-careers='@json($rmCareers)' aria-label="Roadmap perjalananmu">
    <div class="rm-pin">
        <header class="rm-head">
            <div>
                <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-5xl">Dari bingung sampai siap melamar</h2>
                <p class="rm-sub mt-2 text-white/80">Maskot Owlspire berjalan sendiri di jalur ini. Klik langkahnya kalau mau melompat.</p>
            </div>
            <div class="rm-meter" aria-hidden="true"><small>Kesiapan &middot; <span data-rm-day>Day 1</span></small><b><span data-rm-pct>0</span>%</b><div class="rm-bar"><i data-rm-bar></i></div></div>
        </header>

        {{-- Penanda alur untuk HP: tombol sentuh per langkah + garis progres (disembunyikan di desktop). --}}
        <ol class="rm-steps" data-rm-steps aria-label="Langkah perjalanan">
            @foreach (['Kenali', 'Pilih', 'Belajar', 'Lamar'] as $i => $short)
                <li><button type="button" class="rm-chip" data-rm-chip="{{ $i }}" aria-label="Langkah {{ $i + 1 }}: {{ $rmSteps[$i]['title'] ?? $short }}"><b>{{ $i + 1 }}</b><span>{{ $short }}</span></button></li>
            @endforeach
        </ol>

        <div class="rm-stage">
            <div class="rm-big" aria-hidden="true"><span data-rm-big>Day 1</span></div>
            <svg data-rm-svg preserveAspectRatio="xMidYMid meet" role="img" aria-label="Jalur empat langkah dari Day 1 sampai Final Day: kenali dirimu sampai lamar kerja">
                <g data-stars></g>
                <g data-sun><circle class="sun-glow" r="46"/><circle class="sun-core" r="22"/></g>
                <g data-clouds>
                    <g class="rm-cloud" style="--d:0s"><ellipse cx="90" cy="64" rx="34" ry="10"/><ellipse cx="112" cy="55" rx="22" ry="11"/><ellipse cx="70" cy="57" rx="18" ry="9"/></g>
                    <g class="rm-cloud" style="--d:-18s"><ellipse cx="330" cy="30" rx="30" ry="9"/><ellipse cx="350" cy="22" rx="20" ry="10"/><ellipse cx="312" cy="24" rx="16" ry="8"/></g>
                    <g class="rm-cloud" style="--d:-33s"><ellipse cx="500" cy="96" rx="36" ry="10"/><ellipse cx="520" cy="87" rx="22" ry="11"/><ellipse cx="480" cy="89" rx="18" ry="9"/></g>
                </g>
                <g data-world>
                    <g data-layer="1" opacity=".5"><path d="M-400 300 L-200 190 L-20 180 L140 260 L320 130 L520 270 L700 160 L900 280 L1100 150 L1300 290 L1500 190 L1700 280 L1900 170 L2200 290 V900 H-400Z" fill="#ffffff14"/></g>
                    <g data-layer="2" opacity=".7"><path d="M-400 380 L-200 300 L0 290 L200 360 L420 260 L640 370 L860 280 L1100 370 L1300 300 L1500 380 L1700 290 L1900 370 L2200 300 V900 H-400Z" fill="#0000002e"/></g>
                </g>
                <path class="rm-land-edge" data-landedge d=""/>
                <path class="rm-land" data-land d=""/>
                <path class="rm-soil-edge" data-soiledge d=""/>
                <path class="rm-road" data-road d=""/>
                <g class="rm-decor" data-decor></g>
                <path class="rm-trail" data-trail d=""/>
                <path class="rm-line" data-line d=""/>
                <g data-stations></g>
                <g class="rm-fx" data-fx></g>
                <g class="rm-walker walking" data-walker><g data-flip><ellipse class="wk-shadow" cx="0" cy="15" rx="12" ry="3"/><g class="wk-hop" data-hop>
                    
                    <x-owl :animated="false" x="-21" y="-34" width="42" height="49" />
                </g></g></g>
            </svg>
        </div>

        <aside class="rm-panel" aria-live="polite"><div class="rm-card" data-rm-card></div></aside>
    </div>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-rm]'); if (!root) return;
        const NS = 'http://www.w3.org/2000/svg';
        const steps = JSON.parse(root.dataset.steps), careers = JSON.parse(root.dataset.careers);
        const n = steps.length, days = steps.map((_, i) => (i === n - 1 ? 'Final Day' : 'Day ' + (i + 1)));
        const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        const $ = (s) => root.querySelector(s);
        const svg = $('[data-rm-svg]'), line = $('[data-line]'), trail = $('[data-trail]'), road = $('[data-road]'), gSt = $('[data-stations]'), fx = $('[data-fx]');
        const walker = $('[data-walker]'), flip = $('[data-flip]'), hop = $('[data-hop]'), world = $('[data-world]'), sun = $('[data-sun]'), clouds = $('[data-clouds]'), starsG = $('[data-stars]');
        const landEdge = $('[data-landedge]'), land = $('[data-land]'), soilEdge = $('[data-soiledge]'), decor = $('[data-decor]'), dayLbl = $('[data-rm-day]'), big = $('[data-rm-big]');
        const chips = root.querySelectorAll('[data-rm-chip]'), card = $('[data-rm-card]'), pin = $('.rm-pin'), pct = $('[data-rm-pct]'), bar = $('[data-rm-bar]'), layers = root.querySelectorAll('[data-layer]');
        const wide = 'M50 440 C 190 450, 250 340, 150 300 S 60 190, 200 170 S 420 250, 390 140 S 470 100, 520 100';
        const tall = 'M30 195 C 110 200, 110 70, 200 100 S 290 210, 380 150 S 490 50, 570 60';
        const mq = matchMedia('(max-width: 900px)');
        const lerp = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
        const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        let L = 0, nodes = [], pts = [], last = -1, seq = 0, target = 0, cp = 0, moving = false, reached = 0, facing = 1, off = 0;
        // Tabel titik jalur: posisi maskot dibaca dari sini (bukan getPointAtLength tiap frame yang memaksa hitung ulang gaya/tata letak).
        const LUT_N = 360; let lutX = null, lutY = null;
        const stage = $('.rm-stage'), stepsEl = $('[data-rm-steps]');
        let skyEl = pin, lastP = -1, lastSlow = 0, lastPct = -1;

        // ---- Visual kecil per langkah ----
        const viz = {
            radar() {
                const v = [.85, .55, .6, .9, .5], lb = ['Analitis', 'Kreatif', 'Sosial', 'Teknis', 'Rapi'];
                const pt = (r, k) => [100 + r * Math.cos(-Math.PI / 2 + k * 1.2566), 100 + r * Math.sin(-Math.PI / 2 + k * 1.2566)];
                const poly = (m) => v.map((x, k) => pt(66 * x * m, k).map((n) => n.toFixed(1)).join(',')).join(' ');
                const grid = [.33, .66, 1].map((m) => `<polygon points="${[0, 1, 2, 3, 4].map((k) => pt(66 * m, k).join(',')).join(' ')}" fill="none" stroke="#fff" stroke-opacity=".18"/>`).join('');
                const labels = lb.map((t, k) => { const p = pt(86, k); return `<text x="${p[0]}" y="${p[1]}" text-anchor="middle" dominant-baseline="central">${t}</text>`; }).join('');
                const dots = v.map((x, k) => { const p = pt(66 * x, k); return `<circle class="rd" style="--k:${k}" cx="${p[0]}" cy="${p[1]}" r="3.5"/>`; }).join('');
                return `<div class="v-radar"><svg viewBox="0 0 200 200">${grid}${labels}<polygon class="rp" points="${poly(1)}"/>${dots}</svg><div class="v-top"><small>Karier teratas</small><b>${esc(careers[0])}</b><span>cocok 92%</span><b>${esc(careers[1] || '')}</b><span>cocok 84%</span><b>${esc(careers[2] || '')}</b><span>cocok 79%</span></div></div>`;
            },
            chips() {
                const body = careers.map((c, i) => `<button type="button" class="v-chip${i === 0 ? ' on' : ''}" style="--k:${i}" data-chip="${i}"><em>${String(i + 1).padStart(2, '0')}</em>${esc(c)}</button>`).join('');
                return `<div class="v-chips"><div>${body}</div><p class="v-pick">Target: <b data-pick>${esc(careers[0])}</b> <span>(klik untuk memilih)</span></p></div>`;
            },
            ring() {
                const dots = Array.from({ length: 15 }, (_, i) => `<i style="--k:${i}"></i>`).join('');
                return `<div class="v-ring"><svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="42" fill="none" stroke="#fff" stroke-opacity=".15" stroke-width="8"/><circle class="arc" cx="50" cy="50" r="42" fill="none" stroke="#7fa8e8" stroke-width="8" stroke-linecap="round" stroke-dasharray="264" transform="rotate(-90 50 50)"/></svg><b>14:32</b><div><small>Soal 6 dari 15</small><div class="v-dots">${dots}</div></div></div>`;
            },
            bars() {
                const rows = [['SQL', 40, 85], ['Excel', 70, 80], ['Python', 25, 60]];
                return `<div class="v-bars">${rows.map(([n, a, t], i) => `<div class="v-row" style="--k:${i}"><span>${n}</span><div class="v-track"><i style="--w:${a}%"></i><u style="left:${t}%"></u></div><em>${a < t - 15 ? 'kurang' : 'hampir'}</em></div>`).join('')}<p class="v-note">Garis oranye: target karier. Bar terisi: skormu sekarang.</p></div>`;
            },
            gauge() {
                return `<div class="v-gauge"><svg viewBox="0 0 120 80"><path d="M10 70 A50 50 0 0 1 110 70" fill="none" stroke="#fff" stroke-opacity=".15" stroke-width="10" stroke-linecap="round"/><path class="g" d="M10 70 A50 50 0 0 1 110 70" fill="none" stroke="#7fa8e8" stroke-width="10" stroke-linecap="round" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100"/><line x1="60" y1="70" x2="60" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round" class="nd"/></svg><div class="v-lock"><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path class="sh" d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></div><div><small>Passing grade 65</small><b>Skor 78</b><span class="v-ok">LULUS</span></div></div>`;
            },
            pipe() {
                const st = ['Tersimpan', 'Dilamar', 'Wawancara', 'Diterima'];
                return `<div class="v-pipe"><div class="rail"><i></i></div>${st.map((s, k) => `<div class="stg" style="--k:${k}"><b>${k + 1}</b><span>${s}</span></div>`).join('')}</div>`;
            },
        };

        const cardHTML = (idx) => {
            const s = steps[idx];
            const ic = (n) => `<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#i-${n}"/></svg>`;
            return `
                <div class="rm-meta"><span class="rm-no">${days[idx]} &middot; Langkah ${idx + 1} dari ${steps.length}</span><span class="rm-tier${s.premium ? ' pro' : ''}">${esc(s.tier)}</span><span class="rm-time">${esc(s.time)}</span></div>
                <h3><span class="rm-ico">${ic(s.icon)}</span>${esc(s.title)}</h3>
                <p class="rm-long">${esc(s.long)}</p>
                <div class="rm-viz">${viz[s.viz]()}</div>
                <ul class="rm-pts">${s.points.map((p, k) => `<li style="--k:${k}">${esc(p)}</li>`).join('')}</ul>
                <div class="rm-act"><a class="btn-primary" href="${esc(s.url)}">${esc(s.cta)}</a>
                    <div class="rm-arrows"><button type="button" data-go="${idx - 1}" aria-label="Langkah sebelumnya" ${idx === 0 ? 'disabled' : ''}>&larr;</button><button type="button" data-go="${idx + 1}" aria-label="Langkah berikutnya" ${idx === steps.length - 1 ? 'disabled' : ''}>&rarr;</button></div></div>`;
        };

        const render = (idx) => {
            seq++;
            card.className = 'rm-card swap';
            card.innerHTML = cardHTML(idx);
            card.querySelectorAll('[data-go]').forEach((b) => b.addEventListener('click', () => jump(+b.dataset.go)));
            card.querySelectorAll('[data-chip]').forEach((b) => b.addEventListener('click', () => {
                card.querySelectorAll('[data-chip]').forEach((x) => x.classList.toggle('on', x === b));
                card.querySelector('[data-pick]').textContent = careers[+b.dataset.chip];
            }));
        };

        // HP: kartu dikunci ke tinggi langkah terpanjang, jadi halaman tidak naik-turun saat langkah berganti.
        const lockH = () => {
            card.style.minHeight = '';
            if (!mq.matches) return;
            const probe = document.createElement('div');
            probe.className = 'rm-card'; probe.setAttribute('aria-hidden', 'true');
            probe.style.cssText = 'position:absolute;left:0;right:0;top:0;visibility:hidden;pointer-events:none;min-height:0;max-height:none';
            card.parentNode.appendChild(probe);
            let h = 0;
            steps.forEach((_, i) => { probe.innerHTML = cardHTML(i); h = Math.max(h, probe.offsetHeight); });
            probe.remove();
            card.style.minHeight = h + 'px';
        };

        // Maskot berjalan otomatis (bukan lewat scroll/kursor): lompat ke langkah tertentu hanya saat langkah diklik.
        const jump = (i) => { target = Math.max(0, Math.min(n - 1, i)) / (n - 1); hold = 0; };

        const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
        const ss = (a, b, v) => { const t = clamp((v - a) / (b - a)); return t * t * (3 - 2 * t); };
        const el = (tag, attrs = {}) => { const e = document.createElementNS(NS, tag); for (const k in attrs) e.setAttribute(k, attrs[k]); return e; };
        const mix = (stops, p) => {
            const f = clamp(p) * (stops.length - 1), i = Math.min(stops.length - 2, Math.floor(f));
            return lerp(stops[i], stops[i + 1], f - i);
        };
        // Langit: malam (Day 1) -> fajar -> siang cerah (Final Day)
        const SKY_TOP = [[12, 16, 30], [38, 46, 92], [70, 120, 190], [74, 150, 215]];
        const SKY_BOT = [[70, 40, 36], [200, 100, 70], [235, 170, 110], [160, 210, 240]];

        // Isi pemandangan: bintang di langit malam
        const scenery = (m) => {
            starsG.innerHTML = '';
            for (let k = 0; k < 26; k++) { const c = el('circle', { cx: (k * 97) % 600, cy: (k * 53) % (m ? 140 : 260), r: k % 3 ? 1.2 : 2, class: 'rm-star' }); c.style.animationDelay = (k % 7) * .4 + 's'; starsG.appendChild(c); }
        };

        // Kembang api kecil + "Yeay!" di tiap titik yang dicapai
        const CONF = ['#7fa8e8', '#ffffff', '#ffd0a8', '#6ee7b7', '#8fc1e3', '#dce8fb'];
        const WORDS = ['', 'Yeay!', 'Mantap!', 'Keren!', 'Sip, lanjut!', 'Siap melamar!'];
        const burst = (i) => {
            const p = pts[i]; if (!p) return;
            const fin = i === n - 1, m = mq.matches, g = el('g', { class: 'rm-burst', transform: `translate(${p.x} ${p.y})` });
            nodes[i].classList.remove('pop'); void nodes[i].getBoundingClientRect(); nodes[i].classList.add('pop');
            hop.classList.remove('hop'); void hop.getBoundingClientRect(); hop.classList.add('hop');
            if (reduce) return;
            g.appendChild(el('circle', { class: 'rm-ring', r: 26 }));
            const cnt = m ? (fin ? 22 : 10) : (fin ? 38 : 18);
            for (let k = 0; k < cnt; k++) {
                const a = -Math.PI / 2 + (Math.random() - .5) * Math.PI * 1.7, dist = (fin ? 70 : 46) + Math.random() * (fin ? 130 : 72);
                const c = k % 3 === 0 ? el('rect', { x: -3, y: -2, width: 7, height: 4, rx: 1 }) : el('circle', { r: 2.5 + Math.random() * 2.5 });
                c.setAttribute('fill', CONF[k % CONF.length]); c.setAttribute('class', 'pc');
                c.style.setProperty('--x', Math.cos(a) * dist + 'px'); c.style.setProperty('--y', Math.sin(a) * dist + 'px'); c.style.setProperty('--r', (Math.random() * 720 - 360) + 'deg');
                c.style.animationDelay = Math.random() * .12 + 's';
                g.appendChild(c);
            }
            const t = el('text', { class: 'rm-yeay', y: -(66 + (m ? 30 : 22)) - 16 });
            t.style.fontSize = (m ? 26 : 20) + 'px'; t.textContent = fin ? 'Final Day! ' + WORDS[5] : WORDS[Math.min(i, 4)] || 'Yeay!';
            g.appendChild(t);
            fx.appendChild(g); setTimeout(() => g.remove(), 1900);
            setTimeout(() => nodes[i] && nodes[i].classList.remove('pop'), 700);
        };

        const build = () => {
            const m = mq.matches, d = m ? tall : wide;
            off = m ? -120 : 0;
            svg.setAttribute('viewBox', m ? '0 -70 600 320' : '0 -20 600 520');
            world.setAttribute('transform', `translate(0 ${off})`);
            road.setAttribute('d', d); line.setAttribute('d', d); trail.setAttribute('d', d);
            L = line.getTotalLength(); line.style.strokeDasharray = L;
            lutX = new Float32Array(LUT_N + 1); lutY = new Float32Array(LUT_N + 1);
            for (let i = 0; i <= LUT_N; i++) { const q = line.getPointAtLength((L * i) / LUT_N); lutX[i] = q.x; lutY[i] = q.y; }
            // Warna langit: di HP dipasang di kotak jalur saja (bukan seluruh section), supaya perubahan warna tidak menghitung ulang gaya kartu di bawahnya.
            ['--bg1', '--bg2'].forEach((k) => { pin.style.removeProperty(k); stage.style.removeProperty(k); });
            skyEl = m ? stage : pin;
            scenery(m);
            const k = m ? .6 : 1;
            [[landEdge, 108], [land, 96], [soilEdge, 58], [road, 50]].forEach(([e, w]) => { e.setAttribute('d', d); e.style.strokeWidth = w * k; });
            decor.innerHTML = '';
            const rnd = (x) => { const v = Math.sin(x * 127.1 + 311.7) * 43758.5453; return v - Math.floor(v); };
            for (let t = 4, j = 0; t < L; t += 11 * k, j++) {
                const a0 = line.getPointAtLength(t), a1 = line.getPointAtLength(Math.min(L, t + 3));
                let nx = -(a1.y - a0.y), ny = a1.x - a0.x; const nl = Math.hypot(nx, ny) || 1; nx /= nl; ny /= nl;
                const side = rnd(j + 1) > .5 ? 1 : -1, off = (31 + rnd(j + 7) * 15) * k, x = a0.x + nx * off * side, y = a0.y + ny * off * side, kind = rnd(j + 3);
                if (kind < .5) decor.appendChild(el('path', { d: `M${x} ${y} l${-3 * k} ${-7 * k} M${x} ${y} l0 ${-9 * k} M${x} ${y} l${3 * k} ${-7 * k}`, class: 'tuft' }));
                else if (kind < .72) decor.appendChild(el('circle', { cx: x, cy: y, r: 2.4 * k, class: 'fl', style: '--fc:' + ['#ffd0a8', '#ffffff', '#7fa8e8', '#dce8fb'][j % 4] }));
                else if (kind < .86) decor.appendChild(el('ellipse', { cx: x, cy: y, rx: 4.5 * k, ry: 3 * k, class: 'rock' }));
            }
            gSt.innerHTML = ''; fx.innerHTML = ''; nodes = []; pts = []; last = -1;
            const fs = m ? 19 : 14, fh = m ? 30 : 22;
            steps.forEach((s, i) => {
                const p = line.getPointAtLength((L * i) / (n - 1)); pts.push(p);
                const g = el('g', { class: 'j-st' + (i === n - 1 ? ' fin' : ''), tabindex: '0', role: 'button', 'aria-label': days[i] + ', langkah ' + (i + 1) + ': ' + s.title });
                const lw = Math.round(s.title.length * 7 + 22), lab = m ? '' : `<g class="lab-g"><rect class="lab-bg" x="${(p.x - lw / 2).toFixed(1)}" y="${p.y + 35}" width="${lw}" height="22" rx="11"/><text class="lab" x="${p.x}" y="${p.y + 46}">${esc(s.title)}</text></g>`;
                const fw = Math.round(days[i].length * fs * .62 + 18), top = p.y - 66 - fh;
                const fx0 = p.x + fw > 585 ? p.x - fw : p.x; // dekat tepi kanan: bendera mengarah ke kiri
                const flag = `<g class="flag${fx0 < p.x ? ' rtl' : ''}"><line class="pole" x1="${p.x}" y1="${p.y - 22}" x2="${p.x}" y2="${top}"/><g class="cloth"><rect x="${fx0}" y="${top}" width="${fw}" height="${fh}" rx="5"/><text x="${fx0 + fw / 2}" y="${top + fh / 2}" style="font-size:${fs}px">${days[i]}</text></g></g>`;
                g.innerHTML = `<circle class="halo" cx="${p.x}" cy="${p.y}" r="26"/><circle class="dot" cx="${p.x}" cy="${p.y}" r="${m ? 20 : 26}"/><text class="num" x="${p.x}" y="${p.y}">${i + 1}</text><use class="ico" href="#i-${s.icon}" x="${p.x - 13}" y="${p.y - 13}" width="26" height="26"/>${lab}${flag}`;
                g.addEventListener('click', () => jump(i));
                g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); jump(i); } });
                gSt.appendChild(g); nodes.push(g);
            });
            reached = 0; for (let i = n - 1; i >= 0; i--) if (cp >= i / (n - 1) - .004) { reached = i; break; }
            lockH(); lastP = -1; lastSlow = 0; lastPct = -1; draw(true);
        };

        const clampN = (v) => Math.max(0, Math.min(1, v));
        const pointAt = (len) => {
            const f = clampN(len / L) * LUT_N, i = Math.min(LUT_N - 1, Math.floor(f)), t = f - i;
            return { x: lutX[i] + (lutX[i + 1] - lutX[i]) * t, y: lutY[i] + (lutY[i + 1] - lutY[i]) * t };
        };
        // Tulis properti hanya bila nilainya berubah: menghindari hitung ulang gaya yang sia-sia.
        const setVar = (e, k, v) => { const c = e._v || (e._v = {}); if (c[k] === v) return; c[k] = v; e.style.setProperty(k, v); };

        // Lapisan lambat: langit, matahari, paralaks, persen, penanda langkah. Perubahannya halus sehingga ~10x per detik cukup.
        const ambient = (p) => {
            layers[0].style.transform = `translateX(${(-p * 90).toFixed(1)}px)`; layers[1].style.transform = `translateX(${(-p * 180).toFixed(1)}px)`;
            setVar(skyEl, '--bg1', mix(SKY_TOP, p).join(','));
            setVar(skyEl, '--bg2', mix(SKY_BOT, p).join(','));
            const m = mq.matches, e = 1 - Math.pow(1 - p, 2);
            sun.setAttribute('transform', `translate(${(120 + p * 40).toFixed(1)} ${((m ? 200 : 470) + ((m ? 18 : 62) - (m ? 200 : 470)) * e).toFixed(1)})`);
            sun.style.opacity = ss(.04, .32, p).toFixed(2);
            setVar(sun, '--sun', mix([[255, 150, 86], [255, 190, 110], [255, 241, 184]], p).join(' '));
            starsG.style.opacity = clamp(1 - p * 1.6).toFixed(2); clouds.style.opacity = (ss(.25, .8, p) * .85).toFixed(2);
            // Kecerahan daratan dalam langkah 4% (nilai berubah ~12 kali sepanjang perjalanan, bukan tiap frame)
            setVar(svg, '--lbp', (Math.round((.5 + ss(0, .7, p) * .5) * 25) * 4) + '%');

            const pc = Math.round(p * 100);
            if (pc !== lastPct) { lastPct = pc; pct.textContent = pc; }
            bar.style.transform = `scaleX(${p.toFixed(3)})`;
            nodes.forEach((g, i) => g.classList.toggle('on', p >= i / (n - 1) - .004));
            const idx = Math.min(n - 1, Math.round(p * (n - 1)));
            if (stepsEl) setVar(stepsEl, '--rm-p', p.toFixed(3));
            chips.forEach((c, i) => { c.classList.toggle('on', p >= i / (n - 1) - .004); c.classList.toggle('cur', i === idx); if (i === idx) c.setAttribute('aria-current', 'step'); else c.removeAttribute('aria-current'); });
            if (idx === last) return;
            last = idx;
            nodes.forEach((g, i) => g.classList.toggle('cur', i === idx));
            dayLbl.textContent = days[idx]; big.textContent = days[idx]; big.classList.remove('flip'); void big.offsetWidth; big.classList.add('flip');
            render(idx);
            pin.classList.toggle('win', idx === n - 1);
        };

        // Lapisan cepat (tiap frame, hanya saat maskot bergerak): garis jalur dan posisi maskot.
        // Lapisan lambat dipanggil paling cepat tiap 50 ms (100 ms di HP) dan selalu sekali lagi setelah gerakan berhenti.
        let slowDirty = false;
        const draw = (force) => {
            const p = cp, now = performance.now();
            if (force || p !== lastP) {
                lastP = p; slowDirty = true;
                const len = L * p;
                line.style.strokeDashoffset = L * (1 - p);
                const pt = pointAt(len), ah = pointAt(Math.min(L, len + 5));
                if (Math.abs(ah.x - pt.x) > .2) { const f = ah.x < pt.x ? -1 : 1; if (f !== facing) { facing = f; flip.setAttribute('transform', `scale(${facing} 1)`); } }
                walker.setAttribute('transform', `translate(${pt.x.toFixed(1)} ${(pt.y - 14).toFixed(1)})`);
            }
            if (slowDirty && (force || now - lastSlow > (mq.matches ? 100 : 50))) { lastSlow = now; slowDirty = false; ambient(p); }
        };

        // Rayakan setiap titik yang baru dilewati (arah maju saja).
        const arrive = () => {
            let hi = 0; for (let i = n - 1; i >= 0; i--) if (cp >= i / (n - 1) - .004) { hi = i; break; }
            for (let i = reached + 1; i <= hi; i++) setTimeout(() => burst(i), (i - reached - 1) * 280);
            reached = hi;
        };

        // Autoplay: maskot berjalan ke titik berikutnya, berhenti sebentar, lalu lanjut; setelah Final Day mulai lagi dari Day 1.
        const SPEED = (1 / (n - 1)) / 2.6, WAIT = 3.6, WAIT_END = 5;
        let visible = true, hold = 0, tPrev = 0, hover = false;
        let running = false;
        const startLoop = () => { if (running) return; running = true; tPrev = 0; requestAnimationFrame(loop); };
        const loop = (t) => {
            // Di luar layar atau tab tersembunyi: loop berhenti total (tidak ada kerja per frame), dihidupkan lagi saat terlihat.
            if (!visible || document.hidden) { running = false; return; }
            const dt = Math.min(.05, (t - (tPrev || t)) / 1000); tPrev = t;
            {
                const d = target - cp;
                if (reduce || Math.abs(d) < .0004) cp = target;
                else cp += Math.sign(d) * Math.min(Math.abs(d), SPEED * dt * Math.min(1, .3 + Math.abs(d) * (n - 1) * 2));
                moving = Math.abs(target - cp) > .0012;
                walker.classList.toggle('walking', moving);
                draw(); arrive();
                if (moving) hold = 0;
                else if (!hover) {
                    hold += dt;
                    const idx = Math.round(cp * (n - 1));
                    if (hold > (idx === n - 1 ? WAIT_END : WAIT)) {
                        hold = 0;
                        if (idx >= n - 1) { cp = target = 0; reached = 0; last = -1; draw(true); } else target = (idx + 1) / (n - 1);
                    }
                }
            }
            requestAnimationFrame(loop);
        };
        if ('IntersectionObserver' in window) new IntersectionObserver((es) => { visible = es[0].isIntersecting; root.classList.toggle('is-off', !visible); if (visible) startLoop(); }, { threshold: .1 }).observe(root);
        document.addEventListener('visibilitychange', () => { if (!document.hidden && visible) startLoop(); });
        const panel = $('.rm-panel'); panel.addEventListener('mouseenter', () => { hover = true; }); panel.addEventListener('mouseleave', () => { hover = false; });

        chips.forEach((c) => c.addEventListener('click', () => jump(+c.dataset.rmChip)));
        let rz = 0; addEventListener('resize', () => { clearTimeout(rz); rz = setTimeout(() => { lockH(); }, 150); });
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(lockH);
        mq.addEventListener('change', build);
        addEventListener('keydown', (e) => {
            const r = root.getBoundingClientRect();
            if (r.top > innerHeight || r.bottom < 0 || /INPUT|TEXTAREA|SELECT/.test(e.target.tagName)) return;
            if (e.key === 'ArrowRight') jump(last + 1); else if (e.key === 'ArrowLeft') jump(last - 1);
        });
        build(); startLoop();

    })();
</script>
