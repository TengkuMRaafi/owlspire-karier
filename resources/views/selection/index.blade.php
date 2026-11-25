@extends('layouts.app')

@section('title', 'Info seleksi')
@section('description', 'Jadwal pendaftaran dan tahapan seleksi CPNS, BUMN, Polri/TNI, lengkap dengan hitung mundur dan tautan pengumuman resmi.')

@section('content')
    @php
        $next = $upcoming->first();
        $nw = $next?->window();
        $secs = $nw ? max(0, $nw['target']->getTimestamp() - now()->getTimestamp()) : 0;
    @endphp
    <style>
        .sx-hero { position: relative; overflow: hidden; background: #0f1828; color: #fff; }
        .sx-hero::before { content: ""; position: absolute; inset: 0; background: radial-gradient(40rem 22rem at 88% 0%, rgb(79 130 230 / .5), transparent 62%); pointer-events: none; }
        .sx-in { position: relative; max-width: 56rem; margin: 0 auto; padding: 3rem 1rem 3.2rem; display: grid; gap: 1.6rem 3rem; grid-template-columns: minmax(0, 1fr) auto; align-items: center; }
        @media (max-width: 860px) { .sx-in { grid-template-columns: minmax(0, 1fr); } }
        .sx-eyebrow { font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #9fb2d3; }
        .sx-h { margin-top: .5rem; font-family: var(--font-display, inherit); font-size: clamp(1.9rem, 4.4vw, 2.9rem); font-weight: 700; line-height: 1.08; letter-spacing: -.01em; }
        .sx-h em { font-style: italic; color: var(--color-accent-400, #7fa8e8); }
        .sx-p { max-width: 34rem; margin-top: .8rem; line-height: 1.6; color: #b9c9e6; }
        .sx-next { padding: 1.2rem 1.4rem; border: 1px solid rgb(255 255 255 / .14); border-radius: 1.2rem; background: rgb(255 255 255 / .06); min-width: 19rem; }
        .sx-next small { font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #9fb2d3; }
        .sx-next h2 { margin-top: .3rem; font-size: 1.02rem; font-weight: 800; line-height: 1.3; }
        .sx-next p { margin-top: .2rem; font-size: .84rem; color: #b9c9e6; }
        .sx-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-top: 1rem; text-align: center; }
        .sx-tiles div { padding: .65rem .2rem; border-radius: .8rem; background: rgb(255 255 255 / .09); }
        .sx-tiles b { display: block; font-size: 1.6rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .sx-tiles span { display: block; margin-top: .3rem; font-size: .68rem; font-weight: 700; color: #9fb2d3; }
        .sx-body { max-width: 56rem; margin: 0 auto; padding: 2rem 1rem 4rem; display: grid; gap: 2rem; }
        .sx-sec h2 { margin-bottom: .8rem; font-size: 1.1rem; font-weight: 800; color: var(--color-graphite-800, #1f2a3d); }
        .sx-empty { padding: 2rem 1.2rem; border: 1.5px dashed var(--color-graphite-300, #c5cfde); border-radius: 1.1rem; text-align: center; color: var(--color-graphite-600, #4a5a73); line-height: 1.6; }
        .sx-past summary { cursor: pointer; font-size: 1rem; font-weight: 800; color: var(--color-graphite-700, #34435c); }
        .sx-past .sl-list { margin-top: .8rem; }
    </style>

    <header class="sx-hero">
        <div class="sx-in">
            <div>
                <p class="sx-eyebrow">Info seleksi</p>
                <h1 class="sx-h">Jangan ketinggalan <em>jadwal</em> seleksi.</h1>
                <p class="sx-p">Pendaftaran dan tahapan CPNS, BUMN, Polri/TNI dalam satu daftar, lengkap dengan hitung mundur dan tautan pengumuman resmi.</p>
            </div>
            @if ($next)
                <div class="sx-next" aria-label="Seleksi terdekat">
                    <small>{{ $nw['state'] === 'live' ? 'Sedang berlangsung, berakhir dalam' : 'Seleksi terdekat, mulai dalam' }}</small>
                    <h2>{{ $next->title }}</h2>
                    <p>{{ $next->categoryLabel() }}@if ($next->stage) &middot; {{ $next->stage }}@endif &middot; {{ $next->rangeLabel() }}</p>
                    <div class="sx-tiles" data-sx-count="{{ $nw['target']->getTimestamp() * 1000 }}" role="timer" aria-label="Hitung mundur">
                        <div><b data-u="d">{{ intdiv($secs, 86400) }}</b><span>hari</span></div>
                        <div><b data-u="h">{{ str_pad((string) intdiv($secs % 86400, 3600), 2, '0', STR_PAD_LEFT) }}</b><span>jam</span></div>
                        <div><b data-u="m">{{ str_pad((string) intdiv($secs % 3600, 60), 2, '0', STR_PAD_LEFT) }}</b><span>menit</span></div>
                        <div><b data-u="s">{{ str_pad((string) ($secs % 60), 2, '0', STR_PAD_LEFT) }}</b><span>detik</span></div>
                    </div>
                </div>
            @endif
        </div>
    </header>

    <div class="sx-body">
        @include('selection.partials.filters', ['route' => 'selection.index', 'category' => $category])

        <section class="sx-sec" aria-labelledby="sx-up">
            <h2 id="sx-up">Akan datang dan berlangsung</h2>
            @if ($upcoming->isNotEmpty())
                @include('selection.partials.list', ['events' => $upcoming])
            @else
                <div class="sx-empty">
                    <p>Belum ada jadwal{{ $category !== '' ? ' untuk kategori ini' : '' }}. Sambil menunggu pengumuman, asah dulu kemampuanmu lewat simulasi.</p>
                    <a href="{{ route('exam.index') }}" class="btn-primary" style="margin-top: .9rem">Coba simulasi</a>
                </div>
            @endif
        </section>

        @if ($past->isNotEmpty())
            <details class="sx-past">
                <summary>Sudah lewat</summary>
                @include('selection.partials.list', ['events' => $past])
            </details>
        @endif

        <section class="sx-sec" aria-labelledby="sx-portal">
            <h2 id="sx-portal">Portal resmi</h2>
            @include('selection.partials.portals', ['portals' => $portals])
        </section>
    </div>

    @if ($next)
        <script>
            (function () {
                var box = document.querySelector('[data-sx-count]');
                if (!box) return;
                var target = Number(box.getAttribute('data-sx-count'));
                var el = function (u) { return box.querySelector('[data-u="' + u + '"]'); };
                var pad = function (n) { return n < 10 ? '0' + n : String(n); };
                function tick() {
                    var s = Math.max(0, Math.floor((target - Date.now()) / 1000));
                    el('d').textContent = Math.floor(s / 86400);
                    el('h').textContent = pad(Math.floor(s % 86400 / 3600));
                    el('m').textContent = pad(Math.floor(s % 3600 / 60));
                    el('s').textContent = pad(s % 60);
                    if (s === 0) clearInterval(timer);
                }
                var timer = setInterval(tick, 1000);
                tick();
            })();
        </script>
    @endif
@endsection
