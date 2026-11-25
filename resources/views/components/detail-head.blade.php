{{-- Judul kartu detail dengan ikon kecil. Isi slot tampil di kanan (badge atau tautan). --}}
@props(['icon', 'title'])
<div class="pn-card-h ad-d-h">
    <div class="ad-d-h-t"><span class="ad-d-h-ic" aria-hidden="true"><x-icon :name="$icon" /></span><h2>{{ $title }}</h2></div>
    @if (trim((string) $slot) !== '')<div class="ad-d-h-end">{{ $slot }}</div>@endif
</div>
