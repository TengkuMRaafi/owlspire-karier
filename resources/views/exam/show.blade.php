@extends('layouts.app')

@section('title', $type->name)
@section('description', $type->description)

@section('content')
    @php
        $segs = $type->segments->map(fn ($s) => [
            'name' => $s->name,
            'desc' => $s->description,
            'count' => (int) min($s->question_count_trial, $s->questions_count),
        ])->values();
        $totalQ = (int) $segs->sum('count');
        $minutes = (int) ($type->trial_duration_seconds / 60);
        $maxSeg = max(1, (int) ($segs->max('count') ?? 1));
        // Gambar header per seleksi (public/images/simulasi/{kode}.svg); kalau belum ada, pakai gambar umum karier.
        $heroImg = is_file(public_path('images/simulasi/'.$type->code.'.svg'))
            ? asset('images/simulasi/'.$type->code.'.svg')
            : asset('images/karier/umum.svg');
        $heroTag = ['cpns' => 'Seleksi ASN', 'bumn' => 'Rekrutmen BUMN', 'polri' => 'Seleksi Polri & TNI'][$type->code] ?? 'Seleksi resmi';
        $rules = [
            'Waktu dihitung server. Menutup halaman tidak menghentikan timer.',
            'Jawaban tersimpan otomatis setiap kali kamu memilih.',
            'Kunci jawaban dan pembahasan baru muncul setelah ujian selesai.',
            'Soal yang kamu jawab salah masuk antrean ulang (khusus akun yang masuk).',
        ];
    @endphp

    @include('careers.partials.kd-icons')

    {{-- Hero: gaya sama dengan halaman detail karier --}}
    <section class="kd-hero on-dark">
        <div class="kd-hero-bg" aria-hidden="true"><img src="{{ $heroImg }}" alt="" decoding="async"></div>
        <div class="kd-wrap kd-hero-in">
            <div class="kd-hero-main">
                <nav class="kd-crumb" aria-label="Jejak halaman">
                    <a href="{{ url('/') }}">Beranda</a><span aria-hidden="true">/</span>
                    <a href="{{ route('exam.index') }}">Simulasi</a><span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $type->name }}</span>
                </nav>
                <div class="kd-pills">
                    <span class="kd-pill kd-pill-solid">Simulasi ujian</span>
                    <span class="kd-pill">Dinilai di server</span>
                </div>
                <h1 class="font-display kd-title">{{ $type->name }}</h1>
                <p class="kd-lead">{{ $type->description }}</p>
                <div class="kd-cta">
                    <a href="#simulasi" class="btn-primary">Mulai simulasi<svg class="kd-ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg></a>
                </div>
            </div>

            <aside class="kd-hero-card" aria-label="Ringkasan ujian">
                <div class="kd-poster"><img src="{{ $heroImg }}" alt="" loading="lazy" decoding="async"><span class="kd-poster-tag">{{ $heroTag }}</span></div>
                <ul class="kd-rank-meta">
                    <li><b>{{ $totalQ }}</b> soal</li>
                    <li><b>{{ $minutes }}</b> menit</li>
                    <li><b>{{ $segs->count() }}</b> bagian</li>
                </ul>
            </aside>
        </div>
    </section>

    <div class="kd-wrap kd-grid">
        <div class="kd-main">
            @if ($running && $running->exam_type_id === $type->id)
                <section class="kd-sec">
                    <div class="kd-trend">
                        <span class="kd-trend-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-clock"/></svg></span>
                        <div style="flex:1">
                            <p class="kd-k">Sedang berjalan</p>
                            <p class="kd-trend-t">Ujian ini masih berjalan.</p>
                        </div>
                        <a href="{{ route('exam.room', $running) }}" class="btn-primary">Lanjutkan</a>
                    </div>
                </section>
            @endif

            {{-- Susunan ujian --}}
            <section id="susunan" class="kd-sec" data-reveal>
                <header class="kd-sh"><p class="kd-k">Isi ujian</p><h2 class="font-display">Susunan ujian</h2></header>
                <ul class="kd-does kd-sg" style="--n: {{ min(3, max(1, $segs->count())) }}">
                    @foreach ($segs as $i => $seg)
                        @php
                            // "Tes Wawasan Kebangsaan (TWK)" -> label + kode; di HP kodenya tampil besar di atas label.
                            $hasCode = preg_match('/^(.*?)\s*\(([^()]+)\)\s*$/u', $seg['name'], $m) === 1;
                            $segLabel = $hasCode ? $m[1] : $seg['name'];
                            $segCode = $hasCode ? $m[2] : null;
                        @endphp
                        <li>
                            <span class="kd-does-no">{{ sprintf('%02d', $i + 1) }}</span>
                            <p class="kd-sg-n">{{ $segLabel }}@if ($segCode) <span class="kd-sg-code">{{ $segCode }}</span>@endif</p>
                            @if ($seg['desc'])<p class="kd-sg-d text-xs font-normal text-graphite-500">{{ $seg['desc'] }}</p>@endif
                            <span class="kd-k kd-sg-c mt-auto">{{ $seg['count'] }} soal</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs text-graphite-500">
                    Durasi: {{ $minutes }} menit.
                    Setiap jawaban benar bernilai {{ config('platform.exam.points_per_correct') }} poin.
                </p>
            </section>

            {{-- Aturan main --}}
            <section id="aturan" class="kd-sec" data-reveal>
                <header class="kd-sh"><p class="kd-k">Sebelum mulai</p><h2 class="font-display">Aturan main</h2></header>
                <ul class="kd-does kd-rules">
                    @foreach ($rules as $i => $rule)
                        <li><span class="kd-does-no">{{ sprintf('%02d', $i + 1) }}</span><p>{{ $rule }}</p><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg></li>
                    @endforeach
                </ul>
            </section>
        </div>

        <aside class="kd-side">
            <div id="simulasi" class="kd-card kd-sim">
                <p class="eyebrow">Simulasi</p>
                <h2 class="font-display">{{ $type->name }}</h2>
                <p class="kd-sim-meta"><span>{{ $totalQ }} soal</span><span>{{ $minutes }} menit</span><span>{{ $segs->count() }} bagian</span></p>
                <ul class="kd-seg">
                    @foreach ($segs as $seg)
                        <li><span>{{ $seg['name'] }}</span><span class="kd-seg-n">{{ $seg['count'] }} soal</span><i style="--w: {{ round($seg['count'] / $maxSeg * 100) }}%"></i></li>
                    @endforeach
                </ul>
                <div class="kd-sim-go">@include('exam.partials.start', ['type' => $type, 'tickets' => $tickets, 'tone' => 'light'])</div>
                <p class="kd-fine">Coba gratis dulu. Skor dan pembahasan lengkap ada di simulasi penuh.</p>
            </div>
        </aside>
    </div>
@endsection
