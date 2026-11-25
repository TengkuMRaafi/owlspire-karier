@extends('layouts.admin')

@section('title', 'Jenis ujian')
@section('heading', 'Jenis ujian')
@section('subheading', 'Atur durasi, segmen, jumlah soal, dan passing grade tiap ujian.')
@section('actions')
    <a href="{{ route('admin.setup.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah jenis ujian</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari nama atau kode" :filters="['cari', 'kategori', 'status']">
                <x-dropdown name="kategori" aria="Kategori" all="Semua kategori" :options="['selection' => 'Seleksi resmi', 'career' => 'Simulasi karier']" :value="request('kategori')" submit />
                <x-dropdown name="status" aria="Status" all="Semua status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :value="request('status')" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Jenis ujian</th><th>Kategori</th><th class="is-num">Durasi</th><th class="is-num">Segmen</th><th class="is-num">Soal</th><th class="is-num">Percobaan</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($types as $t)
                        <tr>
                            <td class="is-no">{{ $types->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $t->name }}</b><small>{{ $t->code }}</small></span></td>
                            <td>{{ $t->kind === 'career' ? 'Karier' : 'Seleksi' }}</td>
                            <td class="is-num">{{ (int) ($t->duration_seconds / 60) }} mnt</td>
                            <td class="is-num">{{ $t->segments_count }}</td>
                            <td class="is-num">{{ $questionCounts[$t->id] ?? 0 }}</td>
                            <td class="is-num">{{ $t->attempts_count }}</td>
                            <td><span @class(['ui-badge has-dot', 'is-success' => $t->is_active])>{{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="is-aksi"><x-row-actions :show="route('admin.setup.show', $t)" :edit="route('admin.setup.edit', $t)" :delete="route('admin.setup.destroy', $t)" :confirm="'Hapus jenis ujian '.$t->name.' beserta segmen dan soalnya?'" :label="$t->name" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="ui-table-empty">Tidak ada jenis ujian yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$types" label="jenis ujian" />
    </div>
@endsection
