@extends('layouts.admin')

@section('title', 'Video belajar')
@section('heading', 'Video belajar')
@section('subheading', 'Tinjau video Creative Commons hasil pencarian otomatis, atau tambahkan sendiri dengan lisensi dan kreator yang jelas.')
@section('actions')
    <a href="{{ route('admin.learning.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah video</a>
@endsection

@section('content')
    <div class="pn-stack">
        @unless ($hasKey)
            <x-alert type="warning">Pencarian otomatis belum aktif. Isi <code>YOUTUBE_API_KEY</code> di .env lalu jalankan <code>php artisan belajar:ambil-youtube</code>. Menambah video manual tetap bisa.</x-alert>
        @endunless

        <div class="ui-table-wrap">
            <form method="GET" class="ui-table-bar" role="search">
                <x-table-search name="cari" placeholder="Cari judul atau kreator" :filters="['cari', 'status', 'jalur']">
                    <x-dropdown name="status" aria="Status" all="Semua status" :options="['menunggu' => 'Menunggu persetujuan ('.$pending.')', 'tayang' => 'Tayang']" :value="request('status')" submit />
                    <x-dropdown name="jalur" aria="Jalur belajar" all="Semua jalur" :options="$courses->pluck('title', 'id')->all()" :value="request('jalur')" submit />
                </x-table-search>
            </form>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Video</th><th>Jalur</th><th>Lisensi</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($videos as $l)
                            <tr>
                                <td class="is-no">{{ $videos->firstItem() + $loop->index }}</td>
                                <td><span class="ui-cell"><b>{{ \Illuminate\Support\Str::limit($l->title, 60) }}</b><small>{{ $l->credit_name }}</small></span></td>
                                <td>{{ $l->section->course->title ?? '-' }}</td>
                                <td class="ui-table-desc">{{ \Illuminate\Support\Str::limit($l->license, 30) }}</td>
                                <td><span @class(['ui-badge has-dot', 'is-success' => $l->is_approved, 'is-warning' => ! $l->is_approved])>{{ $l->is_approved ? 'Tayang' : 'Menunggu' }}</span></td>
                                <td class="is-aksi">
                                    <x-row-actions :show="route('admin.learning.show', $l)" :edit="route('admin.learning.edit', $l)" :delete="route('admin.learning.destroy', $l)" confirm="Hapus video ini?" label="video">
                                        @unless ($l->is_approved)
                                            <form method="POST" action="{{ route('admin.learning.approve', $l) }}">@csrf<button class="ui-act is-ok" type="submit" title="Setujui"><x-icon name="check" /><span>Setujui</span></button></form>
                                        @endunless
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ui-table-empty">Belum ada video.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-foot :paginator="$videos" label="video" />
        </div>
    </div>
@endsection
