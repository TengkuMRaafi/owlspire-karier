@extends('layouts.dashboard')

@section('title', 'Karier saya')
@section('heading', 'Karier saya')
@section('subheading', 'Pilih karier target, pantau skor kesiapan, dan temukan arah yang cocok.')
@section('actions')
    <a href="{{ route('careers.quiz') }}" class="btn-outline"><x-icon name="compass" class="size-4" /> Tes arah karier</a>
@endsection

@section('content')
    <div class="pn-stack">
        @if ($target && $readiness)
            <div class="pn-card">
                <div class="pn-card-h"><div><p class="eyebrow">Karier target</p><h2 class="font-display text-2xl font-semibold text-ink">{{ $target->name }}</h2></div>
                    <a href="{{ route('careers.show', $target) }}" class="ui-link">Detail karier</a></div>
                <div class="ac-ready">
                    <p class="ac-score">{{ $readiness['total'] }}<small>/100</small></p>
                    <div class="ac-parts">
                        @foreach ($readiness['parts'] as $part)
                            <div class="ac-part">
                                <div class="ac-part-h"><span>{{ $part['label'] }} <small>({{ $part['weight'] }}%)</small></span><span class="tabular-nums">{{ $part['value'] }}%</span></div>
                                <div class="ui-meter" role="img" aria-label="{{ $part['label'] }} {{ $part['value'] }} persen"><i style="width: {{ $part['value'] }}%"></i></div>
                                <p>{{ $part['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="pn-card is-dashed"><p class="font-extrabold text-graphite-800">Belum ada karier target.</p><p class="ad-hint">Pilih satu di bawah untuk melihat skor kesiapanmu.</p></div>
        @endif

        <div class="ui-table-wrap">
            <form method="GET" class="ui-table-bar" role="search">
                <x-table-search name="q" placeholder="Cari karier atau kata kunci" :filters="['q', 'divisi']">
                    <x-dropdown name="divisi" aria="Divisi" all="Semua bidang" :options="$divisions" :value="$divisi" submit />
                </x-table-search>
            </form>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Karier</th><th>Bidang</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($careers as $c)
                            @php $isTarget = $target && $target->id === $c->id; @endphp
                            <tr>
                                <td class="is-no">{{ $careers->firstItem() + $loop->index }}</td>
                                <td><span class="ui-cell"><b>{{ $c->name }}</b><small>{{ \Illuminate\Support\Str::limit($c->summary, 90) }}</small></span></td>
                                <td>{{ $c->division }}</td>
                                <td>@if ($isTarget)<span class="ui-badge is-accent">Target</span>@else<span class="text-graphite-400">-</span>@endif</td>
                                <td class="is-aksi">
                                    <x-row-actions :show="route('careers.show', $c)" :label="$c->name">
                                        @unless ($isTarget)
                                            <form method="POST" action="{{ route('careers.target', $c) }}">@csrf<button type="submit" class="ui-act is-ok" title="Jadikan target"><x-icon name="target" /><span>Jadikan target</span></button></form>
                                        @endunless
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ui-table-empty">Belum ada karier yang cocok{{ $q !== '' ? ' dengan "'.$q.'"' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-foot :paginator="$careers" label="karier" />
        </div>
    </div>
@endsection
