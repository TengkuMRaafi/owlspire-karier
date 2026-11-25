{{-- PILIH SIMULASI di landing: kartu 3 seleksi resmi + Simulasi Jalur Karier. --}}
<section id="pilih-simulasi" class="mx-auto max-w-6xl px-4 py-16" aria-label="Pilih simulasi">
    @include('partials.sim-cards')

    {{-- Penanda halaman carousel (hanya tampil di HP; lihat exam.css) --}}
    <div class="sc-nav" data-sc-nav>
        <div class="sc-nav-dots" data-sc-dots></div>
        <span class="sc-nav-n" data-sc-n aria-hidden="true"></span>
    </div>
</section>

<script>
    (function () {
        var sec = document.getElementById('pilih-simulasi'); if (!sec) return;
        var grid = sec.querySelector('.sc-grid'), dotsEl = sec.querySelector('[data-sc-dots]'), nEl = sec.querySelector('[data-sc-n]');
        if (!grid || !dotsEl) return;
        var cards = [].slice.call(grid.querySelectorAll('.sc-card')), dots = [], cur = -1, tick = false;
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (cards.length < 2) { sec.querySelector('[data-sc-nav]').hidden = true; return; }

        function left(i) { // posisi gulir agar kartu ke-i rata dengan tepi kiri area isi
            var pad = parseFloat(getComputedStyle(grid).paddingLeft) || 0;
            return cards[i].getBoundingClientRect().left - grid.getBoundingClientRect().left + grid.scrollLeft - pad;
        }
        cards.forEach(function (c, i) {
            var b = document.createElement('button'); b.type = 'button';
            b.setAttribute('aria-label', 'Simulasi ' + (i + 1) + ' dari ' + cards.length);
            b.addEventListener('click', function () { grid.scrollTo({ left: left(i), behavior: reduce ? 'auto' : 'smooth' }); });
            dotsEl.appendChild(b); dots.push(b);
        });
        function mark() {
            tick = false;
            var step = cards[1].offsetLeft - cards[0].offsetLeft || 1;
            var i = Math.max(0, Math.min(cards.length - 1, Math.round(grid.scrollLeft / step)));
            if (i === cur) return; cur = i;
            dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); if (k === i) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
            if (nEl) nEl.textContent = (i + 1) + ' / ' + cards.length;
        }
        grid.addEventListener('scroll', function () { if (!tick) { tick = true; requestAnimationFrame(mark); } }, { passive: true });
        mark();

        // Geser otomatis tiap 3 detik (hanya saat kartu berupa carousel di HP). Berhenti saat disentuh, disorot, atau difokus;
        // lanjut lagi 5 detik setelah dilepas. Tidak jalan saat tab tersembunyi, bagian di luar layar, atau gerak dikurangi.
        var AUTO_MS = 3000, RESUME_MS = 5000, timer = null, resume = null, inView = false, held = false;
        function scrollable() { return grid.scrollWidth > grid.clientWidth + 8; }
        function step() {
            if (held || document.hidden || !inView || !scrollable()) return;
            var n = (Math.max(cur, 0) + 1) % cards.length;
            grid.scrollTo({ left: left(n), behavior: 'smooth' });
        }
        function start() { if (reduce || timer) return; timer = setInterval(step, AUTO_MS); }
        function stop() { clearInterval(timer); timer = null; }
        function hold() { held = true; clearTimeout(resume); }
        function release() { clearTimeout(resume); resume = setTimeout(function () { held = false; }, RESUME_MS); }
        grid.addEventListener('pointerdown', hold, { passive: true });
        grid.addEventListener('pointerup', release, { passive: true });
        grid.addEventListener('pointercancel', release, { passive: true });
        grid.addEventListener('mouseenter', hold);
        grid.addEventListener('mouseleave', release);
        grid.addEventListener('focusin', hold);
        grid.addEventListener('focusout', release);
        dotsEl.addEventListener('click', function () { hold(); release(); });
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (es) { inView = es[0].isIntersecting; }, { threshold: .4 }).observe(sec);
        } else { inView = true; }
        start();
    })();
</script>
