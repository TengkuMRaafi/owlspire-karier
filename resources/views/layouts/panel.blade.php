{{--
    Shell panel bersama untuk admin dan akun pengguna. Dipanggil dari layouts/admin dan layouts/dashboard
    dengan $area = 'admin' | 'account'. Data sidebar disiapkan App\View\Composers\PanelComposer;
    isi menu ada di config/panel.php; semua gaya ada di resources/css/layouts/panel.css.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    {{-- Perataan teks di dalam panel (admin, staf, pengguna): baris dipatahkan lebih rapi (tanpa kata sendirian di baris akhir dan tanpa ujung kanan yang menjorok jauh). Tetap rata kiri, bukan justify. --}}
    <style>
        .pn-body { text-wrap: pretty; }
        .pn-body :where(h1, h2, h3, h4) { text-wrap: balance; }
        .pn-body :where(pre, code, textarea, input, select) { text-wrap: wrap; }
    </style>
    {{-- Sidebar desktop dikunci ke layar (fixed). Versi sticky lama bisa ikut tergulung dan menyisakan kolom putih saat scroll.
         Ditulis inline supaya langsung jalan tanpa npm run build; isinya sama dengan resources/css/layouts/panel.css. --}}
    <style>
        @media (min-width: 1024px) {
            .pn-side { position: fixed; inset: 0 auto 0 0; width: var(--pn-w); height: 100vh; height: 100dvh; }
            .pn-body { grid-column: 2; }
        }
        @media (max-width: 1023px) { .pn-body { grid-column: 1; } }
    </style>
    {{-- Tema panel (terang/gelap) dipulihkan sebelum halaman digambar. Hanya berlaku di panel, bukan situs publik. --}}
    <script>try { if (localStorage.getItem('owlspire_theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}</script>
    @if ($isAdminArea)<meta name="robots" content="noindex, nofollow">@endif
    <title>@yield('title', $isAdminArea ? 'Admin' : 'Dashboard') | {{ $isAdminArea ? 'Admin ' : '' }}{{ config('platform.brand.name') }}</title>
</head>
<body class="bg-surface font-sans text-graphite-700 antialiased">
    @include('layouts.loader')
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-bold">Lewati ke konten</a>

    <div class="pn" data-panel>
        {{-- Pulihkan sidebar yang diciutkan sebelum halaman digambar, supaya tidak berkedip. --}}
        <script>try { if (localStorage.getItem('owlspire_pn_collapsed') === '1') document.currentScript.parentElement.classList.add('is-collapsed'); } catch (e) {}</script>

        <aside class="pn-side" id="pn-side" aria-label="Sidebar">
            <div class="pn-brand">
                <a href="{{ $navHome }}" aria-label="{{ $isAdminArea ? 'Dashboard admin' : 'Dashboard' }}"><x-logo :light="true" class="pn-logo" /></a>
                @if ($navBadge)<span class="pn-badge">{{ $navBadge }}</span>@endif
            </div>

            <nav class="pn-nav" aria-label="{{ $isAdminArea ? 'Menu admin' : 'Menu akun' }}">
                @foreach ($nav as $group)
                    <div class="pn-group">
                        <p class="pn-label">{{ $group['label'] }}</p>
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['url'] }}" title="{{ $item['label'] }}" @class(['pn-link', 'is-active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                                <x-icon :name="$item['icon']" class="pn-ic" />
                                <span class="pn-text">{{ $item['label'] }}</span>
                                @if ($item['locked'])
                                    <span class="pn-pro">PRO</span>
                                @elseif ($item['count'] > 0)
                                    <span class="pn-count" aria-label="{{ $item['count'] }} menunggu">{{ $item['count'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="pn-foot">
                @unless ($isAdminArea)
                    @if ($isInternal)
                        <div class="pn-plan is-premium">
                            <p class="pn-plan-t"><x-icon name="shield" /> Akses penuh</p>
                            <p>{{ $meCaption }}: semua fitur terbuka, tanpa paket.</p>
                            @if ($adminUrl)<a href="{{ $adminUrl }}" class="btn-primary btn-sm">Buka panel</a>@endif
                        </div>
                    @elseif ($isPremium)
                        <div class="pn-plan is-premium">
                            <p class="pn-plan-t"><x-icon name="crown" /> Premium aktif</p>
                            <p>{{ $premiumUntil ? 'Berlaku sampai '.$premiumUntil->translatedFormat('d M Y').'.' : 'Tanpa batas waktu.' }}</p>
                        </div>
                    @else
                        <div class="pn-plan">
                            <p class="pn-plan-t"><x-icon name="sparkle" /> Paket Gratis</p>
                            <p>Buka ujian penuh, analisis CV, dan semua materi belajar.</p>
                            <a href="{{ $upgradeUrl }}" class="btn-primary btn-sm">Naik ke Premium</a>
                        </div>
                    @endif
                @endunless

                <div class="pn-me">
                    <span class="pn-avatar" aria-hidden="true">{{ $meInitial }}</span>
                    <span class="pn-who"><b>{{ $meName }}</b><small>{{ $meCaption }}</small></span>
                    <form method="POST" action="{{ route('logout') }}" data-confirm="Kamu akan keluar dari akunmu di perangkat ini." data-confirm-title="Keluar dari Owlspire?" data-confirm-ok="Ya, keluar" data-confirm-tone="logout">
                        @csrf
                        <button type="submit" class="pn-out" title="Keluar" aria-label="Keluar"><x-icon name="logout" /></button>
                    </form>
                </div>
            </div>
        </aside>
        <div class="pn-scrim" data-panel-scrim aria-hidden="true"></div>

        <div class="pn-body">
            {{-- Navbar atas: hanya tema, riwayat, notifikasi, dan profil. Judul halaman ada di dalam isi. --}}
            <header class="pn-top">
                <button type="button" class="pn-toggle" data-panel-toggle aria-controls="pn-side" aria-expanded="true" aria-label="Buka atau ciutkan menu">
                    <x-icon name="panel" class="is-desktop" />
                    <x-icon name="menu" class="is-mobile" />
                </button>

                <div class="pn-tools">
                    <button type="button" class="pn-tool" data-theme-toggle aria-pressed="false" aria-label="Ganti tema terang atau gelap" title="Tema">
                        <x-icon name="moon" class="pn-theme-moon" />
                        <x-icon name="sun" class="pn-theme-sun" />
                    </button>

                    {{-- Riwayat: aktivitas terbaru --}}
                    <div class="pn-pop-wrap" data-pn-pop>
                        <button type="button" class="pn-tool" data-pn-pop-btn aria-expanded="false" aria-haspopup="true" aria-label="Riwayat" title="Riwayat">
                            <x-icon name="history" />
                        </button>
                        <div class="pn-pop is-right" hidden>
                            <p class="pn-pop-h">Riwayat terbaru</p>
                            @forelse ($history as $h)
                                <a href="{{ $h['url'] }}" class="pn-pop-item"><x-icon :name="$h['icon']" /><span>{{ $h['text'] }}<small>{{ $h['time'] }}</small></span></a>
                            @empty
                                <p class="pn-pop-empty">Belum ada aktivitas.</p>
                            @endforelse
                            @if ($historyUrl)<a href="{{ $historyUrl }}" class="pn-pop-more">Lihat semua riwayat</a>@endif
                        </div>
                    </div>

                    {{-- Notifikasi: hal yang perlu perhatian (pesanan, video, ujian berjalan) --}}
                    <div class="pn-pop-wrap" data-pn-pop>
                        <button type="button" class="pn-tool" data-pn-pop-btn aria-expanded="false" aria-haspopup="true" aria-label="Notifikasi{{ count($notices) ? ', '.count($notices).' baru' : '' }}" title="Notifikasi">
                            <x-icon name="bell" />
                            @if (count($notices))<span class="pn-dot" aria-hidden="true">{{ count($notices) }}</span>@endif
                        </button>
                        <div class="pn-pop is-right" hidden>
                            <p class="pn-pop-h">Notifikasi</p>
                            @forelse ($notices as $n)
                                <a href="{{ $n['url'] }}" class="pn-pop-item"><x-icon :name="$n['icon']" /><span>{{ $n['text'] }}</span></a>
                            @empty
                                <p class="pn-pop-empty">Tidak ada yang perlu diurus.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Profil: kartu pratinjau akun (sampul, avatar, peran, tanggal bergabung), pengaturan, keluar --}}
                    <div class="pn-pop-wrap" data-pn-pop>
                        <button type="button" class="pn-acct" data-pn-pop-btn aria-expanded="false" aria-haspopup="true" aria-label="Menu profil {{ $meName }}">
                            <span class="pn-avatar" aria-hidden="true">{{ $meInitial }}</span>
                            <span class="pn-acct-name">{{ $meName }}</span>
                            <x-icon name="chevron-down" class="pn-acct-chev" />
                        </button>
                        <div class="pn-pop pn-pop-acct is-right" hidden>
                            <div class="pn-prev">
                                <span class="pn-prev-cover" aria-hidden="true"></span>
                                <span class="pn-avatar is-lg" aria-hidden="true">{{ $meInitial }}</span>
                                <b class="pn-prev-name">{{ $meName }}</b>
                                <small class="pn-prev-mail">{{ $meEmail }}</small>
                                <div class="pn-prev-chips">
                                    <span class="pn-prev-chip is-role"><x-icon :name="$isInternal ? 'shield' : ($isPremium ? 'crown' : 'sparkle')" />{{ $meCaption }}</span>
                                    @if ($meJoined)<span class="pn-prev-chip"><x-icon name="calendar" />Sejak {{ $meJoined }}</span>@endif
                                </div>
                            </div>
                            <div class="pn-prev-menu">
                                @if ($profileUrl)<a href="{{ $profileUrl }}" class="pn-pop-item"><x-icon name="user" /><span>Profil &amp; pengaturan</span></a>@endif
                                <a href="{{ $siteUrl }}" class="pn-pop-item"><x-icon name="arrow-right" /><span>Ke situs</span></a>
                                <form method="POST" action="{{ route('logout') }}" data-confirm="Kamu akan keluar dari akunmu di perangkat ini." data-confirm-title="Keluar dari Owlspire?" data-confirm-ok="Ya, keluar" data-confirm-tone="logout">
                                    @csrf
                                    <button type="submit" class="pn-pop-item is-out"><x-icon name="logout" /><span>Keluar</span></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="pn-main" id="isi">
                <div class="pn-wrap">
                    <div class="pn-head">
                        <div class="pn-heading">
                            <h1>@yield('heading', $isAdminArea ? 'Admin' : 'Dashboard')</h1>
                            @hasSection('subheading')<p>@yield('subheading')</p>@endif
                        </div>
                        <div class="pn-actions">@yield('actions')</div>
                    </div>
                    @include('partials.flash', ['class' => 'pn-flash', 'showErrors' => true])
                    @include('partials.banners')
                    @include('partials.promo-bar', ['inset' => true])
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
