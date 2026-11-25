/* Kartu ATS (Analisis CV): klik satu poin -> pratinjau CV memperbesar bagian yang dimaksud dan membuka catatannya.
   Memakai pratinjau yang sudah ada: viewer gambar (cv-doc.js) bila CV berupa PDF, atau pratinjau teks bila bukan. */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-ats-go]') : null;
        if (!t) return;
        var keys = (t.getAttribute('data-ats-go') || '').split(',');

        for (var i = 0; i < keys.length; i++) {
            var k = keys[i].trim();
            var row = document.querySelector('[data-cvd] .cvd-grp[data-key="' + k + '"] [data-cvd-open]');
            if (row) { row.click(); return; } // viewer menangani zoom, popup, dan scroll

            var cell = document.querySelector('.cvp-cell[data-key="' + k + '"]');
            if (cell) {
                cell.scrollIntoView({ block: 'center', behavior: 'smooth' });
                cell.classList.add('is-flash');
                setTimeout(function () { cell.classList.remove('is-flash'); }, 1800);
                return;
            }
        }

        var box = document.getElementById('pratinjau');
        if (box) box.scrollIntoView({ block: 'start', behavior: 'smooth' });
    });
})();
