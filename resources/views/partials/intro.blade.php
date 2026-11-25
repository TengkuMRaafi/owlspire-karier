{{-- Splash / perkenalan beranimasi. --}}
@php
    $brand = config('platform.brand.name');
    $introSlides = [
        [
            'tag' => 'Halo!',
            'title' => 'Halo, saya '.$brand.'.',
            'typed' => true,
            'text' => 'Aku yang akan menemanimu dari bingung memilih karier sampai siap melamar kerja.',
            'points' => [],
        ],
        [
            'tag' => 'Masalahnya',
            'title' => 'Ingin berkembang, tapi bingung mulai dari mana.',
            'text' => 'Banyak orang tidak tahu karier yang cocok, tidak punya tempat latihan ujian yang jujur, dan melamar kerja tanpa tahu skill apa yang kurang.',
            'points' => ['Bingung memilih karier', 'Latihan ujian tercecer di mana-mana', 'Tidak tahu celah skill sendiri'],
        ],
        [
            'tag' => 'Solusinya',
            'title' => 'Satu jalur dari bingung sampai siap melamar.',
            'text' => $brand.' menyatukan semuanya: kenali dirimu, pilih karier, uji lewat simulasi, tutup celah skill, lalu lamar kerja.',
            'points' => ['Tes arah karier 20 pertanyaan keahlian', 'Simulasi dengan timer dan pembahasan', 'Jalur belajar sesuai skill yang kurang'],
        ],
        [
            'tag' => 'Fitur',
            'title' => 'Yang kami sediakan untukmu.',
            'text' => 'Mulai gratis, tanpa akun. Premium membuka soal lanjutan dan analisis CV.',
            'points' => ['Karier populer dan skor kesiapan', 'Simulasi CPNS, BUMN, Polri/TNI', 'Belajar, analisis CV, dan lowongan'],
        ],
        [
            'tag' => 'Mari mulai',
            'title' => 'Siap melihat perjalananmu?',
            'text' => 'Masuk ke beranda dan coba simulasi gratis pertamamu. Dua menit saja.',
            'points' => [],
        ],
    ];
    $words = fn ($t) => collect(explode(' ', $t));

    // Obrolan maskot: tiap slide punya beberapa pesan yang diketik bergantian. Klik maskot untuk celetukan acak.
    $introChat = [
        'slides' => [
            ['Hoo-hoo! Aku '.$brand.'.', 'Teman jalanmu dari bingung sampai kerja.'],
            ['Bingung mulai dari mana?', 'Tenang, itu hal paling wajar.'],
            ['Semua digabung jadi satu jalur.', 'Tinggal ikuti, selangkah demi selangkah.'],
            ['Coba dulu, gratis kok.', 'Tanpa akun juga bisa.'],
            ['Siap? Yuk kita mulai!'],
        ],
        'quips' => [
            'Hoo! Geli, tahu.',
            'Aku jaga malam, kamu jaga mimpi.',
            'Kepalaku bisa muter 270 derajat, lho.',
            'Klik Lanjut, jangan keasyikan colek aku.',
            'Skor kesiapan itu tidak bohong.',
        ],
    ];
@endphp

