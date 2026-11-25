{{-- Alert seragam. Pakai: <x-alert type="success" title="Berhasil">Pesan</x-alert>. type: success | error | warning | info --}}
@props(['type' => 'info', 'title' => null, 'dismiss' => false, 'autohide' => null])
@php
    $icon = ['success' => 'check-circle', 'error' => 'x-circle', 'warning' => 'alert', 'info' => 'info'][$type] ?? 'info';
    $role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';
@endphp
<div {{ $attributes->class(['ui-alert', 'is-'.$type]) }} role="{{ $role }}" @if ($autohide) data-autohide="{{ $autohide }}" @endif>
    <span class="ui-alert-ic"><x-icon :name="$icon" /></span>
    <div class="ui-alert-body">
        @if ($title)<p class="ui-alert-t">{{ $title }}</p>@endif
        {{ $slot }}
    </div>
    @if ($dismiss)
        <button type="button" class="ui-alert-x" data-alert-close aria-label="Tutup pesan"><x-icon name="x" /></button>
    @endif
</div>
