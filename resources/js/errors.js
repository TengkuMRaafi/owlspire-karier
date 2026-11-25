// Halaman error: hitung mundur 429 dan cek ulang otomatis 503. Tanpa JavaScript, tombol tetap bisa dipakai.
(function () {
    function fmt(s) { return String(s); }

    var timer = document.querySelector('[data-er-wait]');
    if (timer) {
        var total = Math.max(1, parseInt(timer.getAttribute('data-er-wait'), 10) || 30), left = total;
        var num = timer.querySelector('[data-er-left]'), fg = timer.querySelector('.er-ring-fg');
        var btn = document.querySelector('[data-er-retry]');
        if (btn) btn.classList.add('is-wait');
        var tick = setInterval(function () {
            left -= 1;
            if (num) num.textContent = fmt(Math.max(0, left));
            if (fg) fg.style.strokeDashoffset = String(100 * (1 - Math.max(0, left) / total));
            if (left <= 0) { clearInterval(tick); if (btn) btn.classList.remove('is-wait'); }
        }, 1000);
        if (fg) requestAnimationFrame(function () { fg.style.strokeDashoffset = String(100 * (1 - (total - 1) / total)); });
    }

    var poll = document.querySelector('[data-er-poll]');
    if (poll && window.fetch) {
        var gap = 30, count = gap, out = poll.querySelector('[data-er-poll-left]');
        setInterval(function () {
            count -= 1;
            if (out) out.textContent = String(Math.max(0, count));
            if (count > 0) return;
            count = gap;
            fetch(poll.getAttribute('data-er-poll'), { method: 'HEAD', cache: 'no-store', credentials: 'same-origin' })
                .then(function (r) { if (r.status !== 503) location.reload(); })
                .catch(function () { /* masih mati: coba lagi di putaran berikutnya */ });
        }, 1000);
    }
})();
