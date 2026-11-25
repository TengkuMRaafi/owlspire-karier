{{-- PEMANDU OWLSPIRE: maskot yang menemani pengguna menyusuri landing. Terbang antar sisi layar mengikuti bagian yang sedang dilihat,
     matanya mengikuti kursor / arah gulir, dan memberi penjelasan singkat tiap bagian. Hanya hiasan bantu: tanpa JS landing tetap utuh. --}}
@php
    // [selector bagian, label, pesan, [label tombol, url] atau null]
    $guideSteps = [
        ['.hx', 'Awal', 'Halo, aku Owlspire! Biar aku yang jadi pemandumu. Geser pelan-pelan, nanti kutunjukkan jalurnya.', ['Cari arah kariermu', route('careers.quiz')]],
        ['[data-sb]', 'Papan skor', 'Ini papan skor hari ini. Angkanya dihitung langsung dari data: pengguna, soal, dan simulasi yang sudah selesai. Klik angkanya untuk memutar ulang.', null],
        ['#perjalanan', 'Roadmap', 'Ini jalurmu: lima langkah dari bingung sampai siap melamar. Klik tiap langkah untuk melihat isinya.', ['Mulai tes arah karier', route('careers.quiz')]],
        ['#pilih-simulasi', 'Simulasi', 'Pilih seleksi yang mau dilatih, atau pilih peran kariermu. Soalnya mengikuti pilihanmu.', ['Lihat semua simulasi', route('exam.index')]],
        ['[data-tb]', 'Papan karier', 'Urutan karier di sini bergerak mengikuti lowongan, simulasi, dan target pengguna. Ada yang menarik buatmu?', ['Lihat semua karier', route('exam.index').'#karier']],
        ['[data-pk]', 'Peringkat', 'Ini para juara ujian penuh. Skor sama? Yang paling cepat menang. Siap menyalip mereka?', ['Lihat papan peringkat', route('exam.leaderboard')]],
        ['[data-guide-end]', 'Mulai', 'Bingung mau ke mana? Jawab dua puluh pertanyaan keahlian, nanti kita lihat arah kariermu. Aku ikut!', ['Cari arah kariermu', route('careers.quiz')]],
        ['[data-fq]', 'FAQ', 'Masih ada yang mengganjal? Buka pertanyaannya satu per satu, aku tunggu di sini.', null],
    ];
@endphp

<aside class="og" data-og data-side="right" hidden aria-label="Pemandu Owlspire" data-steps='@json($guideSteps)'>
    <div class="og-bubble" data-og-bubble hidden>
        <div class="og-top">
            <b class="og-name"><i aria-hidden="true"></i>Owlspire</b>
            <span class="og-step" data-og-step></span>
            <button type="button" class="og-min" data-og-min aria-label="Sembunyikan pesan pemandu">
                <svg viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 5l10 10M15 5 5 15"/></svg>
            </button>
        </div>
        <p class="og-txt" data-og-txt aria-hidden="true"></p>
        <p class="sr-only" data-og-sr aria-live="polite"></p>
        <a class="og-cta" data-og-cta hidden></a>
        <div class="og-nav">
            <button type="button" class="og-btn og-prev" data-og-prev aria-label="Bagian sebelumnya">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
            </button>
            <div class="og-dots" data-og-dots role="group" aria-label="Loncat ke bagian"></div>
            <button type="button" class="og-btn og-next" data-og-next>
                <span data-og-nextl>Lanjut</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"/></svg>
            </button>
        </div>
        <span class="og-tail" aria-hidden="true"></span>
    </div>

    <button type="button" class="og-owl" data-og-owl aria-expanded="true" aria-label="Pemandu Owlspire, buka atau tutup pesan">
        <x-owl class="og-ow" :animated="true" />
        <i class="og-ping" data-og-ping aria-hidden="true"></i>
    </button>
    <span class="og-shadow" aria-hidden="true"></span>
</aside>