<div id="intro" class="intro" data-s="0" role="dialog" aria-modal="true" aria-label="Perkenalan {{ $brand }}">
    <div class="intro-bg" aria-hidden="true"><i></i><i></i><i></i><u></u></div>

    <header class="intro-top">
        <p class="intro-brand"><span>Owl</span><b>spire</b></p>
        <div class="intro-prog" role="tablist" aria-label="Slide perkenalan">
            @foreach ($introSlides as $i => $s)<button type="button" role="tab" data-intro-go="{{ $i }}" aria-label="Slide {{ $i + 1 }}: {{ $s['tag'] }}"><b></b></button>@endforeach
        </div>
        <button type="button" class="intro-skip" data-intro-skip>Lewati</button>
    </header>

    <div class="intro-stage">
        @foreach ($introSlides as $i => $s)
            <section class="intro-slide s{{ $i }}" data-intro-slide aria-hidden="true">
                <div class="intro-copy">
                    <p class="intro-tag" data-a>{{ $s['tag'] }}</p>
                    @if (! empty($s['typed']))
                        <h1 class="intro-title is-typed" aria-label="{{ $s['title'] }}"><span data-type="{{ $s['title'] }}" data-speed="62"></span><i class="caret"></i></h1>
                        <p class="intro-text" data-a><span data-type="{{ $s['text'] }}" data-speed="18" data-after="1"></span></p>
                    @else
                        <h2 class="intro-title" aria-label="{{ $s['title'] }}" aria-hidden="false">@foreach ($words($s['title']) as $k => $w)<span class="w" aria-hidden="true"><span style="--i:{{ $k }}">{{ $w }}</span></span> @endforeach</h2>
                        <p class="intro-text" data-a>{{ $s['text'] }}</p>
                    @endif
                    @if ($s['points'])
                        <ul class="intro-points">
                            @foreach ($s['points'] as $k => $p)<li data-a style="--n:{{ $k }}">{{ $p }}</li>@endforeach
                        </ul>
                    @endif
                </div>

                <div class="intro-art" aria-hidden="true">
                    @switch($i)
                        @case(0)
                            <div class="art0"><i class="art0-glow"></i></div>
                            @break
                        @case(1)
                            <div class="art1">
                                <svg viewBox="0 0 320 320">
                                    <path class="tangle" d="M40 250 C90 150 130 300 170 200 S250 110 290 230 M60 120 C120 200 160 70 220 150 S270 260 300 160" fill="none" stroke="#fff" stroke-opacity=".18" stroke-width="3" stroke-linecap="round"/>
                                    <g class="person">
                                        <rect x="108" y="196" width="104" height="110" rx="46" fill="#2f58b3"/>
                                        <g class="head"><circle cx="160" cy="150" r="40" fill="#ffd9a8"/><path d="M120 146 Q122 104 162 108 Q200 106 200 146 Q184 126 160 128 Q134 128 120 146Z" fill="#1b2638"/><circle cx="147" cy="154" r="3.5" fill="#1b2638"/><circle cx="173" cy="154" r="3.5" fill="#1b2638"/><path d="M150 174 Q160 168 170 174" stroke="#1b2638" stroke-width="3" fill="none" stroke-linecap="round"/></g>
                                        <path class="arm" d="M118 232 Q84 186 128 150" fill="none" stroke="#2f58b3" stroke-width="22" stroke-linecap="round"/>
                                    </g>
                                </svg>
                                @foreach ([[6,8,-14,0],[78,2,12,.6],[88,46,-8,1.2],[84,80,16,.3],[2,58,10,.9],[10,86,-12,1.5]] as [$x, $y, $r, $d])
                                    <span class="q" style="left:{{ $x }}%;top:{{ $y }}%;--r:{{ $r }}deg;animation-delay:{{ $d }}s">?</span>
                                @endforeach
                            </div>
                            @break
                        @case(2)
                            <div class="art2">
                                <svg viewBox="0 0 320 320">
                                    <circle class="ring" cx="160" cy="160" r="118" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="2" stroke-dasharray="3 9"/>
                                    <circle cx="160" cy="160" r="86" fill="#1a232d" stroke="#7fa8e8" stroke-width="3"/>
                                    <g class="needle"><polygon points="160,86 172,160 160,174 148,160" fill="#7fa8e8"/><polygon points="160,234 172,160 160,174 148,160" fill="#fff" fill-opacity=".85"/></g>
                                    <circle cx="160" cy="160" r="8" fill="#fff"/>
                                </svg>
                                @foreach ([['Kenali', 50, 2], ['Uji', 92, 50], ['Belajar', 50, 94], ['Lamar', 8, 50]] as $k => [$lb, $x, $y])
                                    <span class="sat" style="left:{{ $x }}%;top:{{ $y }}%;--k:{{ $k }}">{{ $lb }}</span>
                                @endforeach
                            </div>
                            @break
                        @case(3)
                            <div class="art3">
                                <svg class="fc-beam" viewBox="0 0 400 368" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M150 64 C275 62 308 96 312 150 C318 215 235 260 165 300" fill="none" stroke="#7fa8e8" stroke-opacity=".45" stroke-width="2" stroke-dasharray="3 8"/>
                                    <circle r="9" fill="#7fa8e8" opacity=".35"><animateMotion dur="3.6s" repeatCount="indefinite" begin="indefinite" path="M150 64 C275 62 308 96 312 150 C318 215 235 260 165 300"/></circle>
                                    <circle r="5" fill="#cfe0ff"><animateMotion dur="3.6s" repeatCount="indefinite" begin="indefinite" path="M150 64 C275 62 308 96 312 150 C318 215 235 260 165 300"/></circle>
                                </svg>
                                <div class="fc fc1"><small>Simulasi Data Analyst</small>
                                    <div class="fc-row"><svg viewBox="0 0 44 44" class="fc-ring"><circle cx="22" cy="22" r="18" fill="none" stroke="#fff" stroke-opacity=".2" stroke-width="5"/><circle class="fc-arc" cx="22" cy="22" r="18" fill="none" stroke="#7fa8e8" stroke-width="5" stroke-linecap="round" stroke-dasharray="113" transform="rotate(-90 22 22)"/></svg><b data-clock>14:32</b></div>
                                    <div class="fc-dots">@for ($k = 0; $k < 15; $k++)<i @if ($k < 6) class="f" style="--k:{{ $k }}" @endif></i>@endfor</div>
                                </div>
                                <div class="fc fc2"><small>Skor kesiapan</small>
                                    <div class="fc-sc"><b><span data-to="78">0</span></b><em>+6</em></div>
                                    <svg class="fc-spk" viewBox="0 0 100 26" aria-hidden="true"><path pathLength="1" d="M2 22 L20 17 L38 19 L56 11 L76 13 L98 3"/></svg>
                                </div>
                                <div class="fc fc3"><small>Cocok dengan skill kamu</small><b><span data-to="92">0</span>%</b>
                                    <div class="fc-chips"><span style="--k:0">SQL</span><span style="--k:1">Excel</span><span style="--k:2">Statistik</span></div>
                                </div>
                            </div>
                            @break
                        @default
                            <div class="art4">
                                <i class="pulse"></i><i class="pulse p2"></i>
                                <div class="rk-stars" aria-hidden="true">@for ($k = 0; $k < 16; $k++)<i style="left:{{ ($k * 37 + 11) % 96 }}%;animation-delay:-{{ round(fmod($k * .37, 2.6), 2) }}s;height:{{ 30 + ($k * 13) % 34 }}px;opacity:{{ .2 + ($k % 4) * .1 }}"></i>@endfor</div>
                                <i class="rk-shock" aria-hidden="true"></i>
                                @for ($k = 0; $k < 7; $k++)<b class="rk-smoke" aria-hidden="true" style="--k:{{ $k }};--x:{{ ($k - 3) * 22 }}px"></b>@endfor
                                <svg viewBox="0 0 320 320">
                                    <defs>
                                        <radialGradient id="rk-glow"><stop offset="0" stop-color="#7fa8e8" stop-opacity=".55"/><stop offset="1" stop-color="#7fa8e8" stop-opacity="0"/></radialGradient>
                                        <linearGradient id="rk-body" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#c5cfde"/><stop offset=".45" stop-color="#fff"/><stop offset="1" stop-color="#a9c4f2"/></linearGradient>
                                        <linearGradient id="rk-fire" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff3d6"/><stop offset=".35" stop-color="#f5b942"/><stop offset="1" stop-color="#f5b942" stop-opacity="0"/></linearGradient>
                                    </defs>
                                    <circle cx="160" cy="160" r="128" fill="url(#rk-glow)"/>
                                    <circle class="rk-ring" cx="160" cy="160" r="118" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="2" stroke-dasharray="3 9"/>
                                    <g class="rk-speed" stroke="#a9c4f2" stroke-width="3" stroke-linecap="round"><path d="M104 220V262"/><path d="M216 214V270"/><path d="M82 186V214"/><path d="M238 180V212"/></g>
                                    <g class="rk-stars" fill="#fff"><path class="st a" d="M64 96 l3 8 l8 3 l-8 3 l-3 8 l-3 -8 l-8 -3 l8 -3Z"/><path class="st b" d="M254 84 l2.4 6.4 l6.4 2.4 l-6.4 2.4 l-2.4 6.4 l-2.4 -6.4 l-6.4 -2.4 l6.4 -2.4Z"/><circle class="st c" cx="262" cy="170" r="3"/><circle class="st d" cx="52" cy="170" r="2.4"/></g>
                                    <g class="rk">
                                        <path class="rk-fl" d="M144 218 C142 246 154 268 160 288 C166 268 178 246 176 218Z" fill="url(#rk-fire)"/>
                                        <path d="M126 168 L92 212 L92 240 L128 216Z" fill="#2f58b3"/><path d="M194 168 L228 212 L228 240 L192 216Z" fill="#2f58b3"/>
                                        <path d="M160 46 C198 78 206 140 198 212 H122 C114 140 122 78 160 46Z" fill="url(#rk-body)"/>
                                        <path d="M160 46 C177 61 189 80 194 102 H126 C131 80 143 61 160 46Z" fill="#3b6cd4"/>
                                        <path d="M144 212 H176 L172 224 H148Z" fill="#34435c"/>
                                        <circle cx="160" cy="140" r="23" fill="#1b2638" stroke="#7fa8e8" stroke-width="6"/><circle cx="160" cy="140" r="14" fill="#4a7fdc"/><path d="M152 134 a10 10 0 0 1 10 -6" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".8"/>
                                        <path d="M138 178 H182" stroke="#3b6cd4" stroke-width="5" stroke-linecap="round" opacity=".55"/>
                                    </g>
                                </svg>
                                <div class="confetti" data-confetti></div>
                            </div>
                    @endswitch
                </div>
            </section>
        @endforeach
    </div>

    <div class="intro-owl" aria-hidden="true" style="position:absolute;max-width:min(17rem,34vh)" data-chat='@json($introChat)'>
        <x-owl :animated="true" :draw="true" data-chat-owl />
    </div>
    {{-- Gelembung obrolan: posisinya dihitung skrip agar tidak menimpa teks atau ilustrasi slide. --}}
    <div class="owl-chat" data-chat-box aria-hidden="true" data-side="top"><b class="oc-name"><i></i>Owlspire</b><span class="oc-dots"><i></i><i></i><i></i></span><span class="oc-txt" data-chat-txt></span></div>

    <div class="intro-nav" data-intro-nav>
        <button type="button" class="intro-btn intro-btn-ghost" data-intro-prev aria-label="Slide sebelumnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        </button>
        <button type="button" class="intro-btn" data-intro-next><span data-intro-label>Lanjut</span><svg class="ic-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg><svg class="ic-go" viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09zM12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2zM9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg></button>
    </div>
    <p class="intro-hint" aria-hidden="true">Gunakan tombol panah atau geser layar</p>
