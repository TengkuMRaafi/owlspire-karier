{{-- Kerangka semua halaman error. Sengaja berdiri sendiri: tanpa sesi, tanpa database, tanpa app.css/app.js,
     supaya halaman ini tetap tampil walau server atau bundel utama bermasalah. --}}
@php
    $erAssets = '';
    try {
        // Bila build belum memuat errors.css (mis. lupa npm run build), halaman tetap tampil tanpa gaya, bukan malah error baru.
        $erAssets = (string) app(\Illuminate\Foundation\Vite::class)(['resources/css/errors.css', 'resources/js/errors.js']);
    } catch (\Throwable) {
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0e1626">
    <title>@yield('title', 'Terjadi kesalahan') | {{ config('platform.brand.name', 'Owlspire') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ url('/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    {!! $erAssets !!}
</head>
<body class="er">
    <header class="er-top"><a class="er-brand" href="{{ url('/') }}" aria-label="Owlspire, ke beranda"><svg class="er-mark" viewBox="0 0 120 140" aria-hidden="true" focusable="false"><path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136 14 114Q8 108 8 100V72Q8 60 18 54L4 22 36 32Z" fill="#4a7fdc"/><path d="M60 4 84 32 60 52 36 32Z" fill="#3b6cd4"/><path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136Z" fill="#a9c4f2" opacity=".55"/><path d="M4 22 36 32 22 52Q14 54 14 60Z" fill="#2f58b3"/><path d="M116 22 84 32 98 52Q106 54 106 60Z" fill="#2f58b3"/><path d="M60 46V16M50 26 60 14 70 26" fill="none" stroke="#2f58b3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 56Q36 52 54 64V70Q34 62 14 62Z" fill="#2f58b3"/><path d="M106 56Q84 52 66 64V70Q86 62 106 62Z" fill="#2f58b3"/><circle cx="36" cy="76" r="17" fill="#fff"/><circle cx="84" cy="76" r="17" fill="#fff"/><circle cx="36" cy="76" r="10.5" fill="#f0b429"/><circle cx="84" cy="76" r="10.5" fill="#f0b429"/><circle cx="36" cy="76" r="5.8" fill="#1f2a3d"/><circle cx="84" cy="76" r="5.8" fill="#1f2a3d"/><path d="M54 88H66L60 100Z" fill="#f5b942"/><path d="M48 112 60 102 72 112M48 120 60 110 72 120" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="er-word"><span>Owl</span><b>spire</b></span></a></header>

    <main class="er-wrap" id="isi">
        <div class="er-bg" aria-hidden="true"></div>
        <div class="er-inner">
            <div class="er-scene er-in" style="--d:.05s">
                <span class="er-big" aria-hidden="true">@yield('code')</span>
                @yield('scene')
            </div>
            <p class="er-code er-in" style="--d:.35s">Error @yield('code')</p>
            <h1 class="er-title er-in" style="--d:.45s">@yield('heading')</h1>
            <p class="er-lead er-in" style="--d:.55s">@yield('lead')</p>
            @yield('extra')
            <div class="er-actions er-in" style="--d:.8s">@yield('actions')</div>
            @hasSection('note')
                <p class="er-note er-in" style="--d:1.05s">@yield('note')</p>
            @endif
        </div>
    </main>
</body>
</html>
