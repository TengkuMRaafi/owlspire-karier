{{-- Pratinjau langsung di kartu kanan form (.ad-pv). Penanda di Blade:
     data-bind="nama_field"      isi teks dari input itu (data-empty = teks bila kosong)
     data-bind-dd="nama_field"   label pilihan dropdown kustom
     data-show / data-check      tampil hanya bila field terisi / dicentang
     data-key-of + data-key-val  beri kelas is-key bila nilai field sama (kunci jawaban)
     data-chips="nama_field"     pecah isi per koma jadi lencana
     data-yt="nama_field"        sematkan video YouTube dari tautan atau ID (data-yt-default = ID bawaan) --}}
<script>
    (function () {
        var f = document.querySelector('.ad-uf > form'), p = document.querySelector('.ad-uf > .ad-pv');
        if (!f || !p) return;
        function val(n) { var e = f.elements[n]; if (!e) return ''; if (e.type === 'checkbox') return e.checked ? '1' : ''; return e.value || ''; }
        function ddLabel(n) {
            var e = f.elements[n], dd = e && e.closest ? e.closest('[data-dd]') : null;
            if (!dd || !e.value) return '';
            var l = dd.querySelector('[data-dd-label]');
            return l ? l.textContent.trim() : '';
        }
        function ytId(s) {
            s = String(s || '').trim();
            var m = s.match(/(?:v=|youtu\.be\/|\/embed\/|\/shorts\/)([A-Za-z0-9_-]{11})/);
            if (m) return m[1];
            return /^[A-Za-z0-9_-]{11}$/.test(s) ? s : '';
        }
        function render() {
            p.querySelectorAll('[data-bind]').forEach(function (el) {
                var v = val(el.dataset.bind).trim();
                el.textContent = v || el.dataset.empty || '';
                el.classList.toggle('is-empty', !v);
            });
            p.querySelectorAll('[data-bind-dd]').forEach(function (el) {
                var v = ddLabel(el.dataset.bindDd);
                el.textContent = v || el.dataset.empty || '';
                el.classList.toggle('is-empty', !v);
            });
            p.querySelectorAll('[data-show]').forEach(function (el) { el.hidden = !val(el.dataset.show).trim(); });
            p.querySelectorAll('[data-check]').forEach(function (el) { el.hidden = !val(el.dataset.check); });
            p.querySelectorAll('[data-key-of]').forEach(function (el) { el.classList.toggle('is-key', val(el.dataset.keyOf) === el.dataset.keyVal); });
            p.querySelectorAll('[data-chips]').forEach(function (el) {
                var items = val(el.dataset.chips).split(',').map(function (t) { return t.trim(); }).filter(Boolean);
                el.textContent = '';
                items.forEach(function (t) { var s = document.createElement('span'); s.className = 'ui-badge'; s.textContent = t; el.appendChild(s); });
                el.hidden = !items.length;
            });
            p.querySelectorAll('[data-yt]').forEach(function (el) {
                var id = ytId(val(el.dataset.yt)) || el.dataset.ytDefault || '';
                var fr = el.querySelector('iframe'), ph = el.querySelector('[data-yt-empty]');
                if (id) {
                    var src = 'https://www.youtube-nocookie.com/embed/' + id;
                    if (fr.getAttribute('src') !== src) fr.setAttribute('src', src);
                    fr.hidden = false; ph.hidden = true;
                } else {
                    fr.removeAttribute('src'); fr.hidden = true; ph.hidden = false;
                }
            });
        }
        f.addEventListener('input', render);
        f.addEventListener('change', render);
        render();
    })();
</script>
