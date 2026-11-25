{{-- Layar pemuatan (sekali per sesi tab) + garis kemajuan pindah halaman. --}}
<div class="rt-bar" id="rt-bar" aria-hidden="true"></div>

<div class="pl" id="pl" role="presentation" aria-hidden="true">
    <div class="pl-glow"></div>
    <div class="pl-core">
        <x-owl class="pl-mark" :draw="true" />
        <p class="pl-name font-display"><span>Owl</span><b>spire</b></p>
        <div class="pl-bar"><i></i></div>
    </div>
</div>

<script>
    (function () {
        var d = document.documentElement;
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        var vt = d.classList.contains('vt-on');

        /* ---- Layar pemuatan ---- */
        var pl = document.getElementById('pl');
        if (pl) {
            if (!d.classList.contains('is-loading')) {
                pl.remove();
            } else {
                var t0 = performance.now(), MIN = 1200, done = false, p = 0, raf;
                var fill = pl.querySelector('.pl-bar i');
                (function tick() {
                    p += (0.9 - p) * 0.03; fill.style.transform = 'scaleX(' + p.toFixed(3) + ')';
                    if (!done) raf = requestAnimationFrame(tick);
                })();
                var finish = function () {
                    if (done) return; done = true; cancelAnimationFrame(raf);
                    fill.style.transition = 'transform .3s ease-out'; fill.style.transform = 'scaleX(1)';
                    setTimeout(function () {
                        pl.classList.add('is-out');
                        try { sessionStorage.setItem('owlspire_loaded', '1'); } catch (e) {}
                        setTimeout(function () { d.classList.remove('is-loading'); pl.remove(); }, 650);
                    }, Math.max(0, MIN - (performance.now() - t0)) + 250);
                };
                if (document.readyState === 'complete') finish(); else addEventListener('load', finish);
                setTimeout(finish, 6000); // jaring pengaman
            }
        }

        /* ---- Garis kemajuan + cadangan fade untuk browser tanpa View Transitions ---- */
        var bar = document.getElementById('rt-bar');
        function start() {
            if (!bar) return;
            bar.style.transition = 'none'; bar.style.opacity = '1'; bar.style.transform = 'scaleX(0)';
            void bar.offsetWidth;
            bar.style.transition = 'transform 7s cubic-bezier(.08, .6, .2, 1)'; bar.style.transform = 'scaleX(.88)';
        }
        function reset() {
            d.classList.remove('is-leaving');
            if (bar) { bar.style.transition = 'none'; bar.style.opacity = '0'; bar.style.transform = 'scaleX(0)'; }
        }
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download') || a.hasAttribute('data-no-transition')) return;
            var u; try { u = new URL(a.href, location.href); } catch (x) { return; }
            if (u.origin !== location.origin || !/^https?:$/.test(u.protocol)) return;
            if (u.pathname === location.pathname && u.search === location.search) return; // lompat #anchor di halaman yang sama
            start();
            if (!vt && !reduce) {
                e.preventDefault(); d.classList.add('is-leaving');
                setTimeout(function () { location.href = a.href; }, 210);
            }
        });
        document.addEventListener('submit', function (e) { if (!e.defaultPrevented) start(); });
        addEventListener('pageshow', function (e) { if (e.persisted) reset(); });

        /* ---- Navbar: bayangan saat halaman digulir ---- */
        var header = document.querySelector('.site-header');
        if (header) {
            var onScroll = function () { header.classList.toggle('is-scrolled', scrollY > 8); };
            addEventListener('scroll', onScroll, { passive: true }); onScroll();
        }
    })();
</script>
