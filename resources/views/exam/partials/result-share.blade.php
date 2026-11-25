{{--
    Tombol "Bagikan" + dialog kartu hasil. Dipanggil dari exam/result.blade.php (di dalam .rh-act), memakai
    $attempt, $pct, $maxTotal, $benar, $salah, $kosong, $isFull dari halaman itu.
    Gambar kartu digambar di browser (canvas); tautan publik dibuat saat dialog dibuka dan bisa dimatikan.
--}}
@php
    $shareData = [
        'brand' => config('platform.brand.name'),
        'type' => $attempt->type->name,
        'pct' => (int) $pct,
        'score' => (int) $attempt->total_score,
        'max' => (int) $maxTotal,
        'benar' => (int) $benar,
        'salah' => (int) $salah,
        'kosong' => (int) $kosong,
        'full' => (bool) $isFull,
        'passed' => (bool) $attempt->passed,
        'date' => $attempt->finished_at->translatedFormat('d F Y'),
        'storeUrl' => route('exam.share', $attempt),
        'revokeUrl' => route('exam.share.revoke', $attempt),
        'tagline' => (string) config('platform.brand.tagline'),
        'logo' => str_replace('<svg ', '<svg width="240" height="280" ', (string) @file_get_contents(public_path('images/logo-mark.svg'))),
        'segments' => collect($insights['segments'] ?? [])->take(4)->map(fn ($x) => [
            'n' => preg_match('/\(([^)]+)\)/', (string) $x['name'], $m) ? $m[1] : \Illuminate\Support\Str::limit((string) $x['name'], 22),
            'p' => (int) $x['pct'],
        ])->values()->all(),
    ];
@endphp
<button type="button" class="rh-btn" data-share-open><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.5l6.8-4M8.6 13.5l6.8 4"/></svg>Bagikan</button>

