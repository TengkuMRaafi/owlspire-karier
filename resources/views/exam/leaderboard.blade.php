@extends('layouts.app')

@section('title', 'Papan Peringkat')
@section('description', 'Skor tertinggi dari ujian penuh. Skor sama diurutkan dari pengerjaan tercepat. Nama disamarkan.')

@section('content')
    {{-- Ikon dipakai ulang lewat <use> --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <defs>
            <symbol id="lb-i-crown" viewBox="0 0 24 24"><path d="M2.2 7.6 7 11.4 12 3.5l5 7.9 4.8-3.8-2 11.4H4.2z"/><rect x="4.4" y="20.2" width="15.2" height="1.8" rx=".9"/></symbol>
            <symbol id="lb-i-ribbon" viewBox="0 0 24 24"><path d="M4.5 1h6l3.2 12H7.6zM19.5 1h-6l-3.2 12h6.1z"/></symbol>
        </defs>
    </svg>

    {{-- Pilih jenis ujian lewat dropdown; ditulis inline supaya langsung jalan tanpa build ulang --}}
    <style>
        body .lb-pick { margin: 0 0 1.1rem; }
        @media (min-width: 640px) { body .lb-pick { max-width: 22rem; } }
    </style>

    @php
        $mask = fn ($r) => \App\Services\Exam\ExamStats::maskName($r?->user?->name);
    @endphp

    {{-- ============ Header: podium juara ============ --}}
    <section class="lb-hero on-dark">
        <div class="lb-hero-grid mx-auto max-w-6xl px-4">
            <div>
                <p class="lb-eyebrow">Simulasi ujian</p>
                <h1 class="font-display lb-title">Papan <em>peringkat.</em></h1>
                <p class="lb-lead">Skor tertinggi dari ujian penuh. Skor sama diurutkan dari pengerjaan tercepat. Nama disamarkan.</p>


                <div class="lb-cta">
                    <a href="{{ route('exam.index') }}" class="btn-primary">Ikut ujian</a>
                    <a href="#peringkat" class="btn-outline-light">Lihat daftar</a>
                </div>
            </div>

            {{-- Podium (hiasan; daftar lengkap ada di bawah) --}}
            <div class="lb-podium" aria-hidden="true">
                @foreach ([[1, 'lb-p2'], [0, 'lb-p1'], [2, 'lb-p3']] as [$idx, $cls])
                    @php $r = $rows->get($idx); @endphp
                    <div class="lb-pod {{ $cls }}">
                        <div class="lb-who">
                            @if ($idx === 0)<svg class="lb-crown-lg"><use href="#lb-i-crown"/></svg>@endif
                            <span class="lb-av">{{ $r ? mb_strtoupper(mb_substr($mask($r), 0, 1)) : '?' }}</span>
                            <b>{{ $r ? $mask($r) : 'Kosong' }}</b>
                            <small>{{ $r ? $r->total_score.' poin' : '-' }}</small>
                        </div>
                        <div class="lb-bar"><span>{{ $idx + 1 }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Daftar ============ --}}
    <section id="peringkat" class="lb-wrap mx-auto max-w-4xl px-4">
        <form method="GET" action="{{ route('exam.leaderboard') }}#peringkat" class="lb-pick">
            <x-dropdown name="jenis" :options="$types->pluck('name', 'code')->all()" :value="$active?->code" aria="Jenis ujian" submit block />
        </form>

        <div class="lb-list" role="table" aria-label="Peringkat {{ $active?->name }}">
            <div class="lb-row lb-head" role="row">
                <span role="columnheader">#</span>
                <span role="columnheader">Peserta</span>
                <span role="columnheader" class="lb-num">Skor</span>
                <span role="columnheader" class="lb-num">Waktu</span>
            </div>

            @forelse ($rows as $i => $row)
                @php $n = $i + 1; $secs = $row->durationSeconds() ?? 0; $me = auth()->id() === $row->user_id; @endphp
                <div @class(['lb-row', 'lb-t'.$n => $n <= 3, 'is-me' => $me]) role="row" style="--i: {{ $i }}">
                    <span class="lb-rank {{ $n <= 3 ? 'lb-r'.$n : '' }}" role="cell">
                        @if ($n === 1)<svg class="lb-crown" aria-hidden="true"><use href="#lb-i-crown"/></svg>
                        @elseif ($n <= 3)<svg class="lb-rib" aria-hidden="true"><use href="#lb-i-ribbon"/></svg>@endif
                        <i>{{ $n }}</i>
                        @if ($n <= 3)<span class="sr-only">Juara {{ $n }}</span>@endif
                    </span>
                    <span class="lb-who2" role="cell">
                        <b>{{ $mask($row) }}@if ($me)<em class="lb-me">Kamu</em>@endif</b>
                        <small>
                            {{ $row->finished_at?->translatedFormat('d M Y') }}
                            @if ($row->passed)<span class="lb-pass">Lulus</span>@endif
                        </small>
                    </span>
                    <span class="lb-num lb-score" role="cell">{{ $row->total_score }}</span>
                    <span class="lb-num lb-time" role="cell">{{ intdiv($secs, 60) }}m {{ $secs % 60 }}d</span>
                </div>
            @empty
                <div class="lb-empty">Belum ada hasil ujian penuh untuk jenis ini. Jadilah yang pertama.</div>
            @endforelse
        </div>
    </section>

@endsection