</div>

<script>
    (function () {
        var el = document.getElementById('intro');
        var seen = false;
        try {
            if (/[?&]intro=1\b/.test(location.search)) localStorage.removeItem('owlspire_intro_seen');
            seen = localStorage.getItem('owlspire_intro_seen') === '1';
        } catch (e) {}
        if (seen) { el.remove(); return; }
        document.documentElement.classList.add('intro-lock');

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var slides = [].slice.call(el.querySelectorAll('[data-intro-slide]'));
        var segs = [].slice.call(el.querySelectorAll('[data-intro-go]'));
        var prev = el.querySelector('[data-intro-prev]');
        var next = el.querySelector('[data-intro-next]');
        var label = el.querySelector('[data-intro-label]');
        var cur = -1, timers = [], last = slides.length - 1;

        // Konfeti slide terakhir
        var conf = el.querySelector('[data-confetti]');
        if (conf) {
            var cols = ['#7fa8e8', '#fff', '#a9c4f2', '#3b6cd4', '#8fc1e3'];
            for (var k = 0; k < 30; k++) {
                var c = document.createElement('i'), a = Math.random() * 6.283, d = 90 + Math.random() * 130;
                c.style.cssText = '--x:' + Math.cos(a) * d + 'px;--y:' + (Math.sin(a) * d - 30) + 'px;--r:' + (Math.random() * 720 - 360) + 'deg;--c:' + cols[k % 5] + ';animation-delay:' + (Math.random() * 1.6) + 's';
                conf.appendChild(c);
            }
        }

        function clearTimers() { timers.forEach(clearTimeout); timers = []; }

        // Ketik teks satu per satu: "Halo, saya Owlspire." lalu kalimat berikutnya.
        function typeAll(slide) {
            var nodes = [].slice.call(slide.querySelectorAll('[data-type]'));
            var caret = slide.querySelector('.caret');
            nodes.forEach(function (n) { n.textContent = reduce ? n.dataset.type : ''; });
            if (reduce || !nodes.length) return;
            var idx = 0;
            (function nextNode() {
                var n = nodes[idx++]; if (!n) { if (caret) caret.classList.add('idle'); return; }
                var txt = n.dataset.type, sp = +n.dataset.speed || 40, p = 0;
                if (caret) { caret.classList.remove('idle'); if (n.dataset.after) caret.classList.add('idle'); }
                (function tick() {
                    n.textContent = txt.slice(0, ++p);
                    if (p < txt.length) timers.push(setTimeout(tick, sp + (/[,.]/.test(txt[p - 1]) ? 160 : 0)));
                    else timers.push(setTimeout(nextNode, 250));
                })();
            })();
        }

        // Obrolan maskot. Gelembung dipasang di luar maskot dan ditempatkan skrip pada sisi yang kosong
        // (atas, kanan, kiri, bawah) sehingga tidak menimpa judul, teks, ataupun ilustrasi slide.
        var owlEl = el.querySelector('.intro-owl');
        var chat = owlEl ? JSON.parse(owlEl.dataset.chat || '{}') : {};
        var box = el.querySelector('[data-chat-box]'), cTxt = el.querySelector('[data-chat-txt]');
        var owlSvg = el.querySelector('[data-chat-owl]');
        var cTimers = [], quipN = 0;
        var AVOID = '.intro-tag, .intro-title, .intro-text, .intro-points li, .art1 g.person, .art1 .q, .art2 svg, .art2 .sat, .art3 .fc, .art4 svg';
        function clearChat() { cTimers.forEach(clearTimeout); cTimers = []; if (box) box.classList.remove('is-on', 'is-typing'); }
        function overlap(a, b) { var w = Math.min(a.right, b.right) - Math.max(a.left, b.left), h = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top); return w > 0 && h > 0 ? w * h : 0; }
        function place() {
            if (!box || !owlSvg) return;
            var o = owlSvg.getBoundingClientRect(), vw = innerWidth, vh = innerHeight;
            var bw = box.offsetWidth, bh = box.offsetHeight, g = vw < 768 ? 8 : 14, m = 8, ocx = o.left + o.width / 2, ocy = o.top + o.height / 2;
            var obs = [];
            ['.intro-top', '.intro-nav', '.intro-hint'].forEach(function (q) { var n = el.querySelector(q); if (n) obs.push(n.getBoundingClientRect()); });
            var sl = slides[cur]; if (sl) [].forEach.call(sl.querySelectorAll(AVOID), function (n) { var r = n.getBoundingClientRect(); if (r.width && r.height) obs.push({ left: r.left - 6, right: r.right + 6, top: r.top - 6, bottom: r.bottom + 6 }); });
            var cands = [
                ['top', ocx - bw / 2, o.top - bh - g], ['right', o.right + g, ocy - bh / 2],
                ['left', o.left - bw - g, ocy - bh / 2], ['bottom', ocx - bw / 2, o.bottom + g]
            ], best = null;
            cands.forEach(function (c, k) {
                var x = Math.max(m, Math.min(vw - bw - m, c[1])), y = Math.max(m, Math.min(vh - bh - m, c[2]));
                var r = { left: x, right: x + bw, top: y, bottom: y + bh }, score = k * 40;
                obs.forEach(function (b) { score += overlap(r, b); });
                score += overlap(r, o) * 4; // jangan menutupi maskot sendiri
                score += (Math.abs(x - c[1]) + Math.abs(y - c[2])) * 30; // terpaksa digeser agar muat di layar
                if (!best || score < best.s) best = { s: score, side: c[0], x: x, y: y };
            });
            box.dataset.side = best.side;
            box.style.left = best.x + 'px'; box.style.top = best.y + 'px';
            var tx = Math.max(20, Math.min(bw - 20, ocx - best.x)), ty = Math.max(18, Math.min(bh - 18, ocy - best.y));
            box.style.setProperty('--tx', tx + 'px'); box.style.setProperty('--ty', ty + 'px');
        }
        addEventListener('resize', function () { if (box && box.classList.contains('is-on')) place(); });

        function say(msgs, startDelay) {
            if (!box || !msgs || !msgs.length) return;
            var i = 0;
            function render(done, rest) {
                cTxt.textContent = '';
                var a = document.createElement('span'), b = document.createElement('span');
                a.textContent = done; b.textContent = rest; b.className = 'oc-rest'; // sisa teks tak terlihat, supaya ukuran gelembung tetap
                cTxt.appendChild(a); cTxt.appendChild(b);
            }
            function next() {
                var m = msgs[i];
                render('', m);
                if (reduce) { box.classList.remove('is-typing'); render(m, ''); place(); box.classList.add('is-on'); return; }
                box.classList.add('is-typing'); place(); box.classList.add('is-on');
                cTimers.push(setTimeout(function () {
                    box.classList.remove('is-typing'); place();
                    var p = 0;
                    (function tick() {
                        p++; render(m.slice(0, p), m.slice(p));
                        if (p < m.length) cTimers.push(setTimeout(tick, 34 + (/[,.?!]/.test(m[p - 1]) ? 150 : 0)));
                        else if (i < msgs.length - 1) cTimers.push(setTimeout(function () { i++; next(); }, 2300));
                    })();
                }, 800));
            }
            cTimers.push(setTimeout(next, startDelay));
        }
        function chatFor(n) { clearChat(); if (chat.slides) say(chat.slides[n], reduce ? 0 : 1500); }
        if (owlSvg) owlSvg.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!chat.quips) return;
            clearChat();
            var q = chat.quips[quipN++ % chat.quips.length];
            if (!reduce) { owlEl.classList.remove('is-poke'); void owlEl.offsetWidth; owlEl.classList.add('is-poke'); }
            say([q], 0);
            // Setelah celetukan, kembali ke obrolan slide yang sedang tampil.
            cTimers.push(setTimeout(function () { chatFor(cur); }, 3600 + q.length * 40));
        });

        function counters(slide) {
            [].forEach.call(slide.querySelectorAll('[data-to]'), function (n) {
                var to = +n.dataset.to, t0 = performance.now() + 500;
                if (reduce) { n.textContent = to; return; }
                n.textContent = 0;
                (function f(now) {
                    var k = Math.max(0, Math.min(1, (now - t0) / 1400));
                    n.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
                    if (k < 1) requestAnimationFrame(f);
                })(performance.now());
            });
        }

        // Slide "Fitur": jam berdetak dan kartu disorot bergantian bersama poin di sampingnya.
        function featureLive(slide) {
            var cards = [].slice.call(slide.querySelectorAll('.art3 .fc')), pts = [].slice.call(slide.querySelectorAll('.intro-points li'));
            var map = [1, 0, 2]; // poin 1 (skor) -> kartu 2, poin 2 (simulasi) -> kartu 1, poin 3 -> kartu 3
            var clk = slide.querySelector('[data-clock]'), left = 872;
            if (reduce) return;
            if (clk) (function tick() {
                timers.push(setTimeout(function () {
                    left = left > 0 ? left - 1 : 872;
                    clk.textContent = ('0' + Math.floor(left / 60)).slice(-2) + ':' + ('0' + left % 60).slice(-2);
                    tick();
                }, 1000));
            })();
            var i = -1;
            (function step() {
                i = (i + 1) % 3;
                cards.concat(pts).forEach(function (x) { x.classList.remove('hot'); });
                if (pts[i]) pts[i].classList.add('hot');
                if (cards[map[i]]) cards[map[i]].classList.add('hot');
                timers.push(setTimeout(step, 2300));
            })();
        }
        // Slide terakhir: hitung mundur, roket bergetar, meluncur, lalu muncul lagi dari bawah.
        var art4 = el.querySelector('.art4');
        function rocketReset() {
            if (!art4) return;
            art4.classList.remove('is-shake', 'is-launch', 'is-away', 'is-back');
            var c = art4.querySelector('.rk-count'); if (c) c.remove();
        }
        function rocketCount(t) {
            var c = art4.querySelector('.rk-count'); if (c) c.remove();
            if (t) art4.insertAdjacentHTML('beforeend', '<div class="rk-count" aria-hidden="true">' + t + '</div>');
        }
        function rocketLoop() {
            if (!art4 || reduce) return;
            function L(f, ms) { timers.push(setTimeout(f, ms)); }
            rocketReset();
            L(function () { rocketCount(3); }, 2800);
            L(function () { rocketCount(2); }, 3450);
            L(function () { rocketCount(1); art4.classList.add('is-shake'); }, 4100);
            L(function () { rocketCount(''); art4.classList.remove('is-shake'); art4.classList.add('is-launch'); }, 4750);
            L(function () { art4.classList.remove('is-launch'); art4.classList.add('is-away'); }, 6100);
            L(function () { art4.classList.remove('is-away'); art4.classList.add('is-back'); }, 6500);
            L(rocketLoop, 8200);
        }

        function show(n) {
            n = Math.max(0, Math.min(last, n));
            if (n === cur) return;
            clearTimers(); rocketReset();
            [].forEach.call(el.querySelectorAll('.hot'), function (x) { x.classList.remove('hot'); });
            el.style.setProperty('--dir', n > cur ? 1 : -1);
            slides.forEach(function (s, i) {
                var on = i === n;
                if (i === cur) { s.classList.add('is-out'); setTimeout(function () { s.classList.remove('is-out'); }, 750); }
                s.classList.toggle('is-active', on);
                s.setAttribute('aria-hidden', on ? 'false' : 'true');
            });
            cur = n;
            el.dataset.s = n;
            var ow = el.querySelector('.intro-owl');
            if (ow && !reduce) { ow.classList.remove('is-hop'); void ow.offsetWidth; ow.classList.add('is-hop'); }
            segs.forEach(function (b, i) { b.classList.toggle('done', i < n); b.classList.toggle('on', i === n); b.setAttribute('aria-selected', i === n); });
            prev.classList.toggle('is-off', n === 0);
            label.textContent = n === last ? 'Mari mulai' : (n === 0 ? 'Kenalan dulu' : 'Lanjut');
            next.classList.toggle('is-final', n === last);
            var s = slides[n];
            typeAll(s); counters(s); chatFor(n);
            if (n === 3) featureLive(s);
            if (n === last) rocketLoop();
            [].forEach.call(s.querySelectorAll('animateMotion'), function (m) { try { m.beginElementAt(0.4); } catch (e) {} });
        }

        function close() {
            try { localStorage.setItem('owlspire_intro_seen', '1'); } catch (e) {}
            clearTimers(); clearChat();
            el.classList.add('is-leaving');
            document.documentElement.classList.remove('intro-lock');
            document.removeEventListener('keydown', onKey);
            setTimeout(function () { el.remove(); window.scrollTo(0, 0); }, 700);
        }
        function onKey(e) {
            if (e.key === 'ArrowRight' || e.key === 'Enter') { if (e.target === document.body || e.key === 'ArrowRight') next.click(); }
            else if (e.key === 'ArrowLeft') prev.click();
            else if (e.key === 'Escape') close();
        }

        next.addEventListener('click', function () { cur === last ? close() : show(cur + 1); });
        prev.addEventListener('click', function () { show(cur - 1); });
        segs.forEach(function (b) { b.addEventListener('click', function () { show(+b.dataset.introGo); }); });
        el.querySelector('[data-intro-skip]').addEventListener('click', close);
        document.addEventListener('keydown', onKey);

        // Geser di layar sentuh.
        var x0 = null;
        el.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        el.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0; x0 = null;
            if (dx < -50) next.click(); else if (dx > 50) prev.click();
        });

        // Paralaks halus mengikuti pointer.
        // Dibatasi satu kali per frame agar tidak memaksa hitung ulang gaya di setiap gerakan mouse (penyebab patah-patah).
        if (!reduce) {
            var px = 0, py = 0, pend = false;
            el.addEventListener('pointermove', function (e) {
                px = e.clientX; py = e.clientY;
                if (pend) return;
                pend = true;
                requestAnimationFrame(function () {
                    pend = false;
                    el.style.setProperty('--mx', (px / innerWidth - .5).toFixed(2));
                    el.style.setProperty('--my', (py / innerHeight - .5).toFixed(2));
                });
            }, { passive: true });
        }

        show(0);
        next.focus({ preventScroll: true });
    })();
</script>
