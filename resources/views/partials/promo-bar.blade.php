{{-- Banner promo: pita pendek melengkung yang menempel di bawah navbar (ikut saat di-scroll).
     Tidak bisa ditutup: tampil sampai waktu promo habis. Isi otomatis dari diskon paket Premium yang berjalan; admin bisa menimpa angka dan teksnya di Pengaturan > Fitur dan kuota > Banner promo.
     Pakai: @include('partials.promo-bar') (situs) atau @include('partials.promo-bar', ['inset' => true]) (di dalam panel akun). --}}
{{-- Mode pratinjau (Pengaturan admin): kirim ['promoPreview' => [...isi banner...]]. Isi diperbarui oleh skrip formulir, bukan hitung mundur asli. --}}
@php
    // Nama khusus 'promoPreview' (bukan 'preview'): partial mewarisi variabel view induk, dan halaman seperti /cv sudah punya $preview sendiri.
    $pv = $promoPreview ?? null;
    $pbUser = auth()->user();
    $pbHide = ! $pv && (request()->routeIs('billing.*', 'admin.*')
        || ($pbUser && app(\App\Services\Billing\EntitlementService::class)->isPremium($pbUser->id)));
    $pb = $pv ?: ($pbHide ? null : app(\App\Services\Billing\Promo::class)->banner());
@endphp
@if ($pb)
    <div @class(['pb-bar', 'is-inset' => ! empty($inset)]) role="region" aria-label="{{ $pv ? 'Pratinjau banner promo' : 'Promo Premium' }}" data-tone="{{ $pb['tone'] }}" @unless ($pv) data-promo-bar data-promo-key="{{ $pb['key'] }}" @endunless>
        <div class="pb-bg" aria-hidden="true">
            <svg class="pb-wave pb-wave-l" viewBox="0 0 160 100" preserveAspectRatio="none">
                <defs><linearGradient id="pbgl" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--pb-a)"/><stop offset="1" style="stop-color:var(--pb-b)"/></linearGradient></defs>
                <path d="M0 100V36C28 26 58 50 80 72c14 14 36 24 80 28Z" fill="url(#pbgl)"/>
                <path class="pb-w2" d="M0 100V64c24-4 44 12 60 26 10 8 30 10 50 10Z" fill="#fff" fill-opacity=".2"/>
            </svg>
            <svg class="pb-wave pb-wave-r" viewBox="0 0 420 100" preserveAspectRatio="none">
                <defs><linearGradient id="pbgr" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--pb-a)"/><stop offset="1" style="stop-color:var(--pb-b)"/></linearGradient></defs>
                <path class="pb-w3" d="M96 0C66 22 122 46 84 70c-18 12-8 24 8 30h328V0Z" fill="#fff" fill-opacity=".14"/>
                <path d="M62 0C32 22 88 46 50 70c-18 12-8 24 8 30h362V0Z" fill="url(#pbgr)"/>
                <path class="pb-w2" d="M150 0c-22 20 14 42-6 64-10 12-2 28 10 36h266V0Z" fill="#fff" fill-opacity=".12"/>
            </svg>
            <i class="pb-shine"></i>
            <i class="pb-deco pb-d1"></i><i class="pb-deco pb-d3"></i>
            <svg class="pb-deco pb-d2" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3.6" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
            <svg class="pb-deco pb-d4" viewBox="0 0 10 10"><path d="M5 1v8M1 5h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <svg class="pb-deco pb-d5" viewBox="0 0 10 10"><path d="M5 1.2 9.2 9H.8Z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
            <svg class="pb-deco pb-d6" viewBox="0 0 10 10"><path d="M5 1v8M1 5h8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        </div>
        <a href="{{ $pv ? '#' : $pb['url'] }}" class="pb-in" @if ($pv) tabindex="-1" onclick="return false" @endif>
            <span class="pb-main">
                <span class="pb-top">
                    <small class="pb-eyebrow" data-pv="eyebrow">{{ $pb['eyebrow'] }}</small>
                    @if ($pv)
                        <span class="pb-left" data-pv="left" hidden>Berakhir dalam <time data-pv="left-t"></time></span>
                    @elseif ($pb['ends_at'])
                        <span class="pb-left">Berakhir dalam <time data-promo-countdown="{{ $pb['ends_at']->getTimestamp() * 1000 }}" datetime="{{ $pb['ends_at']->toIso8601String() }}">{{ $pb['ends_at']->diffForHumans(null, true) }}</time></span>
                    @endif
                </span>
                <span class="pb-row">
                    <b class="pb-head" data-pv="headline">{{ $pb['headline'] }}</b>
                    <span class="pb-tag" data-pv="cta">{{ $pb['cta'] }}</span>
                </span>
            </span>
            @if ($pb['number'] !== '' || $pv)
                <span class="pb-off" data-pv="off" @if ($pb['number'] === '') hidden @endif>
                    @if ($pb['prefix'] !== '' || $pv)<small data-pv="prefix" @if ($pb['prefix'] === '') hidden @endif>{{ $pb['prefix'] }}</small>@endif
                    <strong @unless ($pv) data-pb-count @endunless data-pv="number">{{ $pb['number'] }}</strong>
                    @if ($pb['suffix'] !== '' || $pv)<small data-pv="suffix" @if ($pb['suffix'] === '') hidden @endif>{{ $pb['suffix'] }}</small>@endif
                </span>
            @endif
        </a>
    </div>
@endif
