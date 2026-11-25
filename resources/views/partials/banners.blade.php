{{-- Spanduk di atas halaman: pengumuman, mode pemeliharaan (untuk admin), masuk-sebagai, email belum diverifikasi, saran 2FA admin.
     Di situs publik (['public' => true]) hanya pengumuman dan penanda masuk-sebagai yang tampil. --}}
@php
    $bn = [];
    $pub = ! empty($public);
    $site = app(\App\Services\Settings\SiteSettings::class);
    $bUser = auth()->user();
    $ann = $site->announcement();
    if ($ann['enabled']) {
        $bn[] = ['tone' => $ann['tone'], 'text' => $ann['text'], 'url' => $ann['url'], 'label' => $ann['url_label'] ?: 'Selengkapnya'];
    }
    if (! $pub && $bUser && $bUser->isInternal() && $site->maintenance()['enabled']) {
        $bn[] = ['tone' => 'warning', 'text' => 'Mode pemeliharaan sedang aktif. Pengunjung biasa melihat halaman pemeliharaan; kamu masih bisa bekerja.', 'url' => route('admin.settings').'#fitur', 'label' => 'Atur'];
    }
    if ($bUser && session('impersonator_id')) {
        $bn[] = ['tone' => 'warning', 'text' => 'Kamu sedang masuk sebagai '.$bUser->name.' ('.$bUser->email.'). Perubahan sandi dan penghapusan akun dinonaktifkan.', 'form' => route('impersonate.stop'), 'label' => 'Kembali ke akun admin'];
    } elseif (! $pub && $bUser && ! $bUser->isInternal() && ! $bUser->hasVerifiedEmail() && ! request()->routeIs('verification.*')) {
        $bn[] = ['tone' => 'info', 'text' => 'Emailmu belum diverifikasi. Verifikasi supaya kamu bisa memulihkan akun lewat email.', 'form' => route('verification.send'), 'label' => 'Kirim link verifikasi'];
    } elseif (! $pub && $bUser && $bUser->isAdmin() && ! $bUser->hasTwoFactor() && ! request()->routeIs('account.settings*')) {
        $bn[] = ['tone' => 'info', 'text' => 'Akun admin belum memakai verifikasi 2 langkah. Aktifkan supaya akun ini lebih sulit dibobol.', 'url' => route('account.settings').'#keamanan', 'label' => 'Aktifkan sekarang'];
    }
@endphp
@if ($bn)
    <style>
        .sx-banners{display:grid;gap:.5rem;margin:0 0 1rem}
        .sx-banner{display:flex;gap:.75rem;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:.65rem .9rem;border-radius:.75rem;font-size:.9rem;line-height:1.4;border:1px solid rgba(37,99,235,.25);background:rgba(37,99,235,.08);color:inherit}
        .sx-banner.is-warning{border-color:rgba(217,119,6,.35);background:rgba(245,158,11,.12)}
        .sx-banner.is-success{border-color:rgba(22,163,74,.3);background:rgba(34,197,94,.1)}
        .sx-banner a,.sx-banner button{font-weight:700;text-decoration:underline;background:none;border:0;padding:0;color:inherit;cursor:pointer;font-size:inherit}
        .sx-banner form{margin:0}
        @media (max-width:639px){
            /* Mobile: teks rata kiri dengan patahan baris dirapikan, tautan/tombol di baris sendiri */
            .sx-banner{gap:.55rem;padding:.75rem .95rem;font-size:.88rem}
            .sx-banner>span{flex:1 1 100%;text-align:left;text-wrap:pretty}
        }
    </style>
    <div class="sx-banners" role="region" aria-label="Pemberitahuan">
        @foreach ($bn as $b)
            <div class="sx-banner is-{{ $b['tone'] }}">
                <span>{{ $b['text'] }}</span>
                @if (! empty($b['form']))
                    <form method="POST" action="{{ $b['form'] }}">@csrf<button type="submit">{{ $b['label'] }}</button></form>
                @elseif (! empty($b['url']))
                    <a href="{{ $b['url'] }}">{{ $b['label'] }}</a>
                @endif
            </div>
        @endforeach
    </div>
@endif
