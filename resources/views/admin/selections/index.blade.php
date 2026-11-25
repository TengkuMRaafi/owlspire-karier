@extends('layouts.admin')

@section('title', 'Info seleksi')
@section('heading', 'Info seleksi')
@section('subheading', 'Jadwal pendaftaran dan tahapan seleksi yang tampil di halaman Info seleksi dan landing. Isi dari pengumuman resmi.')
@section('actions')
    <a href="{{ route('admin.selections.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah jadwal</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari judul atau tahap" :filters="['cari', 'kategori', 'status']">
                <x-dropdown name="kategori" aria="Kategori" all="Semua kategori" :options="\App\Models\SelectionEvent::CATEGORIES" :value="request('kategori')" submit />
                <x-dropdown name="status" aria="Status" all="Semua status" :options="['terbit' => 'Terbit', 'draf' => 'Draf']" :value="request('status')" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Jadwal</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($events as $e)
                        @php $w = $e->window(); @endphp
                        <tr>
                            <td class="is-no">{{ $events->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $e->title }}</b>@if ($e->stage)<small>{{ $e->stage }}</small>@endif</span></td>
                            <td><span class="ui-badge is-accent">{{ $e->categoryLabel() }}</span></td>
                            <td class="is-nowrap"><span class="ui-cell"><b>{{ $e->rangeLabel() }}</b><small>{{ $w['label'] }}</small></span></td>
                            <td><span @class(['ui-badge has-dot', 'is-success' => $e->is_published])>{{ $e->is_published ? 'Terbit' : 'Draf' }}</span></td>
                            <td class="is-aksi"><x-row-actions :edit="route('admin.selections.edit', $e)" :delete="route('admin.selections.destroy', $e)" confirm="Hapus jadwal ini? Tindakan ini tidak bisa dibatalkan." label="jadwal" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ui-table-empty">Belum ada jadwal. Tambahkan dari pengumuman resmi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$events" label="jadwal" />
    </div>
@endsection
