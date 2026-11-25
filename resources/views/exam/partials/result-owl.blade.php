{{-- Ringkasan Owlspire dalam bentuk chat: bubble bot muncul bertahap (Groq bila aktif, kalau tidak berbasis aturan). Chip di bawahnya membuka widget tanya jawab. --}}
@php
    $obBrief = $attempt->mode !== 'full';
    $obFallback = app(\App\Services\Exam\AiParaphrase::class)->fallback($insights, $obBrief);
    $obShort = fn ($x) => preg_match('/\(([^)]+)\)/', $x, $m) ? $m[1] : \Illuminate\Support\Str::limit($x, 22);
    $obWeak = collect($insights['segments'])->sortBy('pct')->first();
@endphp
<style>
    .ob { margin-top: .9rem; border: 1px solid #dce8fb; border-radius: 1.3rem; background: linear-gradient(180deg, #f7faff, #fff 60%); overflow: hidden; }
    .ob-head { display: flex; align-items: center; gap: .6rem; padding: .75rem 1.1rem; border-bottom: 1px solid #e6eefb; background: #fff; }
    .ob-head b { font-size: .92rem; font-weight: 800; }
    .ob-badge { padding: .12rem .6rem; border-radius: 99px; background: #eef4fd; font-size: .68rem; font-weight: 800; color: #2f58b3; }
    .ob-live { display: inline-flex; align-items: center; gap: .35rem; margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--mut); }
    .ob-live i { width: .5rem; height: .5rem; border-radius: 50%; background: #2fbf7a; box-shadow: 0 0 0 3px rgb(47 191 122 / .2); }
    .ob-thread { display: grid; gap: .45rem; padding: 1.1rem 1.1rem .4rem; min-height: 6.5rem; }
    .ob-msg { display: flex; align-items: flex-end; gap: .6rem; animation: ob-in .35s cubic-bezier(.2, .8, .2, 1) both; }
    .ob-av { display: grid; flex: none; place-items: center; width: 2.4rem; height: 2.4rem; border: 1px solid #dce8fb; border-radius: 50%; background: #eef4fd; }
    .ob-av svg { width: 1.3rem; height: auto; }
    .ob-msg.is-cont .ob-av { visibility: hidden; }
    .ob-bub { position: relative; max-width: min(40rem, 92%); margin: 0; padding: .75rem 1rem; border: 1px solid #e0e8f6; border-radius: 1.15rem 1.15rem 1.15rem .3rem; background: #fff; font-size: .92rem; line-height: 1.65; color: #2a384f; box-shadow: 0 10px 18px -16px rgb(27 38 56 / .5); }
    .ob-msg.is-cont .ob-bub { border-bottom-left-radius: 1.15rem; }
    .ob-dots { display: inline-flex; gap: .28rem; padding: .35rem .1rem; }
    .ob-dots i { width: .45rem; height: .45rem; border-radius: 50%; background: #9db4dd; animation: ob-b 1s ease-in-out infinite; }
    .ob-dots i:nth-child(2) { animation-delay: .15s; } .ob-dots i:nth-child(3) { animation-delay: .3s; }
    .ob-chips { display: flex; flex-wrap: wrap; gap: .45rem; padding: .6rem 1.1rem 1.1rem 4.1rem; }
    .ob-chips button { padding: .42rem .9rem; border: 1.5px solid #cfe0fa; border-radius: 99px; background: #fff; font-size: .8rem; font-weight: 700; color: #2f58b3; transition: background .15s, transform .15s, border-color .15s; }
    .ob-chips button:hover { transform: translateY(-1px); border-color: #3b6cd4; background: #eef4fd; }
    .ob-chips .is-free { border-style: dashed; color: var(--mut); }
    .ob-note { margin: 0; padding: 0 1.1rem 1rem 4.1rem; font-size: .72rem; color: var(--mut); }
    @keyframes ob-in { from { opacity: 0; transform: translateY(8px); } }
    @keyframes ob-b { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-4px); opacity: 1; } }
    @media (max-width: 640px) { .ob-chips, .ob-note { padding-left: 1.1rem; } }
    @media (prefers-reduced-motion: reduce) { .ob-msg, .ob-dots i { animation: none; } }
</style>
<section class="ob" id="ra-ai" data-url="{{ route('exam.ai.summary', $attempt) }}">
    <div class="ob-head">
        <b>Owlspire</b><span class="ob-badge">Asisten hasil</span>
        <span class="ob-live"><i></i>siap menjelaskan</span>
    </div>
    <div class="ob-thread" data-out aria-live="polite"></div>
    <div class="ob-chips">
        <button type="button" data-ask="Kenapa skorku segini?">Kenapa skorku segini?</button>
        @if ($obWeak)<button type="button" data-ask="Cara melatih {{ $obWeak['name'] }} supaya naik">Cara melatih {{ $obShort($obWeak['name']) }}</button>@endif
        <button type="button" data-ask="Buat rencana belajar 7 hari">Rencana 7 hari</button>
        <button type="button" class="is-free" data-ask="">Tanya hal lain</button>
    </div>
    <p class="ob-note">{{ $attempt->mode === 'full' ? 'Dirangkum dari hasil pengerjaanmu.' : 'Ringkasan singkat untuk mode coba.' }} Klik pertanyaan di atas, jawabannya muncul di widget Owlspire kiri bawah.</p>
    @if (config('app.debug'))<p class="ob-note" data-note hidden>Mode debug: ringkasan ini berbasis aturan karena belum ada kunci AI yang aktif atau semua penyedia gagal. Jalankan php artisan llm:test.</p>@endif
    <template data-owl><x-owl :animated="true" /></template>
</section>
<script>
    (function () {
        var box = document.getElementById('ra-ai'); if (!box) return;
        var out = box.querySelector('[data-out]'), note = box.querySelector('[data-note]'), tpl = box.querySelector('[data-owl]');
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        var FALLBACK = @json($obFallback), loaded = false;
        var wait = function (ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); };

        function msg(content) {
            var prev = out.lastElementChild; if (prev) prev.classList.add('is-cont');
            var m = document.createElement('div'); m.className = 'ob-msg';
            var av = document.createElement('span'); av.className = 'ob-av'; av.appendChild(tpl.content.cloneNode(true));
            var b = document.createElement('p'); b.className = 'ob-bub';
            if (content === null) { b.innerHTML = '<span class="ob-dots" aria-label="Owlspire sedang mengetik"><i></i><i></i><i></i></span>'; } else { b.textContent = content; }
            m.appendChild(av); m.appendChild(b); out.appendChild(m);
            return m;
        }
        // Tiap paragraf jadi satu bubble, didahului indikator mengetik seperti chat sungguhan.
        function play(text) {
            var parts = String(text).split(/\n{2,}/).map(function (x) { return x.trim(); }).filter(Boolean);
            return parts.reduce(function (p, part) {
                return p.then(function () {
                    var t = msg(null);
                    return wait(Math.min(900, 350 + part.length * 4)).then(function () {
                        t.querySelector('.ob-bub').textContent = part; out.lastElementChild.classList.remove('is-cont');
                        var prev = t.previousElementSibling; if (prev) prev.classList.add('is-cont');
                    });
                });
            }, Promise.resolve());
        }
        function load() {
            if (loaded) return; loaded = true;
            var first = msg(null);
            var tok = document.querySelector('meta[name="csrf-token"]');
            var ctl = window.AbortController ? new AbortController() : null;
            var kill = setTimeout(function () { if (ctl) ctl.abort(); }, 25000);
            fetch(box.dataset.url, { method: 'POST', signal: ctl ? ctl.signal : undefined, headers: { 'X-CSRF-TOKEN': tok ? tok.content : '', 'Accept': 'application/json' } })
                .then(function (r) { if (!r.ok) throw 0; return r.json(); })
                .then(function (j) { if (note && !j.ai) note.hidden = false; return j.text || FALLBACK; })
                .catch(function () { if (note) note.hidden = false; return FALLBACK; }) // gagal pun tetap ada jawaban, tidak ada pesan error
                .then(function (text) { clearTimeout(kill); first.remove(); return play(text); });
        }
        // Dimuat saat tab dibuka supaya tidak membuang kuota bila tab tidak pernah dilihat.
        var pane = box.closest('[data-p]');
        if (!pane || !pane.hidden) { load(); return; }
        new MutationObserver(function () { if (!pane.hidden) load(); }).observe(pane, { attributes: true, attributeFilter: ['hidden'] });
    })();
</script>
