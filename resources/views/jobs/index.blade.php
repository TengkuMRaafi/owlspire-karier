@extends('layouts.app')

@section('title', 'Lowongan')
@section('description', 'Cari lowongan dari LinkedIn, Jobicy, RemoteOK, dan Arbeitnow sekaligus, lengkap dengan persen kecocokan skill.')

@section('content')
    @php $loginUrl = Route::has('login') ? route('login') : route('dashboard.index'); @endphp
    {{-- ============ Hero: owl mencari lowongan ============ --}}
    <section class="lw-hero">
        <div class="lw-hero-grid mx-auto max-w-6xl px-4">
            <div>
                <p class="lw-eyebrow">Lowongan</p>
                <h1 class="font-display lw-title">Satu pencarian, <br><em>banyak sumber.</em></h1>
                <p class="lw-lead">Isi posisi dan skill, kami cari di LinkedIn, Jobicy, RemoteOK, dan Arbeitnow lalu mengurutkannya berdasarkan persen kecocokan.</p>
                <x-telegram-link class="btn-primary btn-sm lw-tg" label="Cari lewat bot Telegram" style="margin-top:.9rem" />
            </div>
            <div class="lw-scene" aria-hidden="true">
                <span class="lw-glow"></span>
                <span class="lw-disc"></span>
                <span class="lw-ring lw-r1"></span><span class="lw-ring lw-r2"></span>
                <span class="lw-ping lw-p1"></span><span class="lw-ping lw-p2"></span>
                <span class="lw-sweep"></span>
                @foreach ([['LinkedIn', 'left: 38%; top: 0;'], ['Jobicy', 'right: -3%; top: 50%;'], ['Arbeitnow', 'left: 52%; bottom: 0;'], ['RemoteOK', 'left: -3%; top: 47%;']] as $k => [$src, $at])
                    <span class="lw-src" style="{{ $at }} --f: {{ $k * .7 }}s"><i></i>{{ $src }}</span>
                @endforeach
                <span class="lw-sp lw-sp1"></span><span class="lw-sp lw-sp2"></span><span class="lw-sp lw-sp3"></span><span class="lw-sp lw-sp4"></span>
                <div class="lw-owl"><x-owl :animated="true" /></div>
                <div class="lw-lens"><span>
                    <svg viewBox="0 0 48 48"><circle cx="20" cy="20" r="13" class="lw-lens-glass"/><path d="M13 16a8 8 0 0 1 7-5" class="lw-lens-shine"/><path d="m30 30 12 12" class="lw-lens-grip"/></svg>
                </span></div>
                @foreach ([
                    ['Data Analyst', 'Jakarta · Kerja', 92, 'D', 'left: 0; top: 9%;'],
                    ['UI/UX Designer', 'Remote · Kerja', 87, 'U', 'right: 0; top: 20%;'],
                    ['Staf Administrasi', 'Bandung · Kerja', 81, 'S', 'right: 3%; bottom: 7%;'],
                    ['Content Writer', 'Remote · Magang', 76, 'C', 'left: 1%; bottom: 17%;'],
                ] as $i => [$role, $meta, $pct, $ini, $pos])
                    <div class="lw-card" style="{{ $pos }} --d: {{ $i * 2 }}s; --p: {{ $pct }}">
                        <span class="lw-card-ic">{{ $ini }}</span>
                        <span class="lw-card-t"><b>{{ $role }}</b><small>{{ $meta }}</small></span>
                        <span class="lw-card-m"><b>{{ $pct }}%</b></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="cari" class="lw-wrap mx-auto max-w-6xl px-4">
        <div class="lw-ws">
            {{-- ============ Kiri: form pencarian ============ --}}
            <form class="lw-side" data-lw-form autocomplete="off">
                <button type="button" class="lw-side-toggle" data-lw-toggle aria-expanded="true">
                    <span><b>Pencarian</b><small data-lw-sum>Isi posisi dan skill</small></span>
                    <i aria-hidden="true">Ubah</i>
                </button>

                <div class="lw-side-body">
                    <h2 class="lw-side-h font-display">Cari lowongan</h2>

                    <div class="lw-field" data-tour="pos">
                        <label for="lw-pos">Posisi yang dicari</label>
                        <div class="lw-tags" data-tags="positions" data-max="5"><input id="lw-pos" type="text" placeholder="mis. Data Analyst" enterkeyhint="done"></div>
                    </div>
                    <div class="lw-field" data-tour="skills">
                        <label for="lw-skill">Skill kamu</label>
                        <div class="lw-tags" data-tags="skills" data-max="25"><input id="lw-skill" type="text" placeholder="mis. python, excel" enterkeyhint="done"></div>
                    </div>
                    <div class="lw-field" data-tour="loc">
                        <label for="lw-loc">Lokasi</label>
                        <div class="lw-tags" data-tags="locations" data-max="3" data-init='["Indonesia"]'><input id="lw-loc" type="text" placeholder="mis. Jakarta, Remote" enterkeyhint="done"></div>
                    </div>
                    <p class="lw-hint">Enter atau koma untuk menambah.</p>

                    <div class="lw-row2">
                        <fieldset class="lw-field" data-tour="kind"><legend>Jenis</legend>
                            <div class="lw-seg">
                                <label><input type="radio" name="kind" value="kerja" checked><span>Kerja</span></label>
                                <label><input type="radio" name="kind" value="magang"><span>Magang</span></label>
                            </div>
                        </fieldset>
                        <fieldset class="lw-field" data-tour="hours"><legend>Diposting</legend>
                            <div class="lw-seg">
                                <label><input type="radio" name="hours" value="24" checked><span>24 jam</span></label>
                                <label><input type="radio" name="hours" value="168"><span>7 hari</span></label>
                                <label><input type="radio" name="hours" value="720"><span>30 hari</span></label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="lw-field lw-dd" data-tour="src" data-lw-dd>
                        <button type="button" class="lw-dd-btn" aria-haspopup="true" aria-expanded="false" aria-label="Pilih sumber lowongan">
                            <b>Sumber</b><span data-lw-src-sum>LinkedIn</span><i aria-hidden="true"></i>
                        </button>
                        <div class="lw-dd-pop" hidden>
                            @foreach ($sources as $s)
                                <label @class(['is-off' => ! $s['available']]) title="{{ $s['note'] }}{{ $s['available'] ? '' : ' (butuh API key)' }}">
                                    <input type="checkbox" name="sources" value="{{ $s['id'] }}" @checked($s['default'] && $s['available']) @disabled(! $s['available'])>
                                    <span>{{ $s['label'] }}</span>
                                    @unless ($s['available'])<small>butuh API key</small>@endunless
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="lw-go" data-tour="go">
                    <p class="lw-err" data-lw-err role="alert" hidden></p>
                    <button type="submit" class="btn-primary lw-submit" data-lw-submit>Cari lowongan</button>
                    <small>LinkedIn bisa memakan 1-2 menit.</small>
                </div>
            </form>

            {{-- ============ Kanan: hasil (tinggi tetap, dipaginasi) ============ --}}
            <div class="lw-main" data-lw-panel data-tour="panel" aria-live="polite">
                <div class="lw-idle" data-lw-idle>
                    <p class="lw-idle-t">Hasil pencarian tampil di sini</p>
                    <ol class="lw-mini">
                        <li><b>1</b>Isi posisi &amp; skill</li>
                        <li><b>2</b>Pilih sumber</li>
                        <li><b>3</b>Lihat skor kecocokan</li>
                    </ol>
                    <button type="button" class="lw-help" data-lw-tour-start>Lihat panduan</button>
                </div>
                <div class="lw-prog" data-lw-prog hidden>
                    <div class="lw-prog-in">
                        <p data-lw-msg>Memulai...</p>
                        <span class="lw-bar"><i data-lw-bar></i></span>
                    </div>
                </div>
                <div class="lw-out" data-lw-out hidden></div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            var form = document.querySelector('[data-lw-form]');
            if (!form) return;
            var URLS = { search: @json(route('jobs.search')), status: @json(route('jobs.status', '__ID__')), login: @json($loginUrl), cv: @json(route('cv.index')) };
            var GUEST = @json($guest);
            var TRACK_URL = @json($guest ? null : route('account.applications.store'));
            var CSRF = document.querySelector('meta[name=csrf-token]').content;
            var JSONH = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF };
            var $ = function (s, r) { return (r || document).querySelector(s); };
            var tags = {};

            /* ---- input tag ---- */
            [].forEach.call(form.querySelectorAll('[data-tags]'), function (box) {
                var name = box.dataset.tags, max = +box.dataset.max, input = $('input', box), list = [];
                function draw() {
                    [].forEach.call(box.querySelectorAll('.lw-tag'), function (t) { t.remove(); });
                    list.forEach(function (v, i) {
                        var t = document.createElement('span'); t.className = 'lw-tag'; t.textContent = v;
                        var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Hapus ' + v); b.textContent = '×';
                        b.onclick = function () { list.splice(i, 1); draw(); };
                        t.appendChild(b); box.insertBefore(t, input);
                    });
                }
                function add(raw) {
                    raw.split(/[,;\n]/).forEach(function (v) {
                        v = v.trim().slice(0, 60);
                        if (v && list.length < max && !list.some(function (x) { return x.toLowerCase() === v.toLowerCase(); })) list.push(v);
                    });
                    draw();
                }
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(input.value); input.value = ''; }
                    else if (e.key === 'Backspace' && !input.value && list.length) { list.pop(); draw(); }
                });
                input.addEventListener('blur', function () { if (input.value.trim()) { add(input.value); input.value = ''; } });
                input.addEventListener('paste', function (e) { var t = (e.clipboardData || window.clipboardData).getData('text'); if (/[,;\n]/.test(t)) { e.preventDefault(); add(t); } });
                box.addEventListener('click', function () { input.focus(); });
                tags[name] = { get: function () { return list.slice(); }, set: function (arr) { list = []; add((arr || []).join(',')); }, add: add };
                if (box.dataset.init) add(JSON.parse(box.dataset.init).join(','));
            });

            /* ---- form dilipat di layar kecil ---- */
            var toggle = $('[data-lw-toggle]'), sumTxt = $('[data-lw-sum]');
            function setClosed(c) { form.classList.toggle('is-closed', c); toggle.setAttribute('aria-expanded', c ? 'false' : 'true'); }
            toggle.addEventListener('click', function () { setClosed(!form.classList.contains('is-closed')); });

            /* ---- sumber: dropdown satu baris ---- */
            var dd = $('[data-lw-dd]'), ddBtn = $('.lw-dd-btn', dd), ddPop = $('.lw-dd-pop', dd), ddSum = $('[data-lw-src-sum]');
            function srcSum() {
                var n = [].map.call(form.querySelectorAll('[name=sources]:checked'), function (c) { return c.parentNode.textContent.replace(/butuh API key/, '').trim(); });
                ddSum.textContent = !n.length ? 'Pilih sumber' : n.slice(0, 2).join(', ') + (n.length > 2 ? ' +' + (n.length - 2) : '');
            }
            function ddOpen(o) {
                ddPop.hidden = !o; ddBtn.setAttribute('aria-expanded', o ? 'true' : 'false');
                if (!o) return;
                var r = ddBtn.getBoundingClientRect(), h = ddPop.offsetHeight;
                ddPop.style.left = r.left + 'px'; ddPop.style.width = r.width + 'px';
                ddPop.style.top = (window.innerHeight - r.bottom >= h + 12 ? r.bottom + 4 : Math.max(8, r.top - h - 4)) + 'px';
            }
            ddBtn.addEventListener('click', function (e) { e.stopPropagation(); ddOpen(ddPop.hidden); });
            document.addEventListener('click', function (e) { if (!ddPop.hidden && !ddPop.contains(e.target)) ddOpen(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !ddPop.hidden) ddOpen(false); });
            window.addEventListener('resize', function () { ddOpen(false); });
            window.addEventListener('scroll', function () { if (!ddPop.hidden) ddOpen(false); }, true);
            form.addEventListener('change', function (e) { if (e.target.name === 'sources') srcSum(); });
            srcSum();

            /* ---- pencarian ---- */
            var err = $('[data-lw-err]'), btn = $('[data-lw-submit]'), idle = $('[data-lw-idle]'), prog = $('[data-lw-prog]'), out = $('[data-lw-out]'), panel = $('[data-lw-panel]');
            var timer, resizeT, redraw = null;
            function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
            function showErr(t) { err.hidden = !t; err.textContent = t || ''; }
            function small() { return window.innerWidth < 960; }

            form.addEventListener('submit', function (e) {
                e.preventDefault(); showErr('');
                var body = {
                    positions: tags.positions.get(), skills: tags.skills.get(), locations: tags.locations.get(),
                    kind: form.kind.value, hours: form.hours.value,
                    sources: [].map.call(form.querySelectorAll('[name=sources]:checked'), function (c) { return c.value; })
                };
                if (!body.positions.length && !body.skills.length) return showErr('Isi minimal satu skill atau posisi.');
                if (!body.sources.length) return showErr('Pilih minimal satu sumber.');
                btn.disabled = true; btn.textContent = 'Mencari...';
                var label = (body.positions.length ? body.positions : body.skills).slice(0, 3).join(', ');
                sumTxt.textContent = label + ' · ' + (body.locations.join(', ') || 'Indonesia');
                idle.hidden = true; out.hidden = true; prog.hidden = false; redraw = null; setProg('Memulai...', 3);
                if (small()) { setClosed(true); panel.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                fetch(URLS.search, { method: 'POST', headers: Object.assign({ 'Content-Type': 'application/json' }, JSONH), body: JSON.stringify(body) })
                    .then(function (r) { return r.json().then(function (d) { return [r.ok, d]; }); })
                    .then(function (x) {
                        if (!x[0]) throw new Error(x[1].error || (x[1].errors && Object.values(x[1].errors)[0][0]) || 'Gagal memulai pencarian.');
                        poll(x[1].id, 0);
                    })
                    .catch(function (e) { stop(); showErr(e.message); idle.hidden = false; prog.hidden = true; setClosed(false); });
            });
            function setProg(m, p) { $('[data-lw-msg]').textContent = m; $('[data-lw-bar]').style.width = Math.max(3, p) + '%'; }
            function stop() { btn.disabled = false; btn.textContent = 'Cari lowongan'; clearTimeout(timer); }
            function fail(t) { stop(); prog.hidden = true; idle.hidden = false; showErr(t); setClosed(false); }
            function poll(id, n) {
                fetch(URLS.status.replace('__ID__', id), { headers: JSONH }).then(function (r) { return r.json(); }).then(function (s) {
                    if (s.status === 'running') {
                        setProg(s.msg || 'Mencari...', s.pct || 3);
                        if (n > 400) return fail('Pencarian terlalu lama. Coba lagi dengan sumber lebih sedikit.');
                        timer = setTimeout(function () { poll(id, n + 1); }, 1500);
                    } else if (s.status === 'done') { stop(); prog.hidden = true; render(s); }
                    else fail(s.msg || 'Pencarian gagal.');
                }).catch(function () { timer = setTimeout(function () { poll(id, n + 1); }, 2500); });
            }

            /* ---- tampilan hasil: tab + kartu dipaginasi ---- */
            function safeUrl(u) { return /^https?:\/\//i.test(u) ? u : '#'; }
            function render(s) {
                out.textContent = ''; out.hidden = false;
                var a = s.insights, min = a.min_match, start = 0, PAGE = 6;

                /* kepala */
                var head = el('div', 'lw-rhead'), tt = el('div', 'lw-rtitle');
                tt.appendChild(el('h3', 'font-display', a.shown ? a.shown + ' lowongan cocok' : 'Belum ada yang cocok'));
                tt.appendChild(el('p', '', 'dari ' + a.found + ' ditemukan'));
                var tiers = el('ul', 'lw-tiers');
                [['hi', 'Sangat cocok'], ['mid', 'Cukup'], ['low', 'Kurang']].forEach(function (t) {
                    var li = el('li', t[0] === 'hi' ? 'is-hi' : ''); li.appendChild(el('b', '', a.tiers[t[0]])); li.appendChild(document.createTextNode(t[1])); tiers.appendChild(li);
                });
                head.appendChild(tt); head.appendChild(tiers); out.appendChild(head);

                var tabs = el('div', 'lw-tabs'); tabs.setAttribute('role', 'tablist');
                var tL = el('button', 'is-on', 'Lowongan'), tS = el('button', '', 'Ringkasan');
                [tL, tS].forEach(function (b) { b.type = 'button'; b.setAttribute('role', 'tab'); tabs.appendChild(b); });
                out.appendChild(tabs);

                /* tab lowongan */
                var pL = el('div'), tool = el('div', 'lw-tool'), lab = el('label'), cb = el('input');
                cb.type = 'checkbox'; cb.checked = true; lab.appendChild(cb); lab.appendChild(document.createTextNode(' Hanya yang cocok (≥' + min + '%)'));
                var count = el('span'); tool.appendChild(lab); tool.appendChild(count); pL.appendChild(tool);
                var list = el('div', 'lw-list'), pager = el('nav', 'lw-pager'); pager.setAttribute('aria-label', 'Halaman hasil');
                pL.appendChild(list); pL.appendChild(pager);

                /* tawaran analisis CV */
                var offer = el('aside', 'lw-offer');
                var ot = el('div'); ot.appendChild(el('b', '', 'Coba analisis CV-mu?')); ot.appendChild(el('p', '', 'Kami baca skill dari CV-mu, lalu tunjukkan skill apa yang masih kurang untuk lowongan-lowongan ini.'));
                var oa = el('a', 'btn-outline', 'Analisis CV-ku'); oa.href = URLS.cv;
                offer.appendChild(ot); offer.appendChild(oa); pL.appendChild(offer);
                out.appendChild(pL);

                /* tab ringkasan */
                var pS = el('div', 'lw-sumtab'); pS.hidden = true;
                function note(label, text) { var p = el('p', 'lw-note'); p.appendChild(el('b', '', label)); p.appendChild(document.createTextNode(text)); pS.appendChild(p); }
                if (a.used.length) note('Skill terpakai', a.used.slice(0, 6).map(function (x) { return x[0] + ' (' + x[1] + ')'; }).join(', '));
                if (a.unused.length && a.used.length) note('Belum muncul', a.unused.slice(0, 5).join(', '));
                if (a.gap.length) note('Sering diminta, belum ada di profilmu', a.gap.map(function (x) { return x[0] + ' (' + x[1] + ')'; }).join(', '));
                if (a.locations.length) note('Terbanyak di', a.locations.map(function (x) { return x[0] + ' (' + x[1] + ')'; }).join(', '));
                if (a.tips.length) { var ul = el('ul', 'lw-tips'); a.tips.forEach(function (t) { ul.appendChild(el('li', '', t)); }); pS.appendChild(ul); }
                if (s.warnings && s.warnings.length) pS.appendChild(el('p', 'lw-warn', 'Catatan: ' + s.warnings.join(' · ')));
                if (s.links && s.links.length) {
                    var l = el('div', 'lw-links'); l.appendChild(el('span', '', 'Cari langsung di:'));
                    s.links.forEach(function (x) { var k = el('a', '', x[0]); k.href = safeUrl(x[1]); k.target = '_blank'; k.rel = 'noopener'; l.appendChild(k); });
                    pS.appendChild(l);
                }
                if (!pS.children.length) pS.appendChild(el('p', 'lw-note', 'Belum ada ringkasan untuk pencarian ini.'));
                out.appendChild(pS);

                function show(which) {
                    var isL = which === 'list';
                    pL.hidden = !isL; pS.hidden = isL;
                    tL.classList.toggle('is-on', isL); tS.classList.toggle('is-on', !isL);
                    tL.setAttribute('aria-selected', isL); tS.setAttribute('aria-selected', !isL);
                    if (isL) draw();
                }
                tL.addEventListener('click', function () { show('list'); }); tS.addEventListener('click', function () { show('sum'); });

                /* paginasi: jumlah kartu per halaman menyesuaikan kolom yang muat (2-3 baris, tidak memanjang) */
                function rows() { return cb.checked ? s.results.filter(function (j) { return j.match >= min; }) : s.results; }
                function pageSize() {
                    var rem = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16, w = list.clientWidth || pL.clientWidth || 600, gap = .9 * rem;
                    var cols = Math.max(1, Math.floor((w + gap) / (17 * rem + gap)));
                    return cols * (cols > 1 ? 3 : 4);
                }
                function go(page) {
                    start = page * PAGE; draw();
                    if (panel.getBoundingClientRect().top < 70) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                function drawPager(total) {
                    pager.textContent = ''; var pages = Math.ceil(total / PAGE), cur = Math.floor(start / PAGE);
                    pager.hidden = pages <= 1; if (pages <= 1) return;
                    function btnP(label, page, opts) {
                        var b = el('button', opts && opts.cur ? 'is-cur' : '', label); b.type = 'button';
                        if (opts && opts.aria) b.setAttribute('aria-label', opts.aria);
                        if (opts && opts.cur) b.setAttribute('aria-current', 'page');
                        if (opts && opts.off) b.disabled = true; else b.addEventListener('click', function () { go(page); });
                        pager.appendChild(b);
                    }
                    btnP('‹', cur - 1, { aria: 'Halaman sebelumnya', off: cur === 0 });
                    var last = -1;
                    for (var p = 0; p < pages; p++) {
                        if (p === 0 || p === pages - 1 || Math.abs(p - cur) <= 1) {
                            if (last !== -1 && p - last > 1) pager.appendChild(el('span', '', '…'));
                            btnP(String(p + 1), p, { cur: p === cur, aria: 'Halaman ' + (p + 1) }); last = p;
                        }
                    }
                    btnP('›', cur + 1, { aria: 'Halaman berikutnya', off: cur >= pages - 1 });
                }
                function draw() {
                    var r = rows(); PAGE = pageSize();
                    if (start >= r.length) start = 0;
                    start = Math.floor(start / PAGE) * PAGE;
                    list.textContent = '';
                    r.slice(start, start + PAGE).forEach(function (j, i) { list.appendChild(card(j, i)); });
                    if (!r.length) list.appendChild(el('div', 'lw-empty', 'Belum ada lowongan yang cocok. Coba perluas rentang waktu, tambah skill, atau matikan filter kecocokan.'));
                    count.textContent = !r.length ? '' : ('Menampilkan ' + (start + 1) + '–' + Math.min(r.length, start + PAGE) + ' dari ' + r.length);
                    drawPager(r.length);
                }
                cb.addEventListener('change', function () { start = 0; draw(); });
                redraw = function () { if (!pL.hidden) draw(); };
                draw();
                if (small()) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            window.addEventListener('resize', function () { clearTimeout(resizeT); resizeT = setTimeout(function () { if (redraw) redraw(); }, 150); });

            /* ---- panduan: zoom ke tiap kolom sampai selesai ---- */
            var TOUR_KEY = 'lw-tour-v1', tourOn = false;
            function tourSeen() { try { return localStorage.getItem(TOUR_KEY) === '1'; } catch (e) { return false; } }
            function tourMark() { try { localStorage.setItem(TOUR_KEY, '1'); } catch (e) { } }
            var STEPS = [
                ['pos', 'Posisi yang dicari', 'Ketik posisi, lalu tekan Enter atau koma untuk menambah. Boleh lebih dari satu.'],
                ['skills', 'Skill kamu', 'Tambahkan skill-mu. Makin lengkap, makin akurat persen kecocokannya.'],
                ['loc', 'Lokasi', 'Isi kota atau "Remote". Bawaannya seluruh Indonesia.'],
                ['kind', 'Jenis', 'Pilih mau cari pekerjaan atau magang.'],
                ['hours', 'Diposting', 'Batasi seberapa baru lowongannya: 24 jam, 7 hari, atau 30 hari.'],
                ['src', 'Sumber', 'Klik untuk memilih situs yang dicari sekaligus. Yang bertanda butuh API key belum aktif.'],
                ['go', 'Mulai mencari', 'Klik tombol ini. LinkedIn bisa memakan 1-2 menit.'],
                ['panel', 'Hasil muncul di sini', 'Lowongan diurutkan dari yang paling cocok, dibagi per halaman. Tab Ringkasan berisi skill yang sering diminta dan tips.']
            ];
            function startTour() {
                if (tourOn) return; tourOn = true;
                var steps = STEPS.map(function (x) { return { el: document.querySelector('[data-tour="' + x[0] + '"]'), t: x[1], d: x[2] }; }).filter(function (x) { return x.el; });
                if (!steps.length) { tourOn = false; return; }
                setClosed(false);
                var i = 0, root = el('div', 'lw-tour'), hole = el('div', 'lw-tour-hole'), tip = el('div', 'lw-tip');
                tip.setAttribute('role', 'dialog'); tip.setAttribute('aria-label', 'Panduan pencarian lowongan');
                root.appendChild(hole); root.appendChild(tip); document.body.appendChild(root);

                function put(b) { hole.style.left = b.l + 'px'; hole.style.top = b.t + 'px'; hole.style.width = b.w + 'px'; hole.style.height = b.h + 'px'; }
                function place() {
                    var r = steps[i].el.getBoundingClientRect(), pad = 8, b = { l: r.left - pad, t: r.top - pad, w: r.width + pad * 2, h: r.height + pad * 2 };
                    put(b);
                    var tw = tip.offsetWidth, th = tip.offsetHeight, vw = window.innerWidth, vh = window.innerHeight, m = 12, x, y;
                    if (b.h > vh * .6) { x = b.l + (b.w - tw) / 2; y = b.t + (b.h - th) / 2; }
                    else if (vw - (b.l + b.w) >= tw + m * 2) { x = b.l + b.w + m; y = b.t; }
                    else if (vh - (b.t + b.h) >= th + m * 2) { x = b.l; y = b.t + b.h + m; }
                    else if (b.t >= th + m * 2) { x = b.l; y = b.t - th - m; }
                    else { x = (vw - tw) / 2; y = vh - th - m; }
                    tip.style.left = Math.max(m, Math.min(x, vw - tw - m)) + 'px';
                    tip.style.top = Math.max(m, Math.min(y, vh - th - m)) + 'px';
                }
                function fill() {
                    tip.textContent = '';
                    tip.appendChild(el('small', '', 'Langkah ' + (i + 1) + ' dari ' + steps.length));
                    tip.appendChild(el('h3', '', steps[i].t)); tip.appendChild(el('p', '', steps[i].d));
                    var dots = el('div', 'lw-tip-dots'); steps.forEach(function (_, k) { dots.appendChild(el('i', k === i ? 'is-on' : '')); }); tip.appendChild(dots);
                    var nav = el('div', 'lw-tip-nav'), skip = el('button', 'lw-tip-skip', i === steps.length - 1 ? '' : 'Lewati'); skip.type = 'button'; skip.addEventListener('click', end);
                    var bt = el('div', 'lw-tip-btns');
                    if (i > 0) { var pv = el('button', 'btn-outline btn-sm', 'Kembali'); pv.type = 'button'; pv.addEventListener('click', function () { go(i - 1); }); bt.appendChild(pv); }
                    var nx = el('button', 'btn-primary btn-sm', i === steps.length - 1 ? 'Selesai' : 'Lanjut'); nx.type = 'button'; nx.addEventListener('click', next); bt.appendChild(nx);
                    nav.appendChild(skip); nav.appendChild(bt); tip.appendChild(nav); nx.focus({ preventScroll: true });
                }
                function go(n) {
                    i = n; fill();
                    steps[i].el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
                    requestAnimationFrame(place);
                }
                function next() { if (i >= steps.length - 1) end(); else go(i + 1); }
                function key(e) {
                    if (e.key === 'Escape') end();
                    else if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); next(); }
                    else if (e.key === 'ArrowLeft' && i > 0) go(i - 1);
                }
                function end() {
                    tourOn = false; tourMark(); root.remove();
                    document.removeEventListener('keydown', key); window.removeEventListener('resize', place); window.removeEventListener('scroll', place, true);
                    var f = document.getElementById('lw-pos'); if (f) f.focus({ preventScroll: true });
                }
                document.addEventListener('keydown', key); window.addEventListener('resize', place); window.addEventListener('scroll', place, true);

                /* mulai dari seluruh layar, lalu mengecil (zoom) ke kolom pertama */
                hole.style.transition = 'none'; put({ l: 0, t: 0, w: window.innerWidth, h: window.innerHeight }); void hole.offsetWidth; hole.style.transition = '';
                go(0);
            }
            [].forEach.call(document.querySelectorAll('[data-lw-tour-start]'), function (b) { b.addEventListener('click', startTour); });
            if (!tourSeen()) setTimeout(startTour, 700);

            function card(j, i) {
                var c = el('article', 'lw-job'); c.style.setProperty('--i', i);
                var logo = el('div', 'lw-logo');
                if (/^https?:\/\//i.test(j.logo || '')) { var im = el('img'); im.src = j.logo; im.alt = ''; im.loading = 'lazy'; im.referrerPolicy = 'no-referrer'; im.onerror = function () { im.remove(); logo.textContent = (j.company || '?').charAt(0).toUpperCase(); }; logo.appendChild(im); }
                else logo.textContent = (j.company || '?').charAt(0).toUpperCase();
                var mid = el('div'); mid.appendChild(el('h4', '', j.title)); mid.firstChild.title = j.title;
                var co = el('p', 'lw-co', j.company + ' · ' + j.location); co.title = co.textContent; mid.appendChild(co);
                var metaTxt = [j.posted, j.job_type, j.seniority, 'via ' + (j.sources || [j.source]).join(', ')].filter(Boolean).join(' · ');
                var meta = el('p', 'lw-meta', metaTxt); meta.title = metaTxt; mid.appendChild(meta);
                var score = el('div', 'lw-score'); score.style.setProperty('--p', j.match); score.title = 'Kecocokan ' + j.match + '%'; score.appendChild(el('b', '', j.match + '%'));
                c.appendChild(logo); c.appendChild(mid); c.appendChild(score);

                var foot = el('div', 'lw-foot'), sk = el('div', 'lw-skills');
                var have = (j.matched || []), rel = have.length ? [] : (j.related || []);
                have.slice(0, 3).forEach(function (x) { sk.appendChild(el('span', '', x)); });
                rel.slice(0, 3).forEach(function (x) { sk.appendChild(el('span', 'is-rel', x)); });
                if (have.length > 3) sk.appendChild(el('span', '', '+' + (have.length - 3)));
                foot.appendChild(sk);
                if (TRACK_URL) {
                    var tr = el('button', 'btn-outline btn-sm', 'Lacak'); tr.type = 'button'; tr.title = 'Simpan ke Pelacak lamaran'; tr.setAttribute('aria-label', 'Lacak lamaran ' + j.title);
                    tr.addEventListener('click', function () {
                        tr.disabled = true;
                        fetch(TRACK_URL, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ title: j.title, company: j.company, url: safeUrl(j.url), location: j.location || null }) })
                            .then(function (r) { if (!r.ok) throw new Error('x'); return r.json(); })
                            .then(function () { tr.textContent = 'Tersimpan'; })
                            .catch(function () { tr.disabled = false; tr.textContent = 'Coba lagi'; });
                    });
                    foot.appendChild(tr);
                }
                var go = el('a', 'btn-outline btn-sm', 'Lihat'); go.href = safeUrl(j.url); go.target = '_blank'; go.rel = 'noopener noreferrer'; go.setAttribute('aria-label', 'Lihat lowongan ' + j.title); foot.appendChild(go);
                c.appendChild(foot);
                return c;
            }
        })();
    </script>
@endsection
