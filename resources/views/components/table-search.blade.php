{{-- Kolom cari di bar tabel + tombol Cari + Reset (muncul bila ada filter aktif).
     Pakai di dalam <form method="GET" class="ui-table-bar">: <x-table-search name="cari" placeholder="Cari nama" :filters="['cari','role']" /> --}}
@props(['name' => 'cari', 'placeholder' => 'Cari...', 'filters' => null])
@php
    $keys = $filters ?? [$name];
    $active = collect($keys)->contains(fn ($k) => filled(request($k)));
@endphp
<div class="ui-search">
    <x-icon name="search" />
    <label for="s-{{ $name }}" class="sr-only">{{ $placeholder }}</label>
    <input id="s-{{ $name }}" type="search" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}" autocomplete="off">
</div>
{{ $slot }}
<button class="ui-go" type="submit">Cari</button>
@if ($active)<a href="{{ url()->current() }}" class="ui-reset">Reset</a>@endif
