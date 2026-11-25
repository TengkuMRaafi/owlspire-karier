@extends('layouts.admin')

@section('title', 'Hasil ujian')
@section('heading', 'Hasil ujian')
@section('subheading', $attempts->total().' ujian selesai')

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari peserta atau ujian" :filters="['cari', 'jenis', 'mode']">
                <x-dropdown name="jenis" aria="Jenis ujian" all="Semua ujian" :options="$types->pluck('name', 'id')->all()" :value="request('jenis')" submit />
                <x-dropdown name="mode" aria="Mode" all="Semua mode" :options="['full' => 'Penuh', 'trial' => 'Coba']" :value="request('mode')" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Peserta</th><th>Ujian</th><th>Mode</th><th class="is-num">Skor</th><th>Status</th><th>Selesai</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($attempts as $a)
                        <tr>
                            <td class="is-no">{{ $attempts->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $a->user?->name ?? 'Tamu' }}</b><small>{{ $a->user?->email }}</small></span></td>
                            <td>{{ $a->type->name }}</td>
                            <td><span @class(['ui-badge', 'is-accent' => $a->mode === 'full'])>{{ $a->mode === 'full' ? 'Penuh' : 'Coba' }}</span></td>
                            <td class="is-num"><b>{{ $a->total_score }}</b></td>
                            <td>
                                @if ($a->mode === 'full')
                                    <span @class(['ui-badge has-dot', 'is-success' => $a->passed, 'is-danger' => ! $a->passed])>{{ $a->passed ? 'Lulus' : 'Belum lulus' }}</span>
                                @else
                                    <span class="text-graphite-400">-</span>
                                @endif
                            </td>
                            <td class="is-nowrap text-graphite-500">{{ $a->finished_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="is-aksi"><x-row-actions :show="route('admin.attempts.show', $a)" :delete="route('admin.attempts.destroy', $a)" confirm="Hapus hasil ujian ini? Jawabannya ikut terhapus." label="hasil ujian" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ui-table-empty">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$attempts" label="hasil ujian" />
    </div>
@endsection
