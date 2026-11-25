{{-- Widget Owlspire di kiri bawah: peserta bisa bertanya soal hasilnya, dijawab Groq (atau aturan bila Groq tidak aktif). Dibuka juga lewat tombol berisi atribut data-ask di halaman hasil. --}}
@php
    $ocShort = fn ($x) => preg_match('/\(([^)]+)\)/', $x, $m) ? $m[1] : \Illuminate\Support\Str::limit($x, 22);
    $ocWeak = collect($insights['segments'])->sortBy('pct')->first();
    // Ringkasan pembuka: Groq bila aktif, kalau gagal pakai versi berbasis aturan ini.
    $ocFallback = app(\App\Services\Exam\AiParaphrase::class)->fallback($insights, $attempt->mode !== 'full');
@endphp
<style>
    .oc { --acc: #3b6cd4; --acc7: #2f58b3; --ink: #1b2638; --mut: #667794; --line: #e2e6ec; position: fixed; left: 1rem; bottom: calc(1rem + var(--oc-lift, 0px)); z-index: 60; font-family: inherit; color: var(--ink); }
    .oc [hidden] { display: none !important; }
    .oc button { font: inherit; cursor: pointer; }
    .oc-fab { position: relative; display: grid; place-items: center; width: 3.9rem; height: 3.9rem; border: 3px solid #fff; border-radius: 50%; background: linear-gradient(145deg, #4f82e6, #2f58b3); box-shadow: 0 18px 30px -12px rgb(27 38 56 / .65); transition: transform .2s; }
    .oc-fab:hover { transform: translateY(-2px) scale(1.04); }
    .oc-fab svg { width: 2rem; height: auto; }
    .oc-fab::after { content: ''; position: absolute; inset: -6px; border: 2px solid rgb(59 108 212 / .5); border-radius: 50%; animation: oc-ring 2.4s ease-out infinite; }
    .oc.is-open .oc-fab::after, .oc.is-seen .oc-fab::after { animation: none; opacity: 0; }
    .oc-tip { position: absolute; left: 4.6rem; bottom: .6rem; width: max-content; max-width: 15rem; padding: .55rem .85rem; border: 1px solid #dce8fb; border-radius: 1rem 1rem 1rem .3rem; background: #fff; font-size: .8rem; font-weight: 700; line-height: 1.4; box-shadow: 0 14px 24px -14px rgb(27 38 56 / .5); animation: oc-pop .4s cubic-bezier(.2, .8, .2, 1) both; }
    .oc-panel { position: absolute; left: 0; bottom: 4.9rem; display: flex; flex-direction: column; width: min(24.5rem, calc(100vw - 2rem)); height: min(34rem, calc(100vh - 8rem)); overflow: hidden; border: 1px solid var(--line); border-radius: 1.4rem; background: #fff; box-shadow: 0 34px 60px -24px rgb(27 38 56 / .6); animation: oc-pop .3s cubic-bezier(.2, .8, .2, 1) both; transform-origin: bottom left; }
    .oc-top { display: flex; align-items: center; gap: .7rem; padding: .8rem 1rem; background: radial-gradient(18rem 8rem at 100% -30%, rgb(79 130 230 / .5), transparent 70%), linear-gradient(100deg, #1b2638 30%, #243a63); color: #fff; }
    .oc-top .oc-av { width: 2.5rem; height: 2.5rem; border-color: rgb(255 255 255 / .25); background: rgb(255 255 255 / .12); }
    .oc-top b { display: block; font-size: .95rem; }
    .oc-top small { display: flex; align-items: center; gap: .35rem; font-size: .72rem; color: #aebbd0; }
    .oc-top small i { width: .45rem; height: .45rem; border-radius: 50%; background: #2fbf7a; }
    .oc-x { margin-left: auto; display: grid; place-items: center; width: 2rem; height: 2rem; border: 0; border-radius: 50%; background: rgb(255 255 255 / .12); color: #fff; }
    .oc-x:hover { background: rgb(255 255 255 / .22); }
    .oc-x svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; }
    .oc-list { flex: 1; display: grid; align-content: start; gap: .55rem; overflow-y: auto; padding: 1rem .9rem .5rem; background: #f7f9fc; scroll-behavior: smooth; }
    .oc-m { display: flex; align-items: flex-end; gap: .5rem; animation: oc-in .3s cubic-bezier(.2, .8, .2, 1) both; }
    .oc-m.is-user { justify-content: flex-end; }
    .oc-av { display: grid; flex: none; place-items: center; width: 1.9rem; height: 1.9rem; border: 1px solid #dce8fb; border-radius: 50%; background: #eef4fd; }
    .oc-av svg { width: 1.05rem; height: auto; }
    .oc-m.is-cont .oc-av { visibility: hidden; }
    .oc-b { max-width: 82%; padding: .6rem .85rem; border-radius: 1.05rem 1.05rem 1.05rem .3rem; background: #fff; border: 1px solid #e3e9f3; font-size: .86rem; line-height: 1.6; color: #2a384f; }
    .oc-b p { margin: 0; } .oc-b p + p { margin-top: .5rem; }
    .oc-m.is-user .oc-b { border: 0; border-radius: 1.05rem 1.05rem .3rem 1.05rem; background: var(--acc); color: #fff; }
    .oc-dots { display: inline-flex; gap: .26rem; padding: .3rem .1rem; }
    .oc-dots i { width: .42rem; height: .42rem; border-radius: 50%; background: #9db4dd; animation: oc-b 1s ease-in-out infinite; }
    .oc-dots i:nth-child(2) { animation-delay: .15s; } .oc-dots i:nth-child(3) { animation-delay: .3s; }
    .oc-chips { display: flex; gap: .4rem; overflow-x: auto; padding: .55rem .9rem .1rem; background: #f7f9fc; scrollbar-width: none; }
    .oc-chips::-webkit-scrollbar { display: none; }
    .oc-chips button { flex: none; padding: .35rem .8rem; border: 1.5px solid #cfe0fa; border-radius: 99px; background: #fff; font-size: .76rem; font-weight: 700; color: var(--acc7); white-space: nowrap; }
    .oc-chips button:hover { border-color: var(--acc); background: #eef4fd; }
    .oc-form { display: flex; gap: .5rem; padding: .6rem .8rem .5rem; background: #f7f9fc; }
    .oc-form input { flex: 1; min-width: 0; padding: .6rem .95rem; border: 1.5px solid var(--line); border-radius: 99px; background: #fff; font: inherit; font-size: .88rem; color: var(--ink); outline: none; }
    .oc-form input:focus { border-color: var(--acc); box-shadow: 0 0 0 3px rgb(59 108 212 / .15); }
    .oc-send { display: grid; place-items: center; flex: none; width: 2.6rem; height: 2.6rem; border: 0; border-radius: 50%; background: var(--acc); color: #fff; transition: background .15s, transform .15s; }
    .oc-send:hover:not(:disabled) { background: var(--acc7); transform: translateY(-1px); }
    .oc-send:disabled { opacity: .45; cursor: default; }
    .oc-send svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .oc-fine { margin: 0; padding: 0 1rem .65rem; background: #f7f9fc; text-align: center; font-size: .68rem; color: var(--mut); }
    @keyframes oc-ring { 0% { transform: scale(.9); opacity: .9; } 100% { transform: scale(1.35); opacity: 0; } }
    @keyframes oc-pop { from { opacity: 0; transform: translateY(8px) scale(.96); } }
    @keyframes oc-in { from { opacity: 0; transform: translateY(6px); } }
    @keyframes oc-b { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-4px); opacity: 1; } }
    @media (max-width: 640px) { .oc { left: .6rem; bottom: calc(.6rem + var(--oc-lift, 0px)); } .oc-fab { width: 3rem; height: 3rem; border-width: 2px; } .oc:not(.is-open) .oc-fab { opacity: .94; } .oc-panel { bottom: 3.8rem; width: calc(100vw - 2.4rem); height: min(30rem, calc(100vh - 11rem)); height: min(30rem, calc(100dvh - 11rem)); } .oc-tip { display: none; } }
    @media (prefers-reduced-motion: reduce) { .oc * { animation: none !important; } }

    /* Perbaikan melebar ke kanan: lebar dikunci ke layar (bukan 100vw yang ikut menghitung scrollbar), kolom pesan tidak boleh lebih lebar dari panel */
    .oc .oc-panel { box-sizing: border-box; max-width: calc(100vw - 1.2rem); }
    .oc .oc-list { grid-template-columns: minmax(0, 1fr); overflow-x: hidden; overscroll-behavior: contain; }
    .oc .oc-m { min-width: 0; max-width: 100%; }
    .oc .oc-b { min-width: 0; box-sizing: border-box; overflow-wrap: anywhere; word-break: normal; }
    .oc .oc-chips, .oc .oc-form, .oc .oc-fine { box-sizing: border-box; max-width: 100%; }
    .oc .oc-fine { overflow-wrap: anywhere; text-wrap: balance; }
    @media (max-width: 640px) {
        .oc { right: .6rem; pointer-events: none; }
        .oc > * { pointer-events: auto; }
        .oc .oc-panel { left: 0; right: 0; width: auto; max-width: none; }
        .oc .oc-b { max-width: calc(100% - 2.4rem); }
        .oc .oc-m.is-user .oc-b { max-width: 88%; }
    }
</style>

<div class="oc" id="oc" data-url="{{ route('exam.ai.ask', $attempt) }}" data-summary="{{ route('exam.ai.summary', $attempt) }}">
    <div class="oc-panel" id="oc-panel" role="dialog" aria-label="Tanya Owlspire tentang hasilmu" hidden>
        <header class="oc-top">
            <span class="oc-av"><x-owl :animated="true" /></span>
            <div><b>Owlspire</b><small><i></i>Asisten hasil &middot; siap menjelaskan</small></div>
            <button type="button" class="oc-x" data-oc-close aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        </header>
        <div class="oc-list" id="oc-list" aria-live="polite"></div>
        <div class="oc-chips">
            <button type="button" data-ask="Kenapa skorku segini?">Kenapa skorku segini?</button>
            @if ($ocWeak)<button type="button" data-ask="Cara melatih {{ $ocWeak['name'] }} supaya naik">Latih {{ $ocShort($ocWeak['name']) }}</button>@endif
            <button type="button" data-ask="Mana yang harus dikejar dulu?">Kejar mana dulu?</button>
            <button type="button" data-ask="Buat rencana belajar 7 hari">Rencana 7 hari</button>
            <button type="button" data-ask="Kenapa waktuku per soal begini?">Soal waktu</button>
        </div>
        <form class="oc-form" id="oc-form" autocomplete="off">
            <input type="text" id="oc-input" maxlength="300" placeholder="Tanya soal hasilmu..." aria-label="Pertanyaan untuk Owlspire">
            <button type="submit" class="oc-send" id="oc-send" aria-label="Kirim"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </form>
        <p class="oc-fine">Dijawab dari data hasilmu. Bisa keliru, cek lagi di tab Pembahasan.</p>
    </div>
    <div class="oc-tip" id="oc-tip" hidden>Aku sudah baca hasilmu. Mau kujelaskan?</div>
    <button type="button" class="oc-fab" id="oc-fab" aria-label="Tanya Owlspire tentang hasilmu" aria-expanded="false"><x-owl :animated="true" /></button>
    <template id="oc-owl"><x-owl :animated="true" /></template>
</div>
<script>
    (function () {
        var root = document.getElementById('oc'); if (!root) return;
        var panel = document.getElementById('oc-panel'), list = document.getElementById('oc-list'), form = document.getElementById('oc-form');
        var input = document.getElementById('oc-input'), send = document.getElementById('oc-send'), fab = document.getElementById('oc-fab'), tip = document.getElementById('oc-tip');
        var tpl = document.getElementById('oc-owl'), history = [], busy = false, greeted = false;
        var tok = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };

        function scroll() { list.scrollTop = list.scrollHeight; }
        function row(role, content) {
            var prev = list.lastElementChild;
            if (prev && prev.classList.contains('is-bot') && role === 'bot') prev.classList.add('is-cont');
            var m = document.createElement('div'); m.className = 'oc-m is-' + role;
            if (role === 'bot') { var av = document.createElement('span'); av.className = 'oc-av'; av.appendChild(tpl.content.cloneNode(true)); m.appendChild(av); }
            var b = document.createElement('div'); b.className = 'oc-b';
            if (content === null) { b.innerHTML = '<span class="oc-dots" aria-label="Owlspire sedang mengetik"><i></i><i></i><i></i></span>'; }
            else { String(content).split(/\n{2,}/).forEach(function (t) { if (!t.trim()) return; var p = document.createElement('p'); p.textContent = t.trim(); b.appendChild(p); }); }
            m.appendChild(b); list.appendChild(m); scroll();
            return m;
        }
        var FALLBACK = @json($ocFallback), reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        var wait = function (ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); };
        // Ringkasan pembuka: tiap paragraf jadi satu bubble, didahului indikator mengetik.
        function summarize() {
            busy = true; send.disabled = true;
            var typing = row('bot', null);
            var ctl = window.AbortController ? new AbortController() : null;
            var kill = setTimeout(function () { if (ctl) ctl.abort(); }, 25000);
            fetch(root.dataset.summary, { method: 'POST', signal: ctl ? ctl.signal : undefined, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tok() } })
                .then(function (r) { if (!r.ok) throw 0; return r.json(); })
                .then(function (j) { return j.text || FALLBACK; })
                .catch(function () { return FALLBACK; }) // gagal pun tetap ada ringkasan, tanpa pesan error
                .then(function (text) {
                    clearTimeout(kill); typing.remove();
                    var parts = String(text).split(/\n{2,}/).map(function (x) { return x.trim(); }).filter(Boolean);
                    return parts.reduce(function (p, part) {
                        return p.then(function () { var t = row('bot', null); return wait(Math.min(900, 350 + part.length * 4)).then(function () { t.remove(); row('bot', part); }); });
                    }, Promise.resolve());
                })
                .then(function () { row('bot', 'Pilih pertanyaan di bawah, atau tanya apa saja soal skor, bagian yang lemah, dan cara belajarnya.'); })
                .then(function () { busy = false; send.disabled = false; });
        }
        function open() {
            panel.hidden = false; root.classList.add('is-open'); fab.setAttribute('aria-expanded', 'true'); tip.hidden = true;
            if (!greeted) { greeted = true; summarize(); }
            setTimeout(function () { input.focus({ preventScroll: true }); }, 50);
        }
        function close() { panel.hidden = true; root.classList.remove('is-open'); root.classList.add('is-seen'); fab.setAttribute('aria-expanded', 'false'); }
        function ask(q) {
            q = (q || '').trim();
            if (!q) { open(); return; }
            open();
            if (busy) return;
            busy = true; send.disabled = true;
            row('user', q);
            var typing = row('bot', null);
            var sent = history.slice(-6);
            history.push({ role: 'user', content: q });
            var ctl = window.AbortController ? new AbortController() : null;
            var kill = setTimeout(function () { if (ctl) ctl.abort(); }, 30000);
            fetch(root.dataset.url, { method: 'POST', signal: ctl ? ctl.signal : undefined, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': tok() }, body: JSON.stringify({ question: q, history: sent }) })
                .then(function (r) { if (r.status === 429) throw 429; if (!r.ok) throw r.status; return r.json(); })
                .then(function (j) { typing.remove(); row('bot', j.text); history.push({ role: 'assistant', content: j.text }); })
                .catch(function (s) { typing.remove(); history.pop(); row('bot', s === 429 ? 'Pertanyaanmu terlalu cepat. Tunggu sebentar lalu coba lagi.' : 'Aku lagi tidak bisa menjawab. Coba kirim ulang sebentar lagi.'); })
                .then(function () { clearTimeout(kill); busy = false; send.disabled = false; input.focus({ preventScroll: true }); });
        }
        // Tombol chat berhenti di atas footer, tidak menimpa isi footer saat halaman digulir ke bawah.
        // Saat panel terbuka tidak diangkat: panel memang boleh menutupi footer selama dipakai.
        var foot = document.querySelector('footer');
        function lift() {
            if (!foot) return;
            var over = root.classList.contains('is-open') ? 0 : Math.max(0, Math.round(window.innerHeight - foot.getBoundingClientRect().top));
            root.style.setProperty('--oc-lift', over + 'px');
        }
        window.addEventListener('scroll', lift, { passive: true });
        window.addEventListener('resize', lift);
        new MutationObserver(lift).observe(root, { attributes: true, attributeFilter: ['class'] });
        lift();
        window.OwlChat = { ask: ask, open: open, close: close };

        fab.addEventListener('click', function () { panel.hidden ? open() : close(); });
        root.querySelector('[data-oc-close]').addEventListener('click', close);
        form.addEventListener('submit', function (e) { e.preventDefault(); var q = input.value; input.value = ''; ask(q); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) close(); });
        // Tombol mana pun dengan data-ask (kartu bagian, chip Ringkasan) membuka widget dengan pertanyaannya.
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-ask]'); if (!b) return;
            e.preventDefault(); ask(b.getAttribute('data-ask'));
        });
        // Layar lebar: panel terbuka sendiri sekali supaya ringkasan langsung terbaca. Layar sempit hanya memunculkan sapaan kecil.
        var key = 'oc-auto:' + root.dataset.url, seen = false;
        try { seen = !!sessionStorage.getItem(key); } catch (e) {}
        if (innerWidth >= 1200 && !seen) {
            setTimeout(function () { if (panel.hidden && !root.classList.contains('is-seen')) { try { sessionStorage.setItem(key, '1'); } catch (e) {} open(); } }, 900);
        } else {
            setTimeout(function () { if (panel.hidden && !root.classList.contains('is-seen')) { tip.hidden = false; setTimeout(function () { tip.hidden = true; }, 7000); } }, 2500);
        }
    })();
</script>
