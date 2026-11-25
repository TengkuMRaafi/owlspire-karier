@extends('layouts.admin')

@section('title', 'Detail hasil ujian')
@section('heading', 'Detail hasil ujian')
@section('subheading', $attempt->type->name)
@section('actions')
    <a href="{{ route('admin.attempts.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    @php
        $dur = $attempt->durationSeconds();
        $wrong = max(0, $answered - $correct);
        $blank = max(0, $total - $answered);
        $pct = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $part = fn ($n) => $total > 0 ? round($n / $total * 100, 2) : 0;
        $name = $attempt->user?->name ?? 'Tamu';
    @endphp
    <div class="pn-stack">
        <x-detail-hero :title="$name" :sub="$attempt->user?->email ?? 'Peserta tanpa akun'" :initial="\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1))">
            <span class="ad-d-chip"><x-icon name="play" /> {{ $attempt->type->name }}</span>
            <span @class(['ui-badge', 'is-accent' => $attempt->mode === 'full'])>{{ $attempt->mode === 'full' ? 'Ujian penuh' : 'Mode coba' }}</span>
            @if ($attempt->mode === 'full')
                <span @class(['ui-badge has-dot', 'is-success' => $attempt->passed, 'is-danger' => ! $attempt->passed])>{{ $attempt->passed ? 'Lulus' : 'Belum lulus' }}</span>
            @endif
            <x-slot:side>
                <div class="ad-d-ring" style="--v: {{ $pct }}" role="img" aria-label="Ketepatan {{ $pct }} persen"><b>{{ $pct }}<small>%</small></b></div>
                <small>Ketepatan jawaban</small>
            </x-slot:side>
        </x-detail-hero>

        <div class="ui-stats is-compact">
            <x-detail-stat icon="trophy" label="Skor total" :value="$attempt->total_score" :accent="true" />
            <x-detail-stat icon="check-circle" label="Benar" :value="$correct" :hint="'dari '.$total.' soal'" />
            <x-detail-stat icon="list" label="Dijawab" :value="$answered" :hint="$blank.' belum dijawab'" />
            <x-detail-stat icon="clock" label="Lama mengerjakan" :value="$dur ? intdiv($dur, 60) : '-'" :hint="$dur ? 'menit '.($dur % 60).' detik' : null" />
        </div>

        <div class="pn-split is-even">
            <div class="pn-card">
                <x-detail-head icon="bars" title="Komposisi jawaban" />
                <div class="ad-d-bar" role="img" aria-label="{{ $correct }} benar, {{ $wrong }} salah, {{ $blank }} kosong">
                    <i class="is-ok" style="width: {{ $part($correct) }}%"></i>
                    <i class="is-no" style="width: {{ $part($wrong) }}%"></i>
                    <i class="is-na" style="width: {{ $part($blank) }}%"></i>
                </div>
                <div class="ad-d-legend">
                    <span><i class="is-ok"></i> Benar <b>{{ $correct }}</b></span>
                    <span><i class="is-no"></i> Salah <b>{{ $wrong }}</b></span>
                    <span><i></i> Kosong <b>{{ $blank }}</b></span>
                </div>
            </div>

            <div class="pn-card">
                <x-detail-head icon="calendar" title="Waktu pengerjaan" />
                <div class="ad-d-info">
                    <x-detail-item icon="clock" label="Mulai">{{ $attempt->started_at->translatedFormat('d M Y H:i') }}</x-detail-item>
                    <x-detail-item icon="check-double" label="Selesai">{{ $attempt->finished_at->translatedFormat('d M Y H:i') }}</x-detail-item>
                    @if ($dur)<x-detail-item icon="history" label="Lama" :wide="true">{{ intdiv($dur, 60) }} menit {{ $dur % 60 }} detik</x-detail-item>@endif
                </div>
            </div>
        </div>
    </div>
@endsection
