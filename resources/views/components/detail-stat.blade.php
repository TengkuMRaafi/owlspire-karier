{{-- Angka ringkas di atas detail; memakai gaya ui-stat yang sama dengan dashboard. --}}
@props(['icon', 'label', 'value', 'hint' => null, 'accent' => false])
<div @class(['ui-stat', 'is-accent' => $accent])>
    <div class="ui-stat-top"><span class="ui-stat-l">{{ $label }}</span><span class="ui-stat-ic"><x-icon :name="$icon" /></span></div>
    <p class="ui-stat-v">{{ $value }}</p>
    @if ($hint)<p class="ui-stat-h">{{ $hint }}</p>@endif
</div>
