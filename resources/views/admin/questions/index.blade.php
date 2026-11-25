@extends('layouts.admin')

@section('title', 'Bank soal')
@section('heading', 'Bank soal')
@section('subheading', $questions->total().' soal ditemukan')
@section('actions')
    <a href="{{ route('admin.questions.import') }}" class="btn-outline"><x-icon name="upload" class="size-4" /> Impor</a>
    <a href="{{ route('admin.questions.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah soal</a>
@endsection

@section('content')
    @php
        $segments = [];
        foreach ($types as $t) { foreach ($t->segments as $s) { $segments[$s->id] = $t->name.' - '.$s->name; } }
    @endphp

    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari teks soal" :filters="['cari', 'jenis', 'segmen', 'trial']">
                <x-dropdown name="jenis" aria="Jenis ujian" all="Semua ujian" :options="$types->pluck('name', 'code')->all()" :value="request('jenis')" submit />
                <x-dropdown name="segmen" aria="Segmen" all="Semua segmen" :options="$segments" :value="request('segmen')" submit />
                <x-dropdown name="trial" aria="Akses" all="Semua akses" :options="['1' => 'Coba gratis', '0' => 'Premium']" :value="request('trial')" submit />
            </x-table-search>
        </form>

        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Soal</th><th>Segmen</th><th>Kunci</th><th>Akses</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($questions as $q)
                        <tr>
                            <td class="is-no">{{ $questions->firstItem() + $loop->index }}</td>
                            <td class="ui-table-desc is-title is-oneline" title="{{ $q->body }}">{{ \Illuminate\Support\Str::limit($q->body, 110) }}</td>
                            <td class="is-nowrap"><span class="ui-cell"><small>{{ $q->segment?->type?->name }}</small><b>{{ $q->segment?->name }}</b></span></td>
                            <td><b>{{ $q->answer }}</b></td>
                            <td><span @class(['ui-badge', 'is-accent' => $q->is_trial, 'is-dark' => ! $q->is_trial])>{{ $q->is_trial ? 'Coba gratis' : 'Premium' }}</span></td>
                            <td class="is-aksi"><x-row-actions :show="route('admin.questions.show', $q)" :edit="route('admin.questions.edit', $q)" :delete="route('admin.questions.destroy', $q)" confirm="Hapus soal ini?" label="soal" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ui-table-empty">Tidak ada soal yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$questions" label="soal" />
    </div>
@endsection
