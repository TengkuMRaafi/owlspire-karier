{{-- Kaki tabel: keterangan jumlah + pagination. Pakai: <x-table-foot :paginator="$users" label="pengguna" /> --}}
@props(['paginator', 'label' => 'data'])
<div class="ui-table-foot">
    <p class="ui-pager-info">
        @if ($paginator->total() > 0)
            Menampilkan <b>{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</b> dari <b>{{ number_format($paginator->total()) }}</b> {{ $label }}
        @else
            Belum ada {{ $label }}
        @endif
    </p>
    {{ $paginator->links() }}
</div>
