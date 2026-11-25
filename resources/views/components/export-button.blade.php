{{-- Tombol unduh Excel: satu bentuk untuk semua halaman admin (Laporan, Pengguna). Pakai: <x-export-button :href="route(...)" />. --}}
@props(['href', 'label' => 'Unduh Excel'])
<a href="{{ $href }}" {{ $attributes->class(['btn-outline']) }}><x-icon name="download" class="size-4" /> {{ $label }}</a>
