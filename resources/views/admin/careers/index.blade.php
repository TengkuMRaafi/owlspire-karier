@extends('layouts.admin')

@section('title', 'Karier')
@section('heading', 'Karier')
@section('subheading', $careers->total().' karier di katalog. Urutan mengikuti kolom urutan; yang nonaktif disembunyikan dari situs.')
@section('actions')
    <a href="{{ route('admin.careers.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah karier</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari karier atau bidang" :filters="['cari', 'divisi', 'status']">
                <x-dropdown name="divisi" aria="Divisi" all="Semua bidang" :options="$divisions" :value="request('divisi')" submit />
                <x-dropdown name="status" aria="Status" all="Semua status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :value="request('status')" submit />
            </x-table-search>
        </form>

        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Karier</th><th>Divisi</th><th class="is-num">Urutan</th><th class="is-num">Soal</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($careers as $c)
                        <tr>
                            <td class="is-no">{{ $careers->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $c->name }}</b><small>{{ \Illuminate\Support\Str::limit($c->summary, 70) }}</small></span></td>
                            <td>{{ $c->division }}</td>
                            <td class="is-num">{{ $c->rank }}</td>
                            <td class="is-num">{{ $questionCounts[$c->exam_type_id] ?? 0 }}</td>
                            <td><span @class(['ui-badge has-dot', 'is-success' => $c->is_active])>{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="is-aksi">
                                <x-row-actions :show="route('admin.careers.show', $c)" :edit="route('admin.careers.edit', $c)" :delete="route('admin.careers.destroy', $c)"
                                    :confirm="'Hapus karier '.$c->name.'? Simulasi, soal, dan hasil ujiannya ikut terhapus.'" :label="$c->name" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ui-table-empty">Tidak ada karier yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$careers" label="karier" />
    </div>
@endsection
