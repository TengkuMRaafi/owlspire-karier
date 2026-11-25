{{-- Satu kotak info di Pengaturan admin: ikon, label kecil, nilai (badge bila ada nada status). --}}
<div class="ad-st-tile">
    <span class="ad-st-tile-ic" aria-hidden="true"><x-icon :name="$icon ?? 'info'" /></span>
    <div class="ad-st-tile-t">
        <small>{{ $label }}</small>
        @if ($tone)
            <span class="ui-badge {{ $tones[$tone] ?? '' }}">{{ $value }}</span>
        @else
            <b title="{{ $value }}">{{ $value }}</b>
        @endif
    </div>
</div>
