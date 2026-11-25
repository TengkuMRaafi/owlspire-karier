@extends('layouts.admin')

@section('title', 'Kamus skill')
@section('heading', 'Kamus skill')
@section('subheading', 'Nama dan alias di sini dicari di teks CV oleh Analisis CV dan dipakai untuk mencocokkan karier.')
@section('actions')
    <a href="{{ route('admin.skills.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah skill</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari skill atau alias" :filters="['cari', 'kategori']">
                <x-dropdown name="kategori" aria="Kategori" all="Semua kategori" :options="$categories" :value="request('kategori')" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Skill</th><th>Kategori</th><th>Alias</th><th class="is-num">Karier</th><th class="is-num">Pengguna</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($skills as $s)
                        <tr>
                            <td class="is-no">{{ $skills->firstItem() + $loop->index }}</td>
                            <td><b>{{ $s->name }}</b></td>
                            <td>@if ($s->category)<span class="ui-badge">{{ $s->category }}</span>@endif</td>
                            <td class="text-graphite-500" style="max-width:20rem">{{ collect($s->aliases)->take(6)->implode(', ') }}{{ count($s->aliases ?? []) > 6 ? ' ...' : '' }}</td>
                            <td class="is-num">{{ $careerCounts[$s->id] ?? 0 }}</td>
                            <td class="is-num">{{ $userCounts[$s->id] ?? 0 }}</td>
                            <td class="is-aksi"><x-row-actions :edit="route('admin.skills.edit', $s)" :delete="route('admin.skills.destroy', $s)" :confirm="'Hapus skill '.$s->name.'? Skill ini juga lepas dari karier dan profil pengguna.'" :label="$s->name" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ui-table-empty">Belum ada skill yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$skills" label="skill" />
    </div>
@endsection
