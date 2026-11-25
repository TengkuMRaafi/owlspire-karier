{{-- Gerak halus antarhalaman, navbar, dan layar pemuatan. --}}
<script>
    (function () {
        var d = document.documentElement, seen = false, intro = false;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if ('onpagereveal' in window) d.classList.add('vt-on');
        try {
            seen = sessionStorage.getItem('owlspire_loaded') === '1';
            intro = location.pathname === '/' && (localStorage.getItem('owlspire_intro_seen') !== '1' || /[?&]intro=1\b/.test(location.search));
        } catch (e) {}
        if (!seen && !reduce && !intro) d.classList.add('is-loading');
        else if (!seen) { try { sessionStorage.setItem('owlspire_loaded', '1'); } catch (e) {} }
    })();
</script>
