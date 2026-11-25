@extends('layouts.app')

@section('title', 'Simulasi & Karier')
@section('description', 'Jelajahi karier, coba simulasinya, dan latihan CPNS, BUMN, serta Polri/TNI dengan timer dan penilaian di server.')

@section('content')
    {{-- ============ Hero: ruang ujian ============ --}}
    <section class="sx-hero on-dark">
        <div class="sx-hero-grid mx-auto max-w-6xl px-4">
            <div class="sx-hero-copy">
                <p class="sx-eyebrow">Simulasi &amp; karier</p>
                <h1 class="font-display sx-title">Pilih ujian,<br>kerjakan seperti <em>sebenarnya.</em></h1>
                <p class="sx-lead">Kenali kariernya, lalu coba simulasinya. Ada seleksi resmi (CPNS, BUMN, Polri/TNI) dan simulasi tiap karier. Timer dan penilaian dihitung di server, jadi hasilnya jujur.</p>

            </div>

            {{-- Lembar jawab animasi: hiasan saja, disembunyikan dari pembaca layar --}}
            <div class="sx-sheet-wrap" aria-hidden="true">
                <div class="sx-sheet">
                    <div class="sx-sheet-top">
                        <div>
                            <small>Lembar jawab</small>
                            <strong>Sesi simulasi</strong>
                        </div>
                        <div class="sx-ring">
                            <svg viewBox="0 0 52 52"><circle class="sx-ring-bg" cx="26" cy="26" r="22"/><circle class="sx-ring-fg" cx="26" cy="26" r="22" pathLength="100"/></svg>
                            <span data-sx-clock>90:00</span>
                        </div>
                    </div>
                    <ol class="sx-rows">
                        @foreach ([1, 3, 0, 2, 1] as $r => $pick)
                            <li style="--r: {{ $r }}">
                                <span class="sx-no">{{ $r + 1 }}</span>
                                @foreach (['A', 'B', 'C', 'D'] as $k => $letter)
                                    <span @class(['sx-bub', 'is-pick' => $k === $pick])>{{ $letter }}</span>
                                @endforeach
                            </li>
                        @endforeach
                    </ol>
                    <div class="sx-sheet-foot">
                        <span class="sx-bar"><i></i></span>
                        <small>Jawaban tersimpan otomatis</small>
                    </div>
                </div>
                <span class="sx-float sx-f1">Skor<b>86</b></span>
                <span class="sx-float sx-f2">Lulus<b>&#10003;</b></span>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">
        @if ($running)
            <div class="sx-running" role="status">
                
                <div class="sx-running-t">
                    <p>Kamu punya ujian yang belum selesai</p>
                    <small>{{ $running->type->name }} &middot; sisa waktu sekitar {{ (int) ceil($running->remainingSeconds() / 60) }} menit</small>
                </div>
                <a href="{{ route('exam.room', $running) }}" class="btn-primary">Lanjutkan ujian</a>
            </div>
        @endif

        @if ($types->isEmpty() && $careerTotal === 0)
            <div class="sx-empty">
                Belum ada jenis ujian aktif. Admin dapat membuatnya di menu <strong>Jenis ujian</strong>, atau jalankan <code>php artisan db:seed</code>.
            </div>
        @endif

        {{-- ============ Simulasi karier ============ --}}
        @if ($careerTotal > 0)
            <div class="sx-head" id="karier">
                <p class="eyebrow">Simulasi karier</p>
                <h2 class="font-display">{{ $careerTotal }} karier, masing-masing punya simulasi.</h2>
            </div>

            <form method="GET" action="{{ route('exam.index') }}#karier" role="search" class="ui-bar" data-live-form>
                <div class="ui-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <label for="sx-q" class="sr-only">Cari simulasi karier</label>
                    <input id="sx-q" type="search" name="q" value="{{ $q }}" placeholder="Cari karier atau skill (mis. data, akuntansi, python)" autocomplete="off" enterkeyhint="search">
                </div>
                @if (count($themeOptions) > 1)
                    <x-dropdown name="bidang" id="sx-bid" aria="Bidang" all="Semua bidang" :options="$themeOptions" :value="$bidang" />
                @endif
                <button type="submit" class="ui-go">Cari</button>
            </form>

            <div class="ui-results" data-live-results data-smooth>
                @include('exam.partials.career-results')
            </div>
        @endif
    </section>

    <script>
        (function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Jam hitung mundur di lembar jawab (hiasan)
            var clock = document.querySelector('[data-sx-clock]');
            if (clock && !reduce) {
                var t = 90 * 60;
                setInterval(function () {
                    t = t > 0 ? t - 1 : 90 * 60;
                    clock.textContent = String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
                }, 1000);
            }
        })();
    </script>
    @include('partials.live-script')
@endsection
