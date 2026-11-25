<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title', 'Beranda') | {{ config('platform.brand.name') }}</title>
    <meta name="description" content="@yield('description', config('platform.brand.description'))">
    @stack('meta')
</head>
<body class="min-h-screen bg-surface font-sans text-graphite-700 antialiased">
    @include('layouts.loader')
    @php
        // [route, label, segera?]
        $navItems = [
            ['learning.index', 'Belajar', ! config('platform.learning_open')],
            ['jobs.index', 'Lowongan', false],
        ];
        $loginUrl = Route::has('login') ? route('login') : route('dashboard.index');
        $registerUrl = Route::has('register') ? route('register') : $loginUrl;
    @endphp

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-bold">Lewati ke konten</a>

    <header class="site-header sticky top-0 z-30 border-b border-white/10 bg-graphite-900/95 text-white">
        <nav class="mx-auto flex max-w-6xl items-center gap-x-6 px-4 py-2 md:flex-wrap md:gap-y-2 md:py-3" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" aria-label="{{ config('platform.brand.name') }}, ke beranda">
                <x-logo :light="true" size="nav" />
            </a>

            <div class="nav-links hidden items-center gap-1 overflow-x-auto text-sm font-semibold md:flex md:w-auto md:flex-1">
                @foreach (array_merge([['home', 'Beranda', false]], $navItems) as [$r, $label, $soon])
                    @if (Route::has($r))
                        @php $on = request()->routeIs($r) || request()->routeIs(str_replace('.index', '.*', $r)); @endphp
                        <a href="{{ route($r) }}"
                           @class(['nav-link', 'is-active' => $on])
                           @if ($on) aria-current="page" @endif>
                            @if ($on)<span class="nav-pill" aria-hidden="true"></span>@endif
                            <span class="nav-label">{{ $label }}</span>
                            @if ($soon)<span class="nav-soon">Segera</span>@endif
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="ml-auto flex items-center gap-2 md:ml-0">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-bold text-accent-300 hover:text-white md:inline-block">Panel admin</a>
                    @endif
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard.index') }}" class="nav-me" title="Dashboard dan profil {{ auth()->user()->name }}">
                        <span class="nav-av" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="nav-me-name">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block" data-confirm="Kamu akan keluar dari akunmu di perangkat ini." data-confirm-title="Keluar dari Owlspire?" data-confirm-ok="Ya, keluar" data-confirm-tone="logout">
                        @csrf
                        <button type="submit" class="btn-outline-light">Keluar</button>
                    </form>
                @else
                    <a href="{{ $loginUrl }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-graphite-100 hover:text-white md:inline-block">Masuk</a>
                    <a href="{{ $registerUrl }}" class="btn-primary">Daftar</a>
                @endauth

                {{-- HP: menu jadi dropdown (Beranda, Belajar, Lowongan, akun) --}}
                <button type="button" class="nav-burger md:hidden" data-nav-toggle aria-expanded="false" aria-controls="nav-menu" aria-label="Buka menu"><i></i><i></i><i></i></button>
            </div>
        </nav>

        <div id="nav-menu" class="nav-menu md:hidden" data-nav-menu hidden>
            <div class="nav-menu-in">
                <div class="mx-auto max-w-6xl px-4 pb-4 pt-2">
                    <ul class="nav-m-list">
                        <li><a href="{{ route('home') }}" @class(['nav-m-link', 'is-active' => request()->routeIs('home')]) @if (request()->routeIs('home')) aria-current="page" @endif><span class="nav-m-l"><x-icon name="layout" class="nav-m-ic" />Beranda</span></a></li>
                        @foreach ($navItems as [$r, $label, $soon])
                            @if (Route::has($r))
                                @php $on = request()->routeIs($r) || request()->routeIs(str_replace('.index', '.*', $r)); $ic = ['learning.index' => 'book', 'jobs.index' => 'briefcase'][$r] ?? 'layers'; @endphp
                                <li><a href="{{ route($r) }}" @class(['nav-m-link', 'is-active' => $on]) @if ($on) aria-current="page" @endif>
                                    <span class="nav-m-l"><x-icon :name="$ic" class="nav-m-ic" />{{ $label }}</span>
                                    @if ($soon)<span class="nav-soon">Segera</span>@elseif ($r === 'learning.index')<span class="nav-new">Baru</span>@endif
                                </a></li>
                            @endif
                        @endforeach
                    </ul>
                    <div class="nav-m-foot">
                        @auth
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="nav-m-link">Panel admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" data-confirm="Kamu akan keluar dari akunmu di perangkat ini." data-confirm-title="Keluar dari Owlspire?" data-confirm-ok="Ya, keluar" data-confirm-tone="logout">
                                @csrf
                                <button type="submit" class="btn-outline-light">Keluar</button>
                            </form>
                        @else
                            <a href="{{ $loginUrl }}" class="btn-outline-light">Masuk</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>

    @include('partials.promo-bar')
    @php $siteBanners = trim(view('partials.banners', ['public' => true])->render()); @endphp
    @if ($siteBanners !== '')
        <div class="mx-auto max-w-6xl px-4 pt-4">{!! $siteBanners !!}</div>
    @endif
    @include('partials.flash', ['class' => 'ui-flash mx-auto mt-4 max-w-6xl px-4'])

    <main id="isi">@yield('content')</main>

    <footer class="ft mt-20">
        {{-- Rapikan footer di HP: lebih rapat, ditulis inline supaya langsung jalan tanpa build ulang --}}
        <style>
            @media (max-width: 639px) {
                .ft.mt-20 { margin-top: 2.5rem; }
                .ft .ft-main { gap: 1.1rem .9rem; padding-top: 1.7rem; padding-bottom: 1.3rem; }
                .ft .ft-about { max-width: none; margin-top: .5rem; font-size: .8rem; line-height: 1.5; }
                .ft .ft-cta { gap: .5rem; margin-top: .75rem; }
                .ft .ft-cta .btn-sm { padding: .4rem .85rem; font-size: .78rem; }
                .ft .ft-head { padding-bottom: .3rem; font-size: .8rem; }
                .ft .ft-col ul { gap: 0; margin-top: .25rem; }
                .ft .ft-col a { gap: .45rem; padding: .17rem 0; font-size: .8rem; line-height: 1.3; }
                .ft .ft-col a::before { width: .32rem; height: .32rem; }
                .ft .ft-col:last-of-type ul { gap: 0 1.1rem; }
                .ft .ft-base { padding: .65rem 0; font-size: .72rem; line-height: 1.45; }
            }
        </style>
        <div class="ft-main mx-auto max-w-6xl px-4">
            <div class="ft-brand">
                <a href="{{ route('home') }}" aria-label="{{ config('platform.brand.name') }}, ke beranda"><x-logo :light="true" :tagline="true" /></a>
                <p class="ft-about">Dari memilih karier, latihan ujian, belajar, sampai siap melamar, di satu tempat.</p>
                <div class="ft-cta">
                    @if (Route::has('careers.quiz'))
                        <a href="{{ route('careers.quiz') }}" class="btn-outline-light btn-sm">Cari arah kariermu</a>
                    @endif
                    <x-telegram-link class="btn-outline-light btn-sm" />
                </div>
            </div>

            @php
                // [judul kolom, [[route, label], ...]]; rute yang belum ada dilewati otomatis
                $footCols = [
                    ['Simulasi', [['exam.index', 'Simulasi & Karier'], ['exam.trial', 'Simulasi 5 Menit'], ['exam.leaderboard', 'Papan Peringkat']]],
                    ['Karier & Belajar', [['careers.quiz', 'Tes Arah Karier'], ['learning.index', 'Belajar'], ['cv.index', 'Analisis CV'], ['jobs.index', 'Lowongan']]],
                ];
            @endphp
            @foreach ($footCols as [$title, $links])
                <nav class="ft-col" aria-label="{{ $title }}">
                    <p class="ft-head">{{ $title }}</p>
                    <ul>
                        @foreach ($links as [$r, $label])
                            @if (Route::has($r))
                                <li><a href="{{ route($r) }}">{{ $label }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            <nav class="ft-col" aria-label="Akun">
                <p class="ft-head">Akun</p>
                <ul>
                    <li><a href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard.index') }}">{{ auth()->check() && auth()->user()->isAdmin() ? 'Panel admin' : 'Dashboard' }}</a></li>
                    @auth
                        <li><a href="{{ route('account.settings') }}">Pengaturan</a></li>
                    @else
                        <li><a href="{{ $loginUrl }}">Masuk</a></li>
                        <li><a href="{{ $registerUrl }}">Daftar</a></li>
                    @endauth
                </ul>
            </nav>
        </div>

        <div class="ft-base">
            <p class="mx-auto max-w-6xl px-4 text-center">&copy; {{ date('Y') }} {{ config('platform.brand.name') }}. {{ config('platform.brand.tagline') }}.</p>
        </div>
    </footer>
</body>
</html>
