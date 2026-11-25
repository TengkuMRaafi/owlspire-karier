{{-- Dropdown kustom (desain menu Karier). Pengganti <select> di seluruh situs.
     Pakai: <x-dropdown name="role" :options="['member' => 'Member']" :value="request('role')" all="Semua role" aria="Role" submit />
     all: teks pilihan "kosong" (nilai ''); tanpa all, pilihan pertama jadi bawaan. submit: kirim formulir begitu dipilih. --}}
@props(['name', 'options' => [], 'value' => null, 'all' => null, 'aria' => '', 'id' => null, 'size' => null, 'submit' => false, 'block' => false, 'up' => false])
@php
    $options = (array) $options;
    // Opsi boleh berkelompok: ['Judul kelompok' => [id => teks, ...], ...]
    $grouped = collect($options)->contains(fn ($v) => is_array($v));
    $groups = $grouped ? $options : [];
    if ($grouped) {
        $options = [];
        foreach ($groups as $items) {
            foreach ((array) $items as $k => $t) {
                $options[$k] = $t; // kunci dipertahankan (id angka)
            }
        }
    }
    $value = (string) ($value ?? '');
    if ($all === null && ! array_key_exists($value, $options)) {
        $value = (string) (array_key_first($options) ?? '');
    }
    $label = $options[$value] ?? ($all ?? '');
    $id ??= 'dd-'.preg_replace('/[^a-z0-9]+/i', '-', $name).'-'.substr(md5($name.$aria.json_encode(array_keys($options))), 0, 5);
@endphp
<div {{ $attributes->class(['ui-dd', 'is-sm' => $size === 'sm', 'is-block' => $block, 'is-up' => $up]) }} data-dd @if ($submit) data-dd-submit @endif>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-dd-value>
    <button type="button" id="{{ $id }}" class="ui-dd-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ $aria }}" data-dd-btn>
        <span data-dd-label>{{ $label }}</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <ul class="ui-dd-list" role="listbox" tabindex="-1" aria-label="{{ $aria }}" data-dd-list>
        @if ($all !== null)
            <li role="option" data-no-rapi data-v="" aria-selected="{{ $value === '' ? 'true' : 'false' }}">{{ $all }}</li>
        @endif
        @if ($grouped)
            @foreach ($groups as $title => $items)
                <li role="presentation" class="is-group" data-no-rapi>{{ $title }}</li>
                @foreach ($items as $key => $text)
                    <li role="option" data-no-rapi data-v="{{ $key }}" aria-selected="{{ (string) $key === $value ? 'true' : 'false' }}">{{ $text }}</li>
                @endforeach
            @endforeach
        @else
            @foreach ($options as $key => $text)
                <li role="option" data-no-rapi data-v="{{ $key }}" aria-selected="{{ (string) $key === $value ? 'true' : 'false' }}">{{ $text }}</li>
            @endforeach
        @endif
    </ul>
</div>
