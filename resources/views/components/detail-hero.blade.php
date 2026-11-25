{{-- Kepala halaman detail admin: avatar/ikon/gambar, judul, chip, dan angka utama di sisi kanan.
     Pakai: <x-detail-hero title="..." sub="..." initial="B" icon="cart" image="/img.svg"> chip... <x-slot:side>...</x-slot:side></x-detail-hero> --}}
@props(['title', 'sub' => null, 'initial' => null, 'icon' => null, 'image' => null])
<section class="ad-d-hero">
    <div class="ad-d-main">
        @if ($image)
            <span class="ad-d-av is-img" aria-hidden="true"><img src="{{ $image }}" alt="" loading="lazy" decoding="async"></span>
        @elseif ($initial)
            <span class="ad-d-av" aria-hidden="true">{{ $initial }}</span>
        @elseif ($icon)
            <span class="ad-d-av is-icon" aria-hidden="true"><x-icon :name="$icon" /></span>
        @endif
        <div class="ad-d-t">
            <h2>{{ $title }}</h2>
            @if ($sub)<p>{{ $sub }}</p>@endif
            @if (trim((string) $slot) !== '')<div class="ad-d-chips">{{ $slot }}</div>@endif
        </div>
    </div>
    @isset($side)<div class="ad-d-side">{{ $side }}</div>@endisset
</section>
