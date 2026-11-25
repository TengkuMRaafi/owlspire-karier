{{-- Tombol aksi di kolom terakhir tabel: Detail, Ubah, Hapus (+ aksi tambahan lewat slot).
     Pakai: <x-row-actions :show="route(...)" :edit="route(...)" :delete="route(...)" confirm="Hapus soal ini?" label="soal" /> --}}
@props(['show' => null, 'edit' => null, 'delete' => null, 'confirm' => 'Hapus data ini? Tindakan ini tidak bisa dibatalkan.', 'label' => 'data'])
<span class="ui-table-actions">
    {{ $slot }}
    @if ($show)<a href="{{ $show }}" class="ui-act is-show" title="Detail" aria-label="Detail {{ $label }}"><x-icon name="eye" /><span>Detail</span></a>@endif
    @if ($edit)<a href="{{ $edit }}" class="ui-act is-edit" title="Ubah" aria-label="Ubah {{ $label }}"><x-icon name="edit" /><span>Ubah</span></a>@endif
    @if ($delete)
        <form method="POST" action="{{ $delete }}" data-confirm="{{ $confirm }}">
            @csrf @method('DELETE')
            <button type="submit" class="ui-act is-danger" title="Hapus" aria-label="Hapus {{ $label }}"><x-icon name="trash" /><span>Hapus</span></button>
        </form>
    @endif
</span>
