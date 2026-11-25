@extends('layouts.app')

@section('title', 'Hasil '.$type->name)
@section('description', $name.' meraih '.$pct.'% di simulasi '.$type->name.'. Coba simulasinya di '.config('platform.brand.name').'.')

@push('meta')
    {{-- Halaman hasil pribadi: boleh dibuka lewat tautan, tapi tidak untuk diindeks mesin pencari. --}}
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('platform.brand.name') }}">
    <meta property="og:title" content="{{ $name }} meraih {{ $pct }}% di simulasi {{ $type->name }}">
    <meta property="og:description" content="Coba simulasinya dan lihat skormu sendiri.">
    <meta property="og:url" content="{{ route('share.show', $attempt->share_token) }}">
    @if (file_exists(public_path('images/og-share.png')))
        <meta property="og:image" content="{{ asset('images/og-share.png') }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
@endpush

@section('content')
    <style>
        .sh { padding: 3rem 1rem 4rem; }
        .sh-card { position: relative; overflow: hidden; max-width: 30rem; margin: 0 auto; padding: 2rem 1.6rem 1.8rem; border-radius: 1.6rem; background: #0f1828; color: #fff; text-align: center; box-shadow: 0 24px 60px rgb(15 24 40 / .25); }
        .sh-card::before { content: ""; position: absolute; inset: 0; background: radial-gradient(26rem 18rem at 92% 0%, rgb(79 130 230 / .5), transparent 65%); pointer-events: none; }
        .sh-card > * { position: relative; }
        .sh-eyebrow { font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #9fb2d3; }
        .sh-title { margin-top: .5rem; font-family: var(--font-display, inherit); font-size: 1.55rem; font-weight: 700; line-height: 1.2; }
        .sh-who { margin-top: .35rem; font-size: .9rem; color: #b9c9e6; }
        .sh-ring { position: relative; width: 11.5rem; height: 11.5rem; margin: 1.5rem auto 0; }
        .sh-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); fill: none; stroke-width: 3.2; stroke-linecap: round; }
        .sh-ring .bg { stroke: rgb(255 255 255 / .13); }
        .sh-ring .fg { stroke: url(#sh-g); }
        .sh-ring div { position: absolute; inset: 0; display: grid; place-content: center; }
        .sh-ring b { font-size: 3rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .sh-ring small { margin-top: .2rem; font-size: .78rem; font-weight: 600; color: #9fb2d3; }
        .sh-badge { display: inline-block; margin-top: 1.3rem; padding: .5rem 1.1rem; border-radius: 99px; font-size: .86rem; font-weight: 800; background: #3b6cd4; }
        .sh-badge.is-ok { background: #1f9d68; } .sh-badge.is-no { background: #d9480f; }
        .sh-legs { display: flex; justify-content: center; flex-wrap: wrap; gap: .3rem 1.2rem; margin-top: 1.1rem; font-size: .86rem; color: #b9c9e6; }
        .sh-legs b { color: #fff; }
        .sh-date { margin-top: 1rem; font-size: .8rem; color: #7f93b8; }
        .sh-cta { max-width: 30rem; margin: 1.6rem auto 0; text-align: center; }
        .sh-cta p { font-size: .95rem; line-height: 1.6; color: var(--color-graphite-600, #4a5a73); }
        .sh-cta .btn-primary { margin-top: .9rem; }
        .sh-cta a.sh-more { display: block; margin-top: .8rem; font-size: .86rem; font-weight: 800; color: var(--color-accent-700, #2f58b3); }
    </style>

    <div class="sh">
        <article class="sh-card" aria-label="Hasil simulasi {{ $type->name }}">
            <p class="sh-eyebrow">Hasil simulasi</p>
            <h1 class="sh-title">{{ $type->name }}</h1>
            <p class="sh-who">{{ $name }}</p>

            <div class="sh-ring" role="img" aria-label="Skor {{ $pct }} persen">
                <svg viewBox="0 0 36 36" aria-hidden="true">
                    <defs><linearGradient id="sh-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7fb0ff"/><stop offset="1" stop-color="#4ee0b0"/></linearGradient></defs>
                    <circle class="bg" cx="18" cy="18" r="15.9155"/>
                    <circle class="fg" cx="18" cy="18" r="15.9155" stroke-dasharray="{{ $pct }} 100"/>
                </svg>
                <div><b>{{ $pct }}%</b><small>{{ $attempt->total_score }} poin</small></div>
            </div>

            @if ($isFull)
                <span @class(['sh-badge', 'is-ok' => $attempt->passed, 'is-no' => ! $attempt->passed])>{{ $attempt->passed ? 'Lulus passing grade' : 'Belum lulus passing grade' }}</span>
            @else
                <span class="sh-badge">Mode coba</span>
            @endif

            <p class="sh-legs"><span><b>{{ $benar }}</b> benar</span><span><b>{{ $salah }}</b> salah</span><span><b>{{ $kosong }}</b> kosong</span></p>
            <p class="sh-date">{{ $attempt->finished_at->translatedFormat('d F Y') }}</p>
        </article>

        <div class="sh-cta">
            <p>Penasaran skormu sendiri? Coba simulasi {{ $type->name }} dengan timer dan penilaian yang dihitung server.</p>
            <a href="{{ route('exam.show', $type) }}" class="btn-primary">Coba simulasi ini</a>
            <a href="{{ route('exam.index') }}" class="sh-more">Lihat semua simulasi</a>
        </div>
    </div>
@endsection
