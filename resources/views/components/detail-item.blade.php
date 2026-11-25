{{-- Satu keterangan: ikon + label kecil + nilai (slot). Bungkus beberapa dalam <div class="ad-d-info">. --}}
@props(['icon', 'label', 'wide' => false])
<div @class(['ad-d-i', 'is-wide' => $wide])>
    <span class="ad-d-i-ic" aria-hidden="true"><x-icon :name="$icon" /></span>
    <div><small>{{ $label }}</small><b>{{ $slot }}</b></div>
</div>
