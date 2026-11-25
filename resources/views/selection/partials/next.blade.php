{{-- Kartu hitung mundur besar untuk seleksi terdekat. Param: $event (SelectionEvent). Dipakai di halaman Info seleksi dalam akun. --}}
@php
    $w = $event->window();
    $secs = max(0, $w['target']->getTimestamp() - now()->getTimestamp());
    $pad = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
@endphp
@once
    <style>
        .nx { padding: 1.7rem 1.8rem; border-radius: 1.5rem; background: radial-gradient(24rem 12rem at 100% 0%, rgb(79 130 230 / .45), transparent 65%), #1b2638; color: #fff; }
        .nx small { font-size: .74rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #9fb2d3; }
        .nx h2 { margin-top: .5rem; font-family: var(--font-display, inherit); font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .nx p { margin-top: .35rem; font-size: .88rem; color: #b9c9e6; }
        .nx-tiles { display: flex; gap: .6rem; margin-top: 1.2rem; }
        .nx-tiles div { flex: 1; padding: .85rem .3rem; border-radius: .95rem; background: rgb(255 255 255 / .1); text-align: center; }
        .nx-tiles b { display: block; font-size: 2.1rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .nx-tiles span { display: block; margin-top: .3rem; font-size: .72rem; font-weight: 700; color: #b9c9e6; }
        @media (max-width: 560px) { .nx { padding: 1.3rem 1.1rem; } .nx h2 { font-size: 1.25rem; } .nx-tiles { gap: .4rem; } .nx-tiles b { font-size: 1.5rem; } }
    </style>
@endonce
<section class="nx" aria-label="Seleksi terdekat">
    <small>{{ $w['state'] === 'live' ? 'Sedang berlangsung, berakhir dalam' : 'Seleksi terdekat, mulai dalam' }}</small>
    <h2>{{ $event->title }}</h2>
    <p>{{ $event->categoryLabel() }}@if ($event->stage) &middot; {{ $event->stage }}@endif &middot; {{ $event->rangeLabel() }}</p>
    <div class="nx-tiles" data-nx-count="{{ $w['target']->getTimestamp() * 1000 }}" role="timer" aria-label="Hitung mundur">
        <div><b data-u="d">{{ intdiv($secs, 86400) }}</b><span>hari</span></div>
        <div><b data-u="h">{{ $pad(intdiv($secs % 86400, 3600)) }}</b><span>jam</span></div>
        <div><b data-u="m">{{ $pad(intdiv($secs % 3600, 60)) }}</b><span>menit</span></div>
        <div><b data-u="s">{{ $pad($secs % 60) }}</b><span>detik</span></div>
    </div>
</section>
<script>
    (function () {
        var box = document.querySelector('[data-nx-count]');
        if (!box) return;
        var target = Number(box.getAttribute('data-nx-count'));
        var el = function (u) { return box.querySelector('[data-u="' + u + '"]'); };
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        function tick() {
            var s = Math.max(0, Math.floor((target - Date.now()) / 1000));
            el('d').textContent = Math.floor(s / 86400); el('h').textContent = pad(Math.floor(s % 86400 / 3600));
            el('m').textContent = pad(Math.floor(s % 3600 / 60)); el('s').textContent = pad(s % 60);
            if (s === 0) clearInterval(timer);
        }
        var timer = setInterval(tick, 1000); tick();
    })();
</script>