<dialog class="shr" data-share aria-labelledby="shr-title">
    <style>
        .shr { inset: 0; margin: auto; width: min(92vw, 30rem); max-height: 94vh; padding: 0; border: 0; border-radius: 1.2rem; background: #fff; color: #1f2a3d; box-shadow: 0 30px 80px rgb(15 24 40 / .45); overflow: auto; }
        .shr::backdrop { background: rgb(15 24 40 / .6); }
        .shr-box { padding: 1.1rem 1.2rem 1.2rem; }
        .shr-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: .8rem; }
        .shr-h h2 { font-size: 1.05rem; font-weight: 800; }
        .shr-x { display: grid; place-items: center; width: 2rem; height: 2rem; border: 0; border-radius: .6rem; background: #edf0f5; font-size: 1.2rem; line-height: 1; cursor: pointer; color: #34435c; }
        .shr-canvas { display: block; width: 100%; height: auto; border-radius: .9rem; background: #0f1828; }
        .shr-note { margin-top: .7rem; font-size: .8rem; line-height: 1.5; color: #667794; }
        .shr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-top: .9rem; }
        .shr-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .65rem .5rem; white-space: nowrap; border: 1.5px solid #c5cfde; border-radius: .8rem; background: #fff; font: inherit; font-size: .86rem; font-weight: 800; color: #1f2a3d; cursor: pointer; text-decoration: none; }
        .shr-btn:hover { border-color: #3b6cd4; color: #2f58b3; }
        .shr-btn.is-main { grid-column: 1 / -1; border-color: #3b6cd4; background: #3b6cd4; color: #fff; }
        .shr-btn.is-main:hover { background: #2f58b3; color: #fff; }
        .shr-btn[hidden] { display: none; }
        .shr-btn[disabled], .shr-btn[aria-disabled='true'] { opacity: .5; pointer-events: none; }
        .shr-url { width: 100%; margin-top: .7rem; padding: .55rem .7rem; border: 1px solid #e2e6ec; border-radius: .7rem; background: #f3f5f8; font: inherit; font-size: .8rem; color: #4a5a73; }
        .shr-status { min-height: 1.2rem; margin-top: .55rem; font-size: .82rem; font-weight: 700; color: #2f58b3; }
        .shr-off { margin-top: .2rem; padding: 0; border: 0; background: none; font: inherit; font-size: .8rem; font-weight: 700; color: #b91c1c; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
    </style>
    <div class="shr-box">
        <div class="shr-h">
            <h2 id="shr-title">Bagikan hasilmu</h2>
            <button type="button" class="shr-x" data-share-close aria-label="Tutup">&times;</button>
        </div>
        <canvas class="shr-canvas" width="1080" height="1350" data-share-canvas role="img" aria-label="Pratinjau kartu hasil"></canvas>
        <p class="shr-note">Tautan hanya menampilkan skor dan nama samaranmu. Soal, jawaban, dan pembahasan tidak ikut terbagi.</p>
        <div class="shr-grid">
            <button type="button" class="shr-btn is-main" data-share-native hidden>Bagikan gambar &amp; tautan</button>
            <button type="button" class="shr-btn" data-share-download>Unduh gambar</button>
            <button type="button" class="shr-btn" data-share-copy disabled>Salin tautan</button>
        </div>
        <input class="shr-url" data-share-url readonly hidden aria-label="Tautan publik hasilmu">
        <p class="shr-status" data-share-status role="status"></p>
    </div>
    <script type="application/json" data-share-data>@json($shareData)</script>
    <script>
        (function () {
            var root = document.querySelector('[data-share]');
            var openBtn = document.querySelector('[data-share-open]');
            if (!root || !openBtn || typeof root.showModal !== 'function') { if (openBtn) openBtn.hidden = true; return; }

            var data = JSON.parse(root.querySelector('[data-share-data]').textContent);
            var canvas = root.querySelector('[data-share-canvas]');
            var ctx = canvas.getContext('2d');
            var $ = function (s) { return root.querySelector(s); };
            var copyBtn = $('[data-share-copy]');
            var nativeBtn = $('[data-share-native]'), urlBox = $('[data-share-url]'), statusEl = $('[data-share-status]');
            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            var shareUrl = null;
            var FONT = 'Poppins, "Plus Jakarta Sans", system-ui, sans-serif';
            var logo = new Image(), logoOk = false;
            logo.onload = function () { logoOk = true; if (root.open) draw(); };
            if (data.logo) logo.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data.logo);

            function say(msg) { statusEl.textContent = msg || ''; }
            function message() { return 'Skorku ' + data.pct + '% di simulasi ' + data.type + ' (' + data.brand + ')' + (shareUrl ? ' ' + shareUrl : ''); }

            function wrap(text, x, y, maxW, lineH, maxLines) {
                var words = String(text).split(/\s+/), line = '', lines = [];
                words.forEach(function (w) {
                    var test = line ? line + ' ' + w : w;
                    if (ctx.measureText(test).width > maxW && line) { lines.push(line); line = w; } else { line = test; }
                });
                if (line) lines.push(line);
                if (lines.length > maxLines) { lines = lines.slice(0, maxLines); lines[maxLines - 1] = lines[maxLines - 1].replace(/\s*\S*$/, '') + '...'; }
                lines.forEach(function (l, i) { ctx.fillText(l, x, y + i * lineH); });
            }

            function rr(x, y, w, h, r) {
                r = Math.min(r, h / 2, w / 2);
                ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
                ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
            }
            function spaced(px) { if ('letterSpacing' in ctx) ctx.letterSpacing = px + 'px'; }
            function fit(text, maxW) {
                text = String(text);
                if (ctx.measureText(text).width <= maxW) return text;
                while (text.length > 1 && ctx.measureText(text + '...').width > maxW) text = text.slice(0, -1);
                return text + '...';
            }

            function badge(text, x, cy, color, ok) {
                ctx.font = '700 27px ' + FONT;
                var w = ctx.measureText(text).width + 88, h = 60;
                ctx.fillStyle = color; rr(x, cy - h / 2, w, h, 12); ctx.fill();
                ctx.strokeStyle = '#fff'; ctx.lineWidth = 4.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.beginPath();
                if (ok) { ctx.moveTo(x + 22, cy + 1); ctx.lineTo(x + 29, cy + 8); ctx.lineTo(x + 41, cy - 7); }
                else { ctx.moveTo(x + 24, cy - 8); ctx.lineTo(x + 40, cy + 8); ctx.moveTo(x + 40, cy - 8); ctx.lineTo(x + 24, cy + 8); }
                ctx.stroke();
                ctx.fillStyle = '#fff'; ctx.textAlign = 'left'; ctx.fillText(text, x + 58, cy + 10);
                return w;
            }

            function draw() {
                var W = 1080, H = 1350, NAVY = '#16233b', INK = '#16233b', MUT = '#6b7a90', LINE = '#e3e8f0', ACC = '#3b6cd4';
                var pct = Math.max(0, Math.min(100, data.pct));
                ctx.clearRect(0, 0, W, H); ctx.shadowBlur = 0; ctx.shadowOffsetY = 0; ctx.textBaseline = 'alphabetic'; ctx.textAlign = 'left'; spaced(0);

                // latar terang + pita kepala navy
                ctx.fillStyle = '#eef1f6'; ctx.fillRect(0, 0, W, H);
                ctx.fillStyle = NAVY; ctx.fillRect(0, 0, W, 320);
                ctx.fillStyle = '#f0b429'; ctx.fillRect(0, 320, W, 4);

                // merek: logo asli situs + wordmark
                if (logoOk) { ctx.drawImage(logo, 60, 52, 62, 72); }
                ctx.fillStyle = '#fff'; ctx.font = '700 44px ' + FONT; ctx.fillText('Owl', 140, 98);
                var ow = ctx.measureText('Owl').width; ctx.fillStyle = '#7fa8e8'; ctx.fillText('spire', 140 + ow, 98);
                if (data.tagline) { ctx.fillStyle = '#9fb2d3'; ctx.font = '700 14px ' + FONT; spaced(3); ctx.fillText(String(data.tagline).toUpperCase(), 141, 126); spaced(0); }
                ctx.fillStyle = '#9fb2d3'; ctx.font = '500 23px ' + FONT; ctx.textAlign = 'right'; ctx.fillText(data.date, 1020, 98); ctx.textAlign = 'left';

                // judul (satu baris, mengecil bila panjang)
                ctx.fillStyle = '#f0b429'; ctx.font = '700 22px ' + FONT; spaced(4); ctx.fillText('HASIL SIMULASI', 60, 208); spaced(0);
                var fs = 60; ctx.font = '700 ' + fs + 'px ' + FONT;
                while (ctx.measureText(data.type).width > 960 && fs > 36) { fs -= 2; ctx.font = '700 ' + fs + 'px ' + FONT; }
                ctx.fillStyle = '#fff'; ctx.fillText(data.type, 60, 276);

                // kartu putih
                var segs = (data.segments || []).slice(0, 4), T = 360, L = 60, CW = 960, P = 56;
                var rowsTop = 954, cardBottom = segs.length ? rowsTop + (segs.length - 1) * 52 + 52 : 866;
                ctx.save(); ctx.shadowColor = 'rgba(15,24,40,.16)'; ctx.shadowBlur = 40; ctx.shadowOffsetY = 14;
                ctx.fillStyle = '#fff'; rr(L, T, CW, cardBottom - T, 26); ctx.fill(); ctx.restore();

                // cincin skor (datar, satu warna)
                var cx = L + P + 128, cy = T + P + 130, r = 104, lw = 24, a0 = -Math.PI / 2;
                ctx.lineWidth = lw; ctx.lineCap = 'round'; ctx.strokeStyle = '#e8edf5'; ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2); ctx.stroke();
                if (pct > 0) { ctx.strokeStyle = ACC; ctx.beginPath(); ctx.arc(cx, cy, r, a0, a0 + Math.PI * 2 * pct / 100); ctx.stroke(); }
                var num = String(pct); ctx.font = '800 70px ' + FONT; var nw = ctx.measureText(num).width; ctx.font = '700 32px ' + FONT; var pw = ctx.measureText('%').width;
                var sx = cx - (nw + pw + 3) / 2;
                ctx.fillStyle = INK; ctx.font = '800 70px ' + FONT; ctx.fillText(num, sx, cy + 20);
                ctx.fillStyle = MUT; ctx.font = '700 32px ' + FONT; ctx.fillText('%', sx + nw + 3, cy + 20);
                ctx.textAlign = 'center'; ctx.fillStyle = MUT; ctx.font = '600 19px ' + FONT; ctx.fillText('capaian', cx, cy + 52); ctx.textAlign = 'left';

                // skor akhir + status
                var ix = cx + r + lw / 2 + 56;
                ctx.fillStyle = MUT; ctx.font = '700 21px ' + FONT; spaced(4); ctx.fillText('SKOR AKHIR', ix, cy - 62); spaced(0);
                ctx.fillStyle = INK; ctx.font = '800 100px ' + FONT; var sw = ctx.measureText(String(data.score)).width; ctx.fillText(String(data.score), ix, cy + 22);
                ctx.fillStyle = MUT; ctx.font = '600 34px ' + FONT; ctx.fillText('/ ' + data.max + ' poin', ix + sw + 16, cy + 22);
                if (data.full) { badge(data.passed ? 'Lulus passing grade' : 'Belum lulus passing grade', ix, cy + 78, data.passed ? '#1f9d68' : '#d9480f', data.passed); }
                else { badge('Mode coba', ix, cy + 78, ACC, true); }

                // benar / salah / kosong
                var sy = 736; ctx.strokeStyle = LINE; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(L + P, sy - 30); ctx.lineTo(L + CW - P, sy - 30); ctx.stroke();
                var colW = (CW - P * 2) / 3;
                [['BENAR', data.benar, '#1f9d68'], ['SALAH', data.salah, '#d9480f'], ['KOSONG', data.kosong, '#6b7a90']].forEach(function (c, k) {
                    var bx = L + P + k * colW + (k ? 36 : 0);
                    if (k) { ctx.strokeStyle = LINE; ctx.beginPath(); ctx.moveTo(L + P + k * colW, sy + 2); ctx.lineTo(L + P + k * colW, sy + 108); ctx.stroke(); }
                    ctx.fillStyle = MUT; ctx.font = '700 20px ' + FONT; spaced(3); ctx.fillText(c[0], bx, sy + 28); spaced(0);
                    ctx.fillStyle = c[2]; ctx.font = '800 64px ' + FONT; ctx.fillText(String(c[1]), bx, sy + 100);
                });
                ctx.strokeStyle = LINE; ctx.beginPath(); ctx.moveTo(L + P, sy + 134); ctx.lineTo(L + CW - P, sy + 134); ctx.stroke();

                // skor per bagian
                if (segs.length) {
                    ctx.fillStyle = MUT; ctx.font = '700 20px ' + FONT; spaced(3); ctx.fillText('SKOR PER BAGIAN', L + P, sy + 180); spaced(0);
                    segs.forEach(function (sg, k) {
                        var yy = rowsTop + k * 52, bx = L + P + 250, bw = CW - P * 2 - 250 - 100, pc = Math.max(0, Math.min(100, sg.p));
                        ctx.textAlign = 'left'; ctx.fillStyle = INK; ctx.font = '600 25px ' + FONT; ctx.fillText(fit(sg.n, 230), L + P, yy + 8);
                        ctx.fillStyle = '#e8edf5'; rr(bx, yy - 8, bw, 16, 8); ctx.fill();
                        ctx.fillStyle = pc >= 75 ? '#1f9d68' : (pc >= 50 ? '#d99a1b' : '#d9480f'); if (pc > 0) { rr(bx, yy - 8, Math.max(16, bw * pc / 100), 16, 8); ctx.fill(); }
                        ctx.textAlign = 'right'; ctx.fillStyle = INK; ctx.font = '700 25px ' + FONT; ctx.fillText(pc + '%', L + CW - P, yy + 8);
                    });
                    ctx.textAlign = 'left';
                }

                // kaki navy
                ctx.fillStyle = NAVY; ctx.fillRect(0, 1240, W, 110);
                ctx.fillStyle = '#f0b429'; ctx.fillRect(0, 1240, W, 4);
                ctx.fillStyle = '#9fb2d3'; ctx.font = '500 25px ' + FONT; ctx.textAlign = 'left'; ctx.fillText('Coba simulasinya di', 60, 1306);
                ctx.fillStyle = '#fff'; ctx.font = '700 29px ' + FONT; ctx.textAlign = 'right'; ctx.fillText(location.host, 1020, 1306); ctx.textAlign = 'left';
            }

            function toBlob(cb) { canvas.toBlob(function (b) { if (b) cb(b); else say('Gambar belum bisa dibuat di browser ini.'); }, 'image/png'); }

            function setLink(url) {
                shareUrl = url; if (url) say('');
                urlBox.value = url || '';
                urlBox.placeholder = url ? '' : 'Tautan dimatikan';
                copyBtn.disabled = !url;
            }

            function call(method, url) {
                return fetch(url, { method: method, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); });
            }

            function open() {
                root.showModal();
                setLink(null); say('Membuat tautan...');
                (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(draw);
                draw();
                call('POST', data.storeUrl).then(function (j) { setLink(j.url); }).catch(function () {
                                        say('Tautan belum bisa dibuat. Kamu tetap bisa mengunduh gambarnya.');
                });
                try {
                    var probe = new File(['x'], 'a.png', { type: 'image/png' });
                    nativeBtn.hidden = !(navigator.share && navigator.canShare && navigator.canShare({ files: [probe] }));
                } catch (e) { nativeBtn.hidden = true; }
            }

            openBtn.addEventListener('click', open);
            root.querySelector('[data-share-close]').addEventListener('click', function () { root.close(); });
            root.addEventListener('click', function (e) { if (e.target === root) root.close(); });

            $('[data-share-download]').addEventListener('click', function () {
                toBlob(function (b) {
                    var a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = 'hasil-simulasi.png';
                    document.body.appendChild(a); a.click(); a.remove(); setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
                });
            });

            nativeBtn.addEventListener('click', function () {
                toBlob(function (b) {
                    var file = new File([b], 'hasil-simulasi.png', { type: 'image/png' });
                    navigator.share({ files: [file], text: message() }).catch(function () {});
                });
            });

            copyBtn.addEventListener('click', function () {
                if (!shareUrl) return;
                var done = function () { say('Tautan disalin.'); };
                if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(shareUrl).then(done, function () { urlBox.hidden = false; urlBox.select(); say('Tekan Ctrl+C untuk menyalin.'); }); }
                else { urlBox.hidden = false; urlBox.select(); say('Tekan Ctrl+C untuk menyalin.'); }
            });
        })();
    </script>
</dialog>
