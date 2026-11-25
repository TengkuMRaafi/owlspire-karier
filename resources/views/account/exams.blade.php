@extends('layouts.dashboard')

@section('title', 'Simulasi')
@section('heading', 'Simulasi')
@section('subheading', $premium ? 'Ujian penuh tanpa batas untuk semua seleksi dan karier.' : 'Mode coba gratis terbuka. Ujian penuh butuh Premium atau tiket.')
@section('actions')
    <a href="{{ route('account.history') }}" class="btn-outline"><x-icon name="history" class="size-4" /> Riwayat ujian</a>
@endsection

@section('content')
    <div class="pn-stack">
        @if ($running)
            <x-alert type="warning" title="Ada ujian yang belum selesai">
                <p>{{ $running->type->name }} &middot; sisa sekitar {{ (int) ceil($running->remainingSeconds() / 60) }} menit. <a href="{{ route('exam.room', $running) }}">Lanjutkan sekarang</a></p>
            </x-alert>
        @endif

        <div class="ui-table-wrap">
            <form method="GET" class="ui-table-bar" role="search">
                <x-table-search name="q" placeholder="Cari simulasi" :filters="['q', 'jenis']">
                    <x-dropdown name="jenis" aria="Jenis simulasi" all="Semua jenis" :options="['selection' => 'Seleksi resmi', 'career' => 'Simulasi karier']" :value="$jenis" submit />
                </x-table-search>
            </form>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Simulasi</th><th>Jenis</th><th class="is-num">Durasi</th><th class="is-num">Bagian</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($types as $type)
                            @php
                                $trialCount = $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count));
                                $fullCount = \App\Services\Exam\FullExamPlan::total($type->segments);
                                $avail = $tickets[$type->id] ?? 0;
                                $isCareer = $type->kind === 'career';
                            @endphp
                            <tr>
                                <td class="is-no">{{ $types->firstItem() + $loop->index }}</td>
                                <td><span class="ui-cell"><b>{{ $type->name }}</b>@if ($avail > 0 && $avail < 100000)<small>Tiket aktif, sisa {{ $avail }}x ujian penuh</small>@elseif ($avail >= 100000)<small>Akses tanpa batas</small>@endif</span></td>
                                <td><span @class(['ui-badge', 'is-accent' => ! $isCareer])>{{ $isCareer ? 'Karier' : 'Seleksi resmi' }}</span></td>
                                <td class="is-num">{{ (int) ($type->duration_seconds / 60) }} mnt</td>
                                <td class="is-num">{{ $type->segments->count() }}</td>
                                <td class="is-aksi">
                                    <span class="ui-table-actions">
                                        <form method="POST" action="{{ route('exam.start', $type) }}">@csrf<input type="hidden" name="mode" value="trial"><button type="submit" class="ui-act is-show" @disabled($trialCount === 0) title="Coba gratis, {{ $trialCount }} soal"><x-icon name="play" /><span>Coba gratis</span></button></form>
                                        @if ($avail > 0)
                                            <form method="POST" action="{{ route('exam.start', $type) }}" @if ($avail < 100000) data-confirm="Tiket ini punya jatah {{ (int) config('platform.exam.pass_attempts', 2) }}x ujian penuh per simulasi. Memulai sekarang memakai 1 kesempatan (sisa {{ $avail }}, jadi {{ $avail - 1 }} setelahnya). Kerjakan dengan sungguh-sungguh dan siapkan waktunya sampai selesai. Kalau jatah habis, simulasi ini terkunci lagi." data-confirm-title="Pakai 1 kesempatan tiket?" data-confirm-ok="Ya, mulai" data-confirm-tone="warning" @endif>@csrf<input type="hidden" name="mode" value="full"><button type="submit" class="ui-act is-ok" @disabled($fullCount === 0) title="{{ $fullCount }} soal"><x-icon name="check" /><span>{{ $isCareer ? 'Lanjutan' : 'Ujian penuh' }}</span></button></form>
                                        @else
                                            <a href="{{ route('billing.index', ['jenis' => $type->code]) }}#tiket" class="ui-act" title="Butuh Premium atau tiket (jatah habis = terkunci lagi)"><x-icon name="lock" /><span>{{ $isCareer ? 'Lanjutan' : 'Ujian penuh' }}</span></a>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ui-table-empty">Belum ada simulasi yang cocok{{ $q !== '' ? ' dengan "'.$q.'"' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-foot :paginator="$types" label="simulasi" />
        </div>
    </div>
@endsection
