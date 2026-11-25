@extends('layouts.app')

@section('title', 'Hasil '.$attempt->type->name)

@section('content')
    @php
        $secs = $attempt->durationSeconds() ?? 0;
        $benar = array_sum(array_column($segments, 'benar'));
        $salah = array_sum(array_column($segments, 'salah'));
        $kosong = array_sum(array_column($segments, 'kosong'));
        $isFull = $attempt->mode === 'full';
        $pct = $maxTotal > 0 ? round($attempt->total_score / $maxTotal * 100) : 0;
    @endphp

    @php $tot = max(1, $benar + $salah + $kosong); $pctB = $benar / $tot * 100; $pctS = $salah / $tot * 100; $pctK = $kosong / $tot * 100; @endphp
    <style>
        .rh { position: relative; overflow: hidden; background: #0f1828; }
        .rh::before { content: ""; position: absolute; inset: 0; background: radial-gradient(44rem 26rem at 88% 10%, rgb(79 130 230 / .55), transparent 62%), radial-gradient(30rem 20rem at 8% 120%, rgb(47 191 122 / .22), transparent 70%), radial-gradient(26rem 14rem at 50% -30%, rgb(124 92 255 / .22), transparent 70%); }
        .rh::after { content: ""; position: absolute; inset: 0; background-image: linear-gradient(rgb(255 255 255 / .045) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255 / .045) 1px, transparent 1px); background-size: 32px 32px; mask-image: radial-gradient(60% 120% at 80% 30%, #000, transparent 75%); pointer-events: none; }
        .rh-in { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 1.4rem 3rem; max-width: 72rem; margin: 0 auto; padding: 2rem 1rem 2.1rem; }
        .rh-crumb { display: inline-flex; align-items: center; gap: .5rem; padding: .3rem .8rem .3rem .6rem; border: 1px solid rgb(255 255 255 / .16); border-radius: 99px; background: rgb(255 255 255 / .07); font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #b9c9e6; }
        .rh-crumb i { width: .45rem; height: .45rem; border-radius: 50%; background: #3ecf8e; box-shadow: 0 0 0 3px rgb(62 207 142 / .22); }
        .rh-title { margin-top: 0; font-family: var(--font-display, inherit); font-size: clamp(1.9rem, 4.4vw, 3rem); font-weight: 700; line-height: 1.05; letter-spacing: -.02em; background: linear-gradient(180deg, #fff 30%, #bcd0f5); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .rh-sub { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem 1.1rem; margin-top: .6rem; font-size: .86rem; color: #9fb2d3; font-variant-numeric: tabular-nums; }
        .rh-sub span { display: inline-flex; align-items: center; gap: .4rem; }
        .rh-sub svg { width: .95rem; height: .95rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .rh-split { display: flex; height: .55rem; max-width: 30rem; margin-top: 1.3rem; border-radius: 99px; overflow: hidden; background: rgb(255 255 255 / .12); }
        .rh-split i { display: block; height: 100%; transform-origin: left; animation: rh-grow .9s cubic-bezier(.2,.8,.2,1) both; }
        .rh-split .b { background: linear-gradient(90deg, #2fbf7a, #5ee0a0); } .rh-split .s { background: #ef6a5b; } .rh-split .k { background: #8b99b3; }
        .rh-leg { display: flex; flex-wrap: wrap; gap: .3rem 1.3rem; margin-top: .55rem; font-size: .84rem; color: #b9c9e6; }
        .rh-leg span { display: inline-flex; align-items: center; gap: .45rem; } .rh-leg b { font-size: 1rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
        .rh-leg i { width: .55rem; height: .55rem; border-radius: 3px; }
        .rh-act { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: 1.3rem; }
        .rh-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1.05rem; border-radius: 99px; font-size: .86rem; font-weight: 800; color: #fff; box-shadow: 0 10px 22px -12px currentColor; }
        .rh-badge svg { width: .95rem; height: .95rem; fill: none; stroke: currentColor; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
        .rh-ok { background: #1f9d68; } .rh-no { background: #d9480f; }
        .rh-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1.1rem; border: 1.5px solid rgb(255 255 255 / .28); border-radius: .9rem; background: rgb(255 255 255 / .06); font-size: .86rem; font-weight: 700; color: #fff; text-decoration: none; transition: background .15s, border-color .15s, transform .15s; }
        button.rh-btn { font-family: inherit; cursor: pointer; }
        .rh-retake { display: contents; }
        .rh-btn:hover { background: rgb(255 255 255 / .14); border-color: rgb(255 255 255 / .5); transform: translateY(-1px); }
        .rh-btn svg { width: .9rem; height: .9rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
        .rh-score { position: relative; display: grid; justify-items: center; gap: .15rem; padding: 1.2rem 1.9rem 1.1rem; border: 1px solid rgb(255 255 255 / .18); border-radius: 1.7rem; background: linear-gradient(160deg, rgb(255 255 255 / .14), rgb(255 255 255 / .04)); box-shadow: 0 30px 60px -30px rgb(0 0 0 / .7), inset 0 1px 0 rgb(255 255 255 / .18); backdrop-filter: blur(8px); }
        .rh-score > small { font-size: .66rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #9fb2d3; }
        .rh-ring { position: relative; width: 8.4rem; height: 8.4rem; margin-top: .35rem; filter: drop-shadow(0 0 14px rgb(110 160 255 / .45)); }
        .rh-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); } .rh-ring circle { fill: none; stroke-width: 2.8; }
        .rh-ring .bg { stroke: rgb(255 255 255 / .13); } .rh-ring .fg { stroke: url(#rh-g); stroke-linecap: round; animation: rh-dash 1.1s ease both; }
        .rh-ring div { position: absolute; inset: 0; display: grid; align-content: center; justify-items: center; }
        .rh-num { font-family: var(--font-display, inherit); font-size: 2.7rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
        .rh-ring small { margin-top: .15rem; font-size: .76rem; color: #b9c9e6; }
        .rh-of { margin-top: .35rem; font-size: .84rem; font-weight: 700; color: #dbe5f6; }
        @keyframes rh-dash { from { stroke-dasharray: 0 100; } } @keyframes rh-grow { from { transform: scaleX(0); } }
        @media (max-width: 760px) { .rh-in { grid-template-columns: 1fr; padding: 1.4rem 1rem 1.5rem; } .rh-score { grid-template-columns: auto 1fr; justify-items: start; align-items: center; column-gap: 1.2rem; padding: 1rem 1.2rem; } .rh-score > small { grid-column: 1 / -1; } .rh-ring { width: 6.4rem; height: 6.4rem; margin-top: 0; } .rh-num { font-size: 2rem; } .rh-of { margin-top: 0; }
        }
        /* HP: tombol aksi rapi 2 kolom (lencana penuh, sisanya 2x2), legenda benar/salah/kosong 3 kolom sama lebar */
        @media (max-width: 760px) {
            .rh .rh-act { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; gap: .6rem; width: 100%; }
            .rh .rh-act > .rh-badge { grid-column: 1 / -1; justify-content: center; text-align: center; }
            .rh .rh-act > .rh-btn, .rh .rh-act > .rh-retake > .rh-btn, .rh .rh-act > .tg-replay { display: inline-flex; align-items: center; justify-content: center; width: 100%; min-width: 0; margin: 0; padding: .6rem .5rem; font-size: .84rem; line-height: 1.2; white-space: nowrap; box-sizing: border-box; }
            .rh .rh-split { max-width: none; }
            .rh .rh-leg { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .3rem .6rem; }
            .rh .rh-leg span { min-width: 0; white-space: nowrap; }
            .rh .rh-sub { flex-direction: column; align-items: flex-start; gap: .35rem; }
        }
        @media (prefers-reduced-motion: reduce) { .rh-ring .fg, .rh-split i { animation: none; } }
    </style>
    <section class="on-dark rh text-white">
        <div class="rh-in">
            <div>
                <h1 class="rh-title">{{ $attempt->type->name }}</h1>
                <p class="rh-sub">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>{{ $attempt->finished_at->translatedFormat('d F Y, H:i') }}</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 2h6"/></svg>{{ intdiv($secs, 60) }} mnt {{ $secs % 60 }} dtk</span>
                </p>
                <div class="rh-split" role="img" aria-label="{{ $benar }} benar, {{ $salah }} salah, {{ $kosong }} kosong"><i class="b" style="width: {{ $pctB }}%"></i><i class="s" style="width: {{ $pctS }}%"></i><i class="k" style="width: {{ $pctK }}%"></i></div>
                <p class="rh-leg"><span><i style="background:#3ecf8e"></i><b>{{ $benar }}</b> benar</span><span><i style="background:#ef6a5b"></i><b>{{ $salah }}</b> salah</span><span><i style="background:#8b99b3"></i><b>{{ $kosong }}</b> kosong</span></p>
                <div class="rh-act">
                    @if ($isFull)
                        <span @class(['rh-badge', 'rh-ok' => $attempt->passed, 'rh-no' => ! $attempt->passed])><svg viewBox="0 0 24 24" aria-hidden="true">@if ($attempt->passed)<path d="M5 12.5l4.5 4.5L19 7.5"/>@else<path d="M6 6l12 12M18 6L6 18"/>@endif</svg>{{ $attempt->passed ? 'Lulus passing grade' : 'Belum lulus passing grade' }}</span>
                    @endif
                    @if (! empty($rank))
                        <span class="rh-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg>Peringkat #{{ $rank }}</span>
                    @endif
                    @include('exam.partials.result-share')
                    <button type="button" class="tg-replay rh-replay-btn" data-tour-open><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1.1.9-1.1 1.7M12 17h.01"/></svg>Panduan</button>
                    @if ($isFull && $fullLeft < 1)
                        <a href="{{ route('billing.index', ['jenis' => $attempt->type?->code]) }}#tiket" class="rh-btn"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Jatah habis, beli tiket</a>
                    @else
                        <form method="POST" action="{{ route('exam.retake', $attempt) }}" class="rh-retake" data-retake @if ($isFull && $fullLeft < 100000) data-confirm="Mengulang ujian penuh memakai 1 kesempatan tiket (sisa {{ $fullLeft }}, jadi {{ $fullLeft - 1 }} setelahnya). Kerjakan dengan sungguh-sungguh dan siapkan waktunya sampai selesai. Kalau jatah habis, simulasi ini terkunci lagi." data-confirm-title="Pakai 1 kesempatan tiket?" data-confirm-ok="Ya, ulangi" data-confirm-tone="warning" @endif>
                            @csrf
                            <button type="submit" class="rh-btn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>Ulangi simulasi</button>
                        </form>
                    @endif
                    <a href="{{ route('exam.index') }}" class="rh-btn">Ujian lain<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>
            </div>
            <div class="rh-score">
                <small>Skor total</small>
                <div class="rh-ring" role="img" aria-label="Skor {{ $attempt->total_score }} dari {{ $maxTotal }}">
                    <svg viewBox="0 0 36 36"><defs><linearGradient id="rh-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7fb0ff"/><stop offset="1" stop-color="#4ee0b0"/></linearGradient></defs><circle class="bg" cx="18" cy="18" r="15.9155"/><circle class="fg" cx="18" cy="18" r="15.9155" stroke-dasharray="{{ min(100, $pct) }} {{ 100 - min(100, $pct) }}"/></svg>
                    <div><b class="rh-num">{{ $attempt->total_score }}</b><small>dari {{ $maxTotal }}</small></div>
                </div>
                <p class="rh-of">{{ $pct }}% dari skor maksimal</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-6">
        @include('exam.partials.result-stats', ['insights' => $insights, 'attempt' => $attempt, 'review' => $review, 'aiOn' => $aiOn && $isFull, 'isFull' => $isFull, 'isPremium' => $isPremium])
    </section>

    {{-- Tur pengenalan hasil (sekali, kunjungan pertama) --}}
    @include('exam.partials.result-tour')

    {{-- Widget tanya jawab Owlspire (kiri bawah) --}}
    @include('exam.partials.result-chat', ['attempt' => $attempt, 'insights' => $insights])
@endsection
