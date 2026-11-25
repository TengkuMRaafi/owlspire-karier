@extends('layouts.dashboard')

@section('title', 'Riwayat')
@section('heading', 'Riwayat ujian')
@section('subheading', 'Semua ujian yang sudah kamu selesaikan.')
@section('actions')
    <a href="{{ route('account.exams') }}" class="btn-primary"><x-icon name="play" class="size-4" /> Mulai simulasi</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="q" placeholder="Cari nama ujian" :filters="['q', 'mode']">
                <x-dropdown name="mode" aria="Mode ujian" all="Semua mode" :options="['full' => 'Ujian penuh', 'trial' => 'Mode coba']" :value="$mode" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Ujian</th><th>Mode</th><th class="is-num">Skor</th><th>Status</th><th>Selesai</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($history as $a)
                        <tr>
                            <td class="is-no">{{ $history->firstItem() + $loop->index }}</td>
                            <td><b class="text-graphite-800">{{ $a->type->name }}</b></td>
                            <td><span @class(['ui-badge', 'is-accent' => $a->mode === 'full'])>{{ $a->mode === 'full' ? 'Penuh' : 'Coba' }}</span></td>
                            <td class="is-num"><b>{{ $a->total_score }}</b></td>
                            <td>@if ($a->mode === 'full')<span @class(['ui-badge has-dot', 'is-success' => $a->passed, 'is-danger' => ! $a->passed])>{{ $a->passed ? 'Lulus' : 'Belum lulus' }}</span>@else<span class="text-graphite-400">-</span>@endif</td>
                            <td class="is-nowrap text-graphite-500">{{ $a->finished_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="is-aksi"><x-row-actions :show="route('exam.result', $a)" :label="$a->type->name">
                                @php $left = $a->mode === 'full' ? ($fullLeft[$a->exam_type_id] ?? 0) : null; @endphp
                                @if ($left !== null && $left < 1)
                                    <a href="{{ route('billing.index', ['jenis' => $a->type->code]) }}#tiket" class="ui-act" title="Jatah tiket habis, beli tiket untuk mengulang"><x-icon name="lock" /><span>Terkunci</span></a>
                                @else
                                    <form method="POST" action="{{ route('exam.retake', $a) }}" @if ($left !== null && $left < 100000) data-confirm="Mengulang ujian penuh memakai 1 kesempatan tiket (sisa {{ $left }}, jadi {{ $left - 1 }} setelahnya). Kerjakan dengan sungguh-sungguh. Kalau jatah habis, simulasi ini terkunci lagi." data-confirm-title="Pakai 1 kesempatan tiket?" data-confirm-ok="Ya, ulangi" data-confirm-tone="warning" @endif>
                                        @csrf
                                        <button type="submit" class="ui-act is-edit" title="Kerjakan lagi dengan soal yang sama" aria-label="Ulangi simulasi {{ $a->type->name }}"><x-icon name="refresh" /><span>Ulangi</span></button>
                                    </form>
                                @endif
                            </x-row-actions></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ui-table-empty">Belum ada ujian. Mulai dengan mode coba gratis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$history" label="ujian" />
    </div>
@endsection
