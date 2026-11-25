<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <style>.au-form .au-or{margin:0}.au-card p.rapi.rapi{text-align:left;hyphens:none;-webkit-hyphens:none}</style>
    <title>@yield('title', 'Masuk') | {{ config('platform.brand.name') }}</title>
</head>
<body class="min-h-screen bg-surface font-sans text-graphite-700 antialiased">
    @include('layouts.loader')
    <div class="au">
        {{-- Panel maskot: hanya tampil di layar lebar --}}
        <aside class="au-aside on-dark" aria-label="Tentang {{ config('platform.brand.name') }}">
            <div class="au-bg" aria-hidden="true">
                <span class="au-glow au-glow-a"></span><span class="au-glow au-glow-b"></span>
                <span class="au-ring au-ring-1"></span><span class="au-ring au-ring-2"></span>
            </div>
            <a href="{{ route('home') }}" class="au-top inline-flex" aria-label="Ke beranda"><x-logo :light="true" :tagline="true" /></a>

            <div class="au-stage">
                <h2 class="au-title font-display">@yield('aside_title', 'Satu langkah lagi menuju <em>hasil yang kamu mau.</em>')</h2>
                <p class="au-sub">Tempat berlatih ujian seleksi, belajar, dan menemukan arah kariermu, untuk siapa saja yang mau maju.</p>
            </div>

            <p class="au-foot">&copy; {{ date('Y') }} {{ config('platform.brand.name') }} &middot; {{ config('platform.brand.tagline') }}</p>
        </aside>

        <main class="au-main">
            <div class="au-card">
                <a href="{{ route('home') }}" class="au-mobile-logo" aria-label="Ke beranda"><x-logo /></a>
                @include('partials.flash', ['class' => 'ui-flash mb-6', 'inline' => true])
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