<script>
    (function () {
        var dock = document.querySelector('[data-og]'); if (!dock) return;
        var defs; try { defs = JSON.parse(dock.getAttribute('data-steps')); } catch (e) { return; }
        var steps = [], els = [];
        defs.forEach(function (d) { var el = document.querySelector(d[0]); if (el) { els.push(el); steps.push({ label: d[1], text: d[2], cta: d[3] }); } });
        if (!steps.length) return;

        var $ = function (s) { return dock.querySelector(s); };
        var bubble = $('[data-og-bubble]'), txt = $('[data-og-txt]'), sr = $('[data-og-sr]'), cta = $('[data-og-cta]');
        var stepEl = $('[data-og-step]'), dotsEl = $('[data-og-dots]'), nextL = $('[data-og-nextl]'), owl = $('[data-og-owl]'), ping = $('[data-og-ping]');
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        var cur = -1, target = 0, curX = 0, side = 'right', typer = 0, hideT = 0, settle = 0, lookT = 0, lastY = scrollY, busy = false, token = 0;
        var minimized = false;
        try { minimized = sessionStorage.getItem('og_min') === '1'; } catch (e) {}
        if (innerWidth < 640 && !minimized) minimized = true; // layar kecil: mulai ringkas, ketuk burung hantu untuk membuka

        var wide = function () { return innerWidth >= 900; };
        var xFor = function (s) { var w = dock.offsetWidth, vw = document.documentElement.clientWidth; return s === 'left' ? 12 : Math.max(12, vw - w - 12); };
        var setX = function (x) { curX = x; dock.style.transform = 'translate3d(' + x + 'px,0,0)'; };

        // ---- Titik loncat
        steps.forEach(function (s, i) {
            var b = document.createElement('button'); b.type = 'button'; b.className = 'og-dot'; b.setAttribute('aria-label', 'Ke bagian ' + s.label); b.title = s.label;
            b.addEventListener('click', function () { goTo(i); });
            // Tekan/arahkan/fokus titik: label bagian tujuan muncul di lencana langkah
            ['pointerenter', 'pointerdown', 'focus'].forEach(function (ev) { b.addEventListener(ev, function () { stepEl.textContent = s.label; stepEl.classList.add('is-peek'); }); });
            ['pointerleave', 'pointercancel', 'blur'].forEach(function (ev) { b.addEventListener(ev, function () { stepEl.classList.remove('is-peek'); if (cur >= 0) stepEl.textContent = stepText(cur); }); });
            dotsEl.appendChild(b);
        });
        var dots = [].slice.call(dotsEl.children);

        function stepText(i) { return (i + 1) + ' / ' + steps.length + ' · ' + steps[i].label; }
        var prevB = $('[data-og-prev]'), nextB = $('[data-og-next]');

        function goTo(i) {
            i = Math.max(0, Math.min(steps.length - 1, i));
            var top = els[i].getBoundingClientRect().top + scrollY - (i === 0 ? 0 : 72);
            scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });
        }

        // ---- Mata mengikuti kursor / arah gulir
        function look(x, y) { owl.style.setProperty('--lx', x.toFixed(2)); owl.style.setProperty('--ly', y.toFixed(2)); }
        addEventListener('pointermove', function (e) {
            if (e.pointerType !== 'mouse' || dock.hidden) return;
            var r = owl.getBoundingClientRect(), dx = e.clientX - (r.left + r.width / 2), dy = e.clientY - (r.top + r.height / 2);
            var d = Math.hypot(dx, dy) || 1, k = Math.min(1, d / 260);
            look(dx / d * 3.4 * k, dy / d * 2.6 * k);
        }, { passive: true });

        // ---- Gerak
        function hop() { if (reduce || !owl.animate) return; owl.animate([{ translate: '0 0' }, { translate: '0 -16px', offset: .4 }, { translate: '0 0' }], { duration: 480, easing: 'cubic-bezier(.3,1.4,.5,1)' }); }
        function fly(toSide) {
            var from = curX, to = xFor(toSide);
            if (reduce || !dock.animate || Math.abs(to - from) < 6) { setX(to); hop(); return Promise.resolve(); }
            var dir = to > from ? 1 : -1;
            dock.classList.add('is-flying'); setX(to);
            var a = dock.animate([
                { transform: 'translate3d(' + from + 'px,0,0)' },
                { transform: 'translate3d(' + ((from + to) / 2) + 'px,-110px,0)', offset: .5 },
                { transform: 'translate3d(' + to + 'px,0,0)' }
            ], { duration: 1000, easing: 'cubic-bezier(.45,.05,.25,1)' });
            if (owl.animate) owl.animate([{ rotate: '0deg' }, { rotate: (dir * -16) + 'deg', offset: .3 }, { rotate: (dir * 12) + 'deg', offset: .75 }, { rotate: '0deg' }], { duration: 1000, easing: 'ease-in-out' });
            return a.finished.catch(function () {}).then(function () { dock.classList.remove('is-flying'); hop(); });
        }

        // ---- Gelembung pesan
        function typeText(t) {
            clearInterval(typer); sr.textContent = t;
            if (reduce) { txt.textContent = t; return; }
            var n = 0; txt.textContent = ''; txt.classList.add('is-typing');
            typer = setInterval(function () { n += 2; txt.textContent = t.slice(0, n); if (n >= t.length) { clearInterval(typer); txt.classList.remove('is-typing'); } }, 22);
        }
        function render(i) {
            var s = steps[i];
            stepEl.textContent = stepText(i);
            var pv = steps[Math.max(0, i - 1)].label;
            prevB.title = i > 0 ? 'Ke atas: ' + pv : 'Bagian pertama';
            prevB.setAttribute('aria-label', i > 0 ? 'Bagian sebelumnya: ' + pv : 'Bagian pertama');
            prevB.disabled = i === 0;
            dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); d.classList.toggle('is-done', k < i); if (k === i) d.setAttribute('aria-current', 'step'); else d.removeAttribute('aria-current'); });
            nextL.textContent = i === steps.length - 1 ? 'Ke atas' : steps[i + 1].label;
            nextB.title = i === steps.length - 1 ? 'Kembali ke awal' : 'Ke bawah: ' + steps[i + 1].label;
            if (s.cta) { cta.hidden = false; cta.textContent = s.cta[0]; cta.href = s.cta[1]; } else { cta.hidden = true; }
            typeText(s.text);
        }
        function showBubble(i) {
            clearTimeout(hideT); bubble.hidden = false; render(i);
            requestAnimationFrame(function () { bubble.classList.add('is-on'); });
            owl.setAttribute('aria-expanded', 'true'); ping.classList.remove('is-on');
        }
        function hideBubble() {
            clearTimeout(hideT); clearInterval(typer); bubble.classList.remove('is-on');
            hideT = setTimeout(function () { if (!bubble.classList.contains('is-on')) bubble.hidden = true; }, reduce ? 0 : 240);
            owl.setAttribute('aria-expanded', 'false');
        }

        // ---- Pindah ke bagian baru: terbang (bila sisi berganti), lalu bicara
        function apply(i) {
            cur = i; var my = ++token;
            var nextSide = 'right'; // posisi tetap di kanan bawah (tidak berpindah sisi)
            hideBubble();
            var go = nextSide !== side ? fly(nextSide) : (hop(), Promise.resolve());
            go.then(function () {
                if (my !== token) return;
                side = nextSide; dock.setAttribute('data-side', side);
                if (minimized) { ping.classList.add('is-on'); } else { showBubble(i); }
            });
        }

        // ---- Bagian mana yang sedang dilihat
        function pick() {
            var vh = innerHeight, line = vh * .45, idx = 0;
            for (var i = 0; i < els.length; i++) { if (els[i].getBoundingClientRect().top <= line) idx = i; }
            if (scrollY + vh >= document.documentElement.scrollHeight - 6) idx = els.length - 1;
            return idx;
        }
        var tick = false;
        addEventListener('scroll', function () {
            if (tick || dock.hidden) return; tick = true;
            requestAnimationFrame(function () {
                tick = false;
                var y = scrollY, dir = y > lastY ? 1 : -1; lastY = y;
                look(0, dir * 2.6); clearTimeout(lookT); lookT = setTimeout(function () { look(0, 0); }, 700); // melirik ke arah gulir
                var i = pick();
                if (i !== target) { target = i; clearTimeout(settle); settle = setTimeout(function () { if (target !== cur) apply(target); }, 260); }
            });
        }, { passive: true });
        addEventListener('resize', function () { setX(xFor(wide() ? side : 'right')); });

        // Pemandu berhenti di atas footer, tidak menimpa isinya saat halaman digulir sampai bawah.
        // Memakai properti `translate` (terpisah dari `transform` yang dipakai animasi terbang), jadi keduanya tidak saling timpa.
        var foot = document.querySelector('footer');
        function lift() {
            if (!foot) return;
            var over = Math.max(0, Math.round(innerHeight - foot.getBoundingClientRect().top));
            dock.style.translate = over ? '0 ' + (-over) + 'px' : '';
        }
        addEventListener('scroll', lift, { passive: true });
        addEventListener('resize', lift);
        lift();

        // ---- Kontrol
        $('[data-og-next]').addEventListener('click', function () { goTo(cur >= steps.length - 1 ? 0 : cur + 1); });
        $('[data-og-prev]').addEventListener('click', function () { goTo(Math.max(0, cur - 1)); });
        $('[data-og-min]').addEventListener('click', function () { minimized = true; try { sessionStorage.setItem('og_min', '1'); } catch (e) {} hideBubble(); owl.focus(); });
        owl.addEventListener('click', function () {
            hop();
            if (bubble.classList.contains('is-on')) { minimized = true; try { sessionStorage.setItem('og_min', '1'); } catch (e) {} hideBubble(); }
            else { minimized = false; try { sessionStorage.removeItem('og_min'); } catch (e) {} showBubble(cur < 0 ? 0 : cur); }
        });
        dock.addEventListener('keydown', function (e) { if (e.key === 'Escape' && bubble.classList.contains('is-on')) { minimized = true; hideBubble(); owl.focus(); } });

        // ---- Mulai setelah splash perkenalan dan layar muat selesai
        function start() {
            dock.hidden = false; setX(xFor('right')); target = pick();
            if (!reduce && owl.animate) owl.animate([{ translate: '0 140px', opacity: 0 }, { translate: '0 0', opacity: 1 }], { duration: 800, easing: 'cubic-bezier(.3,1.3,.5,1)' });
            setTimeout(function () { apply(target); }, reduce ? 0 : 650);
        }
        var root = document.documentElement, ready = function () { return !root.classList.contains('intro-lock') && !root.classList.contains('is-loading'); };
        if (ready()) start();
        else { var mo = new MutationObserver(function () { if (ready()) { mo.disconnect(); setTimeout(start, 500); } }); mo.observe(root, { attributes: true, attributeFilter: ['class'] }); }
    })();
</script>
