@extends('layouts.dashboard')

@section('title', 'Belajar')
@section('heading', 'Belajar')
@section('subheading', $premium ? 'Semua topik di semua jalur terbuka untukmu.' : 'Dua topik pertama tiap jalur terbuka. Sisanya untuk Premium.')

@section('content')
    <div class="pn-stack">
        @unless ($premium)
            <x-alert type="info" title="Paket Gratis">
                <p>Topik di luar dua topik pertama tiap jalur terkunci. <a href="{{ route('account.subscription') }}">Lihat paket Premium</a></p>
            </x-alert>
        @endunless

        <div class="pn-card ac-goal">
            @if ($goal)
                <div class="ac-prog">
                    <div><span>Target minggu ini</span><span class="tabular-nums">{{ $goal['done'] }}/{{ $goal['target'] }} topik{{ $goal['done'] >= $goal['target'] ? ' - tercapai' : '' }}</span></div>
                    <div class="ui-meter" role="img" aria-label="Target mingguan {{ $goal['percent'] }} persen"><i style="width: {{ $goal['percent'] }}%"></i></div>
                </div>
                <a href="{{ route('account.settings') }}#learning" class="ui-link">Ubah target</a>
            @else
                <p class="ac-note"><x-icon name="target" /> Belum ada target belajar mingguan.</p>
                <a href="{{ route('account.settings') }}#learning" class="btn-outline btn-sm">Atur target</a>
            @endif
        </div>

        <div class="ui-table-wrap">
            <form method="GET" class="ui-table-bar" role="search">
                <x-table-search name="q" placeholder="Cari jalur belajar" :filters="['q', 'kategori']">
                    <x-dropdown name="kategori" aria="Kategori" all="Semua kategori" :options="$categories" :value="$kategori" submit />
                </x-table-search>
            </form>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Jalur belajar</th><th>Kategori</th><th>Topik terbuka</th><th>Kemajuan</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($courses as $c)
                            @php $pct = $progress[$c->id] ?? 0; $open = $premium ? $c->lessons_count : min($c->free_count, $c->lessons_count); @endphp
                            <tr>
                                <td class="is-no">{{ $courses->firstItem() + $loop->index }}</td>
                                <td><span class="ui-cell"><b>{{ $c->career?->name ?? $c->title }}</b><small>{{ \Illuminate\Support\Str::limit($c->career?->summary ?? $c->summary, 70) }}</small></span></td>
                                <td>{{ $c->category ?: 'Jalur belajar' }}</td>
                                <td class="is-nowrap"><x-icon :name="$premium ? 'check-circle' : 'lock'" class="size-4 inline" /> {{ $open }} dari {{ $c->lessons_count }}</td>
                                <td><div class="ui-cell-meter"><span>{{ $pct >= 100 ? 'Selesai' : $pct.'%' }}</span><div class="ui-meter" role="img" aria-label="Kemajuan {{ $pct }} persen"><i style="width: {{ $pct }}%"></i></div></div></td>
                                <td class="is-aksi"><x-row-actions :show="route('learning.show', $c)" label="jalur belajar" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ui-table-empty">Belum ada jalur yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-foot :paginator="$courses" label="jalur" />
        </div>
    </div>
@endsection
