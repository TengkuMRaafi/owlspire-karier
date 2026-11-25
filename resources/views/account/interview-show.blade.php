@extends('layouts.dashboard')

@php
    $done = $session->status === 'done';
    $legacy = $session->isLegacy();
    $turns = $session->turns();
    if (! $legacy && isset($turns[0]) && ($turns[0]['role'] ?? '') === 'interviewer') {
        $turns[0]['text'] = \App\Services\Ai\InterviewCoach::stripGreeting((string) $turns[0]['text']);
    }
    $report = $session->report;
    $pairs = $legacy ? [] : $session->pairs();
    $questions = $session->questions ?? [];
    $answers = $session->answers ?? [];
    $emoMap = ['netral' => 'neutral', 'senang' => 'happy', 'ragu' => 'doubt', 'kesal' => 'annoyed', 'marah' => 'angry'];
    $emoNow = $emoMap[$lastMood ?? 'netral'] ?? 'neutral';
    if ($done && ! in_array($emoNow, ['annoyed', 'angry'], true)) { $emoNow = 'happy'; }
    $moodNow = $emoNow === 'happy' ? 'smile' : 'listening';
    $emoLabel = ['neutral' => 'Mendengarkan', 'happy' => 'Tersenyum', 'doubt' => 'Tampak ragu', 'annoyed' => 'Tampak kesal', 'angry' => 'Marah'];
@endphp
@section('title', 'Wawancara AI')
@section('heading', $session->role)
@section('subheading', ($kinds[$session->kind] ?? '').($done ? ' · selesai' : ''))
@section('actions')
    <a href="{{ route('account.interview') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <style>
        .iv { max-width: 48rem; margin-inline: auto; }
        .iv-room { position: relative; height: 21rem; overflow: hidden; border-radius: 1.5rem; background: radial-gradient(26rem 16rem at 50% 30%, #2c4a82, #1b2638 70%); }
        .iv-scene { position: absolute; inset: 0; z-index: 0; }
        .iv-scene svg { display: block; width: 100%; height: 100%; }
        .iv-scene::after { content: ''; position: absolute; inset: 0; background: transparent; transition: background .6s; }
        .iv-room[data-emo='doubt'] .iv-scene::after { background: rgb(245 190 60 / .10); }
        .iv-room[data-emo='annoyed'] .iv-scene::after { background: rgb(210 70 50 / .18); }
        .iv-room[data-emo='angry'] .iv-scene::after { background: rgb(200 30 30 / .30); }
        .iv-tl, .iv-stage, .iv-foot { z-index: 1; }
        .iv-tl { position: absolute; inset: 1.1rem 1.25rem auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
        .iv-tl span { font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #b9c9e6; }
        .iv-tl .iv-pill { padding: .25rem .8rem; border-radius: 99px; background: rgb(255 255 255 / .14); color: #fff; letter-spacing: 0; text-transform: none; font-size: .78rem; }
        .iv-stage { position: absolute; left: 50%; bottom: 0; width: 17rem; height: 18.7rem; transform: translateX(-50%); }
        .iv-foot { position: absolute; inset: auto 1.25rem 1.1rem; display: flex; align-items: flex-end; justify-content: space-between; gap: .6rem; }
        .iv-tag { min-width: 0; padding: .6rem 1rem; border-radius: .9rem; background: rgb(255 255 255 / .12); color: #fff; }
        .iv-tag b { display: block; font-size: .95rem; } .iv-tag small { font-size: .74rem; color: #b9c9e6; }
        .iv-st { flex: none; max-width: 55%; display: flex; align-items: center; gap: .5rem; padding: .5rem .9rem; border-radius: 99px; background: rgb(255 255 255 / .14); color: #fff; font-size: .8rem; font-weight: 700; line-height: 1.2; }
        .iv-st i { flex: none; width: .55rem; height: .55rem; border-radius: 50%; background: #5ee0a0; }
        .iv-chat { display: flex; flex-direction: column; gap: .75rem; padding: 1.4rem; }
        /* Live: satu ucapan pewawancara, besar dan terbaca seperti teks percakapan langsung */
        .iv-steps { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: .3rem; }
        .iv-steps i { height: .32rem; border-radius: 99px; background: var(--color-graphite-200, #e2e6ec); transition: background .4s; }
        .iv-steps i.is-done { background: var(--color-accent-600, #3b6cd4); }
        .iv-steps i.is-now { background: var(--color-accent-400, #7fa8e8); }
        .iv-meta { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-top: .35rem; }
        .iv-meta b { font-size: .8rem; font-weight: 700; color: var(--color-graphite-500, #667794); }
        .iv-wave { display: inline-flex; align-items: center; gap: 3px; height: 1rem; }
        .iv-wave i { width: 3px; height: 30%; border-radius: 3px; background: var(--color-graphite-300, #c5cfde); transition: background .3s; }
        .iv-wave.is-on i { background: var(--color-accent-600, #3b6cd4); animation: ivw-bar .9s ease-in-out infinite; }
        .iv-wave.is-on i:nth-child(2) { animation-delay: .15s; } .iv-wave.is-on i:nth-child(3) { animation-delay: .3s; } .iv-wave.is-on i:nth-child(4) { animation-delay: .45s; }
        @keyframes ivw-bar { 0%, 100% { height: 25%; } 50% { height: 100%; } }
        .iv-live { display: flex; flex-direction: column; gap: .9rem; height: 10.5rem; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; padding: .2rem .35rem .4rem 0; }
        .iv-live .iv-m { max-width: none; padding: .15rem 0 .15rem 1.05rem; border-radius: 0; border-left: 3px solid var(--color-accent-400, #7fa8e8); background: none; font-size: 1rem; line-height: 1.65; color: var(--color-graphite-900, #17213a); animation: ivl-in .35s ease both; }
        .iv-live .iv-m.is-sub { border-left-color: var(--color-graphite-200, #e2e6ec); font-size: .88rem; line-height: 1.6; color: var(--color-graphite-500, #667794); }
        .iv-live .iv-m.is-typing::after { content: ''; display: inline-block; width: 2px; height: 1.05em; margin-left: 2px; vertical-align: -.15em; background: var(--color-accent-600, #3b6cd4); animation: ivl-caret .8s steps(1) infinite; }
        .iv-live .iv-m:has(.iv-dots) { border-left-color: transparent; }
        @keyframes ivl-in { from { opacity: 0; transform: translateY(6px); } }
        @keyframes ivl-caret { 50% { opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .iv-live .iv-m, .iv-wave i, .iv-live .iv-m.is-typing::after { animation: none !important; } }
        .iv-gate { display: grid; justify-items: start; gap: .7rem; padding: .2rem 0 .2rem 1.05rem; border-left: 3px solid var(--color-accent-400, #7fa8e8); }
        .iv-gate p { font-size: .92rem; line-height: 1.55; color: var(--color-graphite-700, #3a4863); }
        .iv-gate div { display: flex; flex-wrap: wrap; gap: .5rem; }
        .iv-bar { height: .4rem; border-radius: 99px; background: var(--color-graphite-200, #e2e6ec); overflow: hidden; }
        .iv-bar i { display: block; height: 100%; background: var(--color-accent-600, #3b6cd4); transition: width .5s; }
        .iv-m { max-width: 82%; padding: .8rem 1.05rem; border-radius: 1.1rem 1.1rem 1.1rem .35rem; background: var(--color-graphite-100, #edf0f5); font-size: .92rem; line-height: 1.6; color: var(--color-graphite-800, #1f2a3d); white-space: pre-line; }
        .iv-m.is-u { align-self: flex-end; border-radius: 1.1rem 1.1rem .35rem 1.1rem; background: var(--color-accent-600, #3b6cd4); color: #fff; }
        .iv-dots { display: inline-flex; gap: .3rem; padding: .25rem .1rem; }
        .iv-dots i { width: .5rem; height: .5rem; border-radius: 50%; background: #7c93bd; animation: ivh-pop 1s infinite; }
        .iv-dots i:nth-child(2) { animation-delay: .15s; } .iv-dots i:nth-child(3) { animation-delay: .3s; }
        .iv-ta { width: 100%; min-height: 5.5rem; margin-top: .6rem; resize: vertical; }
        .iv-act { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .7rem; margin-top: .8rem; }
        .iv-act p { max-width: 21rem; font-size: .8rem; line-height: 1.5; color: var(--color-graphite-500, #667794); }
        .iv-err { min-height: 1.1rem; margin-top: .4rem; font-size: .84rem; font-weight: 700; color: #b91c1c; }
        .iv-sc { display: inline-flex; align-items: baseline; gap: .3rem; font-size: 1.5rem; font-weight: 800; color: var(--color-accent-700, #2f58b3); }
        .iv-sc small { font-size: .78rem; font-weight: 700; color: var(--color-graphite-500, #667794); }
        .iv-total { text-align: center; }
        .iv-total b { display: block; font-size: 3rem; line-height: 1; font-weight: 800; color: var(--color-accent-700, #2f58b3); }
        .iv-two, .iv-fb, .iv-sum, .iv-a, .iv-q, .iv-m, .iv-verdict { text-align: left; hyphens: manual; -webkit-hyphens: manual; letter-spacing: normal; word-spacing: normal; }
        .iv-ring { --p: 0; --c: var(--color-accent-600, #3b6cd4); position: relative; display: grid; place-items: center; width: 8rem; aspect-ratio: 1; margin: .2rem auto 0; border-radius: 50%; background: conic-gradient(var(--c) calc(var(--p) * 1%), var(--color-graphite-200, #e2e6ec) 0); }
        .iv-ring::before { content: ''; position: absolute; inset: .6rem; border-radius: 50%; background: var(--pn-card, #fff); }
        .iv-ring.is-good { --c: #16a34a; } .iv-ring.is-low { --c: #d97706; }
        .iv-ring > span { position: relative; display: grid; justify-items: center; line-height: 1; }
        .iv-ring b { font-size: 2.6rem; color: var(--color-graphite-900, #17213a); }
        .iv-ring small { margin-top: .25rem; font-size: .74rem; font-weight: 700; color: var(--color-graphite-500, #667794); }
        .iv-verdict { margin-top: 1rem; text-align: center; }
        .iv-verdict h2 { font-size: 1.15rem; font-weight: 800; color: var(--color-graphite-900, #17213a); }
        .iv-verdict p { max-width: 30rem; margin: .3rem auto 0; font-size: .88rem; line-height: 1.55; color: var(--color-graphite-500, #667794); }
        .iv-sum { max-width: 36rem; margin-inline: auto; }
        .iv-two > div { border-left: 4px solid #16a34a; }
        .iv-two > div.is-improve { border-left-color: #d97706; }
        .iv-two li, .iv-fb li { list-style: none; position: relative; margin: 0 0 .45rem; padding-left: 1.05rem; }
        .iv-two li::before, .iv-fb li::before { content: ''; position: absolute; left: 0; top: .62em; width: .4rem; height: .4rem; border-radius: 50%; background: #16a34a; }
        .iv-two .is-improve li::before, .iv-fb h4.is-improve + ul li::before { background: #d97706; }
        .iv-two ul, .iv-fb ul { margin: 0; padding: 0; list-style: none; }
        .iv-a { margin-top: .5rem; padding: .7rem .9rem; border-left: 3px solid var(--color-accent-400, #7fa8e8); font-size: .9rem; line-height: 1.55; white-space: pre-line; }
        .iv-fb { margin-top: .9rem; padding: .85rem 1.05rem; border-radius: 1rem; background: var(--color-graphite-100, #edf0f5); font-size: .88rem; line-height: 1.55; }
        .iv-fb h4 { margin: .7rem 0 .25rem; font-size: .86rem; font-weight: 800; color: var(--color-graphite-800, #1f2a3d); }
        .iv-fb h4:first-child { margin-top: 0; }
        .iv-room { transition: background .6s; }
        .iv-det { padding: 0; overflow: hidden; }
        .iv-det > summary { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.25rem; cursor: pointer; list-style: none; }
        .iv-det > summary::-webkit-details-marker { display: none; }
        .iv-det > summary::after { content: ''; flex: none; width: .55rem; height: .55rem; margin-left: .2rem; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: rotate(45deg) translateY(-2px); transition: transform .2s; opacity: .6; }
        .iv-det[open] > summary::after { transform: rotate(-135deg); }
        .iv-det > summary:focus-visible { outline: 2px solid var(--color-accent-600, #3b6cd4); outline-offset: -2px; }
        .iv-det .iv-sh { flex: 1; min-width: 0; }
        .iv-det .iv-sh b { display: block; font-size: .95rem; }
        .iv-det .iv-sh span { display: block; font-size: .82rem; color: var(--color-graphite-500, #667794); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .iv-det .iv-body { padding: 0 1.25rem 1.25rem; border-top: 1px solid var(--color-graphite-100, #edf0f5); }
        .iv-det .iv-body > :first-child { margin-top: 1rem; }
        .iv-tools { display: flex; gap: .5rem; margin-top: .7rem; }
        .iv-ic { display: inline-grid; place-items: center; width: 2.6rem; height: 2.6rem; padding: 0; border: 1px solid var(--color-graphite-300, #c5cfde); border-radius: 50%; background: var(--pn-card, #fff); color: var(--color-graphite-500, #667794); cursor: pointer; transition: background .2s, color .2s, border-color .2s; }
        .iv-ic[hidden] { display: none; }
        .iv-ic svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .iv-ic .on { display: none; } .iv-ic[aria-pressed='true'] .on { display: block; } .iv-ic[aria-pressed='true'] .off { display: none; }
        .iv-ic[aria-pressed='true'] { background: var(--color-accent-600, #3b6cd4); border-color: var(--color-accent-600, #3b6cd4); color: #fff; }
        .iv-ic.is-rec { animation: ivic-pulse 1.4s ease-in-out infinite; }
        .iv-ic:focus-visible { outline: 2px solid var(--color-accent-600, #3b6cd4); outline-offset: 2px; }
        @keyframes ivic-pulse { 50% { box-shadow: 0 0 0 .45rem rgb(59 108 212 / .18); } }
        @media (prefers-reduced-motion: reduce) { .iv-ic.is-rec { animation: none; } }
        .iv-sum { margin-top: .9rem; font-size: .92rem; line-height: 1.65; text-align: left; }
        .iv-two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; margin-top: 1.1rem; }
        .iv-two > div { padding: .85rem 1.05rem; border-radius: 1rem; background: var(--color-graphite-100, #edf0f5); font-size: .88rem; line-height: 1.55; }
        .iv-two h4 { margin: 0 0 .45rem; font-size: .95rem; font-weight: 800; color: var(--color-graphite-900, #17213a); }
        .iv-q { font-size: .82rem; color: var(--color-graphite-500, #667794); line-height: 1.5; }
        @media (max-width: 640px) {
            body .pn-main .iv-two { grid-template-columns: minmax(0, 1fr); gap: .7rem; }
            body .pn-main .iv-two > div { padding: .85rem 1rem; font-size: .9rem; line-height: 1.6; }
            .iv-ring { width: 7rem; } .iv-ring b { font-size: 2.2rem; }
            .iv-room { height: 14.5rem; border-radius: 1.2rem; }
            .iv-stage { width: 9.6rem; height: 10.6rem; }
            .iv-tl { inset: .8rem .9rem auto; gap: .35rem; } .iv-tl span { font-size: .66rem; } .iv-tl .iv-pill { padding: .2rem .6rem; font-size: .7rem; }
            .iv-foot { inset: auto .7rem .7rem; gap: .4rem; }
            .iv-tag { padding: .4rem .7rem; border-radius: .7rem; } .iv-tag b { font-size: .8rem; line-height: 1.25; } .iv-tag small { font-size: .66rem; }
            .iv-st { max-width: 52%; gap: .4rem; padding: .38rem .65rem; font-size: .7rem; } .iv-st i { width: .45rem; height: .45rem; }
            .iv-ta { min-height: 5rem; }
            .iv-chat { padding: 1rem; } .iv-m { max-width: 92%; font-size: .88rem; }
            .iv-live { height: 9rem; } .iv-live .iv-m { font-size: .9rem; line-height: 1.6; padding-left: .8rem; } .iv-live .iv-m.is-sub { font-size: .78rem; }
            .iv-act { flex-direction: column; align-items: stretch; } .iv-act p { max-width: none; } .iv-act .btn-primary { width: 100%; }
        }
        @media (max-width: 380px) {
            .iv-room { height: 13.5rem; } .iv-stage { width: 8.6rem; height: 9.6rem; }
            .iv-tag b { font-size: .74rem; } .iv-st { font-size: .66rem; }
        }
    </style>
    <div class="pn-stack iv no-rapi">
        <div class="iv-room" id="iv-room" data-emo="{{ $emoNow }}" data-scene="{{ $session->kind }}">
            @include('account.partials.scene', ['kind' => $session->kind])
            <div class="iv-tl"><span>{{ $kinds[$session->kind] ?? '' }}</span>
                @unless ($done)<span class="iv-pill" id="iv-pill">{{ $count }} jawaban terkirim</span>@endunless</div>
            <div class="iv-stage">@include('account.partials.interviewer', ['gender' => $persona['gender'], 'look' => $persona['look'] ?? [], 'mood' => $moodNow, 'emo' => $emoNow, 'label' => $persona['name'].', '.$persona['title']])</div>
            <div class="iv-foot">
                <div class="iv-tag"><b>{{ $persona['name'] }}</b><small>{{ $persona['title'] }}</small></div>
                <div class="iv-st"><i></i><span id="iv-stat">{{ $emoLabel[$emoNow] }}</span></div>
            </div>
        </div>

        @if (! $done)
            <section class="pn-card iv-chat" aria-label="Percakapan wawancara">
                <div class="iv-steps" id="iv-steps" role="img" aria-label="Kemajuan wawancara">@for ($i = 1; $i <= $target; $i++)<i class="{{ $i <= $count ? 'is-done' : ($i === $count + 1 ? 'is-now' : '') }}"></i>@endfor</div>
                <div class="iv-meta"><b id="iv-qn">Pertanyaan {{ $count + 1 }}</b><span class="iv-wave" id="iv-wave" aria-hidden="true"><i></i><i></i><i></i><i></i></span></div>
                {{-- Mode live: hanya ucapan pewawancara yang sedang berlangsung. Riwayat lengkap tampil di halaman hasil. --}}
                <div class="iv-live" id="iv-log" aria-live="polite">
                    @if ($count === 0)
                        {{-- Sapaan + pertanyaan pembuka digabung jadi satu ucapan (sebelumnya tampil dobel dua gelembung). --}}
                        <div class="iv-m" id="iv-open">{{ trim($greeting.' '.collect($turns)->where('role', 'interviewer')->pluck('text')->implode(' ')) }}</div>
                    @else
                        @php $lastBot = collect($turns)->where('role', 'interviewer')->last(); @endphp
                        @if ($lastBot)<div class="iv-m">{{ $lastBot['text'] }}</div>@endif
                    @endif
                </div>
            </section>

            <form method="POST" action="{{ route('account.interview.answer', $session) }}" class="pn-card" id="iv-form" data-target="{{ $target }}" data-voice="{{ json_encode($persona['voice'] ?? []) }}" data-answered="{{ $count }}" data-finish-from="{{ \App\Services\Ai\InterviewCoach::MIN_TO_EVALUATE }}">
                @csrf
                <label for="iv-answer" class="text-sm font-bold">Jawabanmu</label>
                <textarea id="iv-answer" name="answer" class="ui-input iv-ta" maxlength="1500" placeholder="Ketik jawabanmu di sini…" required>{{ old('answer') }}</textarea>
                <p class="iv-err" id="iv-err" role="alert">@error('answer'){{ $message }}@enderror</p>
                <div class="iv-act">
                    <p>Pewawancara menanggapi jawabanmu dan bisa bertanya lebih dalam. Penilaian dan saran muncul di akhir sesi. Ctrl+Enter untuk mengirim.</p>
                    <button class="btn-primary" type="submit" data-send>Kirim jawaban</button>
                </div>
                <div class="iv-tools">
                    <button type="button" class="iv-ic" id="iv-voice" aria-pressed="false" aria-label="Suara pewawancara mati" title="Suara pewawancara" hidden>
                        <svg class="off" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="m16 9 5 6M21 9l-5 6"/></svg>
                        <svg class="on" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                    </button>
                    <button type="button" class="iv-ic" id="iv-mic" aria-pressed="false" aria-label="Jawab dengan suara: mati" title="Jawab dengan suara" hidden>
                        <svg class="off" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 2 20 20"/><path d="M9 9v3a3 3 0 0 0 5.1 2.1M15 9.3V5a3 3 0 0 0-5.7-1.3"/><path d="M19 11a7 7 0 0 1-.9 3.4M5 11a7 7 0 0 0 11.2 5.6M12 18v3"/></svg>
                        <svg class="on" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('account.interview.finish', $session) }}" id="iv-finish" style="text-align:center" @if (! $canFinish) hidden @endif onsubmit="return confirm('Akhiri wawancara sekarang dan lihat penilaiannya?')">
                @csrf
                <button type="submit" class="btn-outline">Akhiri wawancara dan lihat penilaian</button>
            </form>
        @elseif ($legacy)
            {{-- Sesi lama (sebelum wawancara berbasis percakapan): ditampilkan seperti semula. --}}
            <section class="pn-card iv-total" aria-label="Skor akhir">
                <small class="text-sm text-graphite-500">Skor rata-rata</small>
                <b>{{ $session->total_score }}</b>
                <div style="margin-top:1rem"><a href="{{ route('account.interview') }}" class="btn-primary">Latihan lagi</a></div>
            </section>
            @foreach ($questions as $i => $q)
                @php $a = $answers[$i] ?? null; @endphp
                @if ($a)
                    <details class="pn-card iv-det" aria-label="Pertanyaan {{ $i + 1 }}">
                        <summary><div class="iv-sh"><b>Pertanyaan {{ $i + 1 }}</b><span>{{ $q }}</span></div><span class="iv-sc">{{ $a['score'] }}<small>/100</small></span></summary>
                        <div class="iv-body">
                            <p class="iv-q">{{ $q }}</p>
                            <p class="iv-a">{{ $a['answer'] }}</p>
                            <div class="iv-fb">
                                @if (! empty($a['strengths']))<h4>Sudah bagus</h4><ul>@foreach ($a['strengths'] as $x)<li>{{ $x }}</li>@endforeach</ul>@endif
                                @if (! empty($a['improve']))<h4 class="is-improve">Bisa ditingkatkan</h4><ul>@foreach ($a['improve'] as $x)<li>{{ $x }}</li>@endforeach</ul>@endif
                                @if (! empty($a['sample']))<h4>Contoh jawaban lebih kuat</h4><p>{{ $a['sample'] }}</p>@endif
                            </div>
                        </div>
                    </details>
                @endif
            @endforeach
        @elseif ($report === null)
            <section class="pn-card iv-total" id="iv-grade-card" aria-live="polite" aria-label="Penilaian">
                <h2 class="text-lg font-bold" id="iv-grade-title">Menyusun penilaian…</h2>
                <p class="text-sm text-graphite-500" id="iv-grade-msg" style="margin-top:.5rem">Pewawancara membaca ulang seluruh percakapanmu. Biasanya hanya beberapa detik.</p>
                @if ($errors->has('grade'))<p class="iv-err">{{ $errors->first('grade') }}</p>@endif
                <form method="POST" action="{{ route('account.interview.grade', $session) }}" id="iv-grade-form" style="margin-top:1rem">
                    @csrf
                    <button class="btn-primary" type="submit" id="iv-grade-btn">Buat penilaian</button>
                </form>
            </section>
            <section class="pn-card iv-chat" aria-label="Transkrip">
                <div class="pn-card-h"><h2>Transkrip</h2></div>
                <div class="iv-m">{{ $greeting }}</div>
                @foreach ($turns as $t)
                    <div class="iv-m{{ $t['role'] === 'candidate' ? ' is-u' : '' }}">{{ $t['text'] }}</div>
                @endforeach
            </section>
        @else
            @php
                    $score = (int) $session->total_score;
                    $tone = $score >= 75 ? 'is-good' : ($score >= 55 ? '' : 'is-low');
                    $verdict = $score >= 75 ? ['Jawabanmu sudah kuat', 'Pertahankan struktur dan contoh nyatanya.'] : ($score >= 55 ? ['Cukup baik', 'Tambah contoh nyata dan hasil yang terukur.'] : ['Masih bisa naik banyak', 'Pakai pola situasi, tindakan, hasil di setiap jawaban.']);
                @endphp
            <section class="pn-card iv-total" aria-label="Skor akhir">
                <div class="iv-ring {{ $tone }}" style="--p: {{ max(0, min(100, $score)) }}" role="img" aria-label="Skor {{ $score }} dari 100"><span><b>{{ $score }}</b><small>dari 100</small></span></div>
                <div class="iv-verdict"><h2>{{ $verdict[0] }}</h2><p>{{ $verdict[1] }} Rata-rata dari {{ count($pairs) }} jawaban.</p></div>
                @if (! empty($report['summary']))<p class="iv-sum">{{ $report['summary'] }}</p>@endif
                @if (! empty($report['strengths']) || ! empty($report['improve']))
                    <div class="iv-two">
                        @if (! empty($report['strengths']))<div><h4>Kekuatan utama</h4><ul>@foreach ($report['strengths'] as $x)<li>{{ $x }}</li>@endforeach</ul></div>@endif
                        @if (! empty($report['improve']))<div class="is-improve"><h4>Perlu ditingkatkan</h4><ul>@foreach ($report['improve'] as $x)<li>{{ $x }}</li>@endforeach</ul></div>@endif
                    </div>
                @endif
                <div style="margin-top:1.2rem"><a href="{{ route('account.interview') }}" class="btn-primary">Latihan lagi</a></div>
            </section>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:0 .25rem">
                <h2 class="text-base font-bold">Pembahasan per jawaban</h2>
                <button type="button" class="btn-outline" id="iv-toggle-all" style="font-size:.82rem">Buka semua</button>
            </div>
            @foreach ($pairs as $i => $pair)
                @php $a = $report['answers'][$i] ?? null; @endphp
                <details class="pn-card iv-det" aria-label="Jawaban {{ $i + 1 }}">
                    <summary><div class="iv-sh"><b>Jawaban {{ $i + 1 }}</b><span>{{ $pair['question'] }}</span></div>@if ($a)<span class="iv-sc">{{ $a['score'] }}<small>/100</small></span>@endif</summary>
                    <div class="iv-body">
                        <p class="iv-q">{{ $pair['question'] }}</p>
                        <p class="iv-a">{{ $pair['answer'] }}</p>
                        @if ($a)
                            <div class="iv-fb">
                                @if (! empty($a['strengths']))<h4>Sudah bagus</h4><ul>@foreach ($a['strengths'] as $x)<li>{{ $x }}</li>@endforeach</ul>@endif
                                @if (! empty($a['improve']))<h4 class="is-improve">Bisa ditingkatkan</h4><ul>@foreach ($a['improve'] as $x)<li>{{ $x }}</li>@endforeach</ul>@endif
                                @if (! empty($a['sample']))<h4>Contoh jawaban lebih kuat</h4><p>{{ $a['sample'] }}</p>@endif
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
            <script>
                (function () {
                    var b = document.getElementById('iv-toggle-all'), d = document.querySelectorAll('details.iv-det');
                    if (!b) return;
                    b.addEventListener('click', function () {
                        var open = b.textContent.indexOf('Buka') === 0;
                        d.forEach(function (x) { x.open = open; });
                        b.textContent = open ? 'Tutup semua' : 'Buka semua';
                    });
                })();
            </script>
        @endif
    </div>

    @if ($done && ! $legacy && $report === null)
        <script>
            (function () {
                var form = document.getElementById('iv-grade-form');
                if (!form || !window.fetch) return;
                var btn = document.getElementById('iv-grade-btn'), title = document.getElementById('iv-grade-title'), msg = document.getElementById('iv-grade-msg');
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                function run() {
                    btn.disabled = true; btn.textContent = 'Menyusun…'; title.textContent = 'Menyusun penilaian…';
                    fetch(form.action, { method: 'POST', credentials: 'same-origin', body: '{}',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error((j && j.message) || 'Gagal menyusun penilaian.'); return j; }); })
                        .then(function (j) { location.href = j.url; })
                        .catch(function (x) {
                            title.textContent = 'Penilaian belum berhasil dibuat';
                            msg.textContent = x.message || 'Coba lagi sebentar lagi. Wawancaramu sudah tersimpan.';
                            btn.disabled = false; btn.textContent = 'Coba lagi';
                        });
                }
                form.addEventListener('submit', function (e) { e.preventDefault(); run(); });
                run();
            })();
        </script>
    @endif

    @unless ($done)
        <script>
            (function () {
                var form = document.getElementById('iv-form');
                if (!form || !window.fetch) return; // tanpa JS, formulir tetap bekerja lewat kirim biasa
                var log = document.getElementById('iv-log'), ta = document.getElementById('iv-answer'), btn = form.querySelector('[data-send]');
                var err = document.getElementById('iv-err'), face = document.querySelector('.ivh'), stat = document.getElementById('iv-stat');
                var pill = document.getElementById('iv-pill'), steps = document.getElementById('iv-steps'), qn = document.getElementById('iv-qn'), wave = document.getElementById('iv-wave'), fin = document.getElementById('iv-finish');
                var target = Number(form.dataset.target), finishFrom = Number(form.dataset.finishFrom), reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
                var LABEL = { listening: 'Mendengarkan', thinking: 'Menimbang jawabanmu', smile: 'Tersenyum', talking: 'Sedang berbicara' };
                var EMO = { netral: 'neutral', senang: 'happy', ragu: 'doubt', kesal: 'annoyed', marah: 'angry' };
                var EMO_LABEL = { doubt: 'Tampak ragu', annoyed: 'Tampak kesal', angry: 'Marah' };
                var room = document.getElementById('iv-room'), emo = face.getAttribute('data-emo') || 'neutral';
                function setEmo(e) { emo = e; face.setAttribute('data-emo', e); room.setAttribute('data-emo', e); }
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

                // Suara bawaan browser (gratis): pewawancara bisa berbicara, dan jawaban bisa didiktekan.
                var synth = window.speechSynthesis, SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                var voiceBtn = document.getElementById('iv-voice'), micBtn = document.getElementById('iv-mic'), voiceOn = false, rec = null, listening = false, base = '';
                try { voiceOn = localStorage.getItem('iv-voice') === '1'; } catch (e) {}
                function paintVoice() { voiceBtn.setAttribute('aria-pressed', voiceOn ? 'true' : 'false'); voiceBtn.setAttribute('aria-label', 'Suara pewawancara ' + (voiceOn ? 'hidup' : 'mati')); }
                function paintMic(on) { micBtn.setAttribute('aria-pressed', on ? 'true' : 'false'); micBtn.classList.toggle('is-rec', on); micBtn.setAttribute('aria-label', on ? 'Jawab dengan suara: hidup, ketuk untuk berhenti' : 'Jawab dengan suara: mati'); }

                // Suara tiap pewawancara dibedakan: suara dipilih sesuai gender, lalu nada dan tempo diatur per karakter
                // (data-voice dari persona). Bila perangkat punya lebih dari satu suara Indonesia, "slot" memilih suara yang berbeda.
                var female = (face.getAttribute('data-gender') || 'f') === 'f', chosen = null, VOICE = {};
                try { VOICE = JSON.parse(form.dataset.voice || '{}') || {}; } catch (e) {}
                function pickVoice() {
                    var list = (synth.getVoices() || []).filter(function (v) { return /^id([-_]|$)/i.test(v.lang) || /indonesia/i.test(v.name); });
                    var scored = list.map(function (v) {
                        var n = v.name, sc = 0, fit = false;
                        if (/natural|neural|online/i.test(n)) sc += 8;
                        if (/google/i.test(n)) sc += 5;
                        if (/microsoft/i.test(n)) sc += 3;
                        if (!v.localService) sc += 1;
                        if (/compact|espeak|festival/i.test(n)) sc -= 6;
                        if (female && /gadis|damayanti|indah|female|perempuan|wanita/i.test(n)) { sc += 4; fit = true; }
                        if (!female && /ardi|male|pria|laki/i.test(n) && !/female/i.test(n)) { sc += 4; fit = true; }
                        return { v: v, sc: sc, fit: fit };
                    }).sort(function (a, b) { return b.sc - a.sc; });
                    var pool = scored.filter(function (x) { return x.fit; });
                    if (!pool.length) pool = scored;
                    chosen = pool.length ? pool[(VOICE.slot || 0) % pool.length].v : null;
                    return chosen;
                }
                // Dibacakan per kalimat dengan jeda alami, tempo dan nada sesuai karakter.
                function say(text) {
                    if (!voiceOn || !synth) return;
                    synth.cancel();
                    var v = chosen || pickVoice();
                    var basePitch = VOICE.pitch || (female ? 1.06 : 0.92), baseRate = VOICE.rate || 0.94;
                    var parts = text.replace(/\s+/g, ' ').match(/[^.!?…]+[.!?…]*/g) || [text];
                    parts.forEach(function (part, i) {
                        part = part.trim(); if (!part) return;
                        var u = new SpeechSynthesisUtterance(part.replace(/\bhm+\b/gi, 'hmm,'));
                        u.lang = 'id-ID'; if (v) u.voice = v;
                        u.rate = baseRate + (/[?]$/.test(part) ? 0.02 : (i % 2) * 0.03);
                        u.pitch = Math.max(0.1, Math.min(2, basePitch + (/[?]$/.test(part) ? 0.05 : 0)));
                        synth.speak(u);
                    });
                }
                if (synth) {
                    if (synth.addEventListener) synth.addEventListener('voiceschanged', function () { chosen = null; });
                    voiceBtn.hidden = false; paintVoice();
                    voiceBtn.addEventListener('click', function () {
                        voiceOn = !voiceOn; try { localStorage.setItem('iv-voice', voiceOn ? '1' : '0'); } catch (e) {}
                        if (!voiceOn) synth.cancel();
                        paintVoice();
                    });
                    window.addEventListener('beforeunload', function () { synth.cancel(); });
                }
                if (SR) {
                    micBtn.hidden = false;
                    rec = new SR(); rec.lang = 'id-ID'; rec.interimResults = true; rec.continuous = false;
                    rec.onresult = function (e) { var t = ''; for (var i = 0; i < e.results.length; i++) t += e.results[i][0].transcript; ta.value = base + t; };
                    rec.onend = function () { listening = false; paintMic(false); };
                    rec.onerror = function (e) { err.textContent = e.error === 'not-allowed' ? 'Izin mikrofon ditolak. Aktifkan di pengaturan browser, atau ketik jawabanmu.' : ''; };
                    micBtn.addEventListener('click', function () {
                        if (listening) { rec.stop(); return; }
                        if (synth) synth.cancel();
                        base = ta.value ? ta.value.replace(/\s+$/, '') + ' ' : '';
                        try { rec.start(); listening = true; paintMic(true); err.textContent = ''; } catch (e) {}
                    });
                }

                function paint(n) {
                    Array.prototype.forEach.call(steps.children, function (el, i) { el.className = i < n ? 'is-done' : (i === n ? 'is-now' : ''); });
                    qn.textContent = 'Pertanyaan ' + (n + 1);
                }
                function mood(m) {
                    wave.classList.toggle('is-on', m === 'talking');
                    face.setAttribute('class', face.getAttribute('class').replace(/\bis-\w+/g, '').trim() + ' is-' + m);
                    stat.textContent = (m === 'listening' || m === 'smile') && EMO_LABEL[emo] ? EMO_LABEL[emo] : LABEL[m];
                }
                function wait(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
                function add(el) { log.appendChild(el); return el; }
                function clearLive() { var old = Array.prototype.slice.call(log.querySelectorAll('.iv-m')); old.forEach(function (n) { n.remove(); }); return old; }
                function bubble(text, mine) { var d = document.createElement('div'); d.className = 'iv-m' + (mine ? ' is-u' : ''); d.textContent = text; return add(d); }
                function typing() { var d = document.createElement('div'); d.className = 'iv-m'; d.innerHTML = '<span class="iv-dots"><i></i><i></i><i></i></span>'; return add(d); }
                function speak(text, e) {
                    setEmo(e); mood('talking'); say(text);
                    var d = bubble('', false), i = 0;
                    d.classList.add('is-typing');
                    return new Promise(function (res) {
                        if (reduce) { d.textContent = text; d.classList.remove('is-typing'); res(); return; }
                        var step = voiceOn ? 1 : 2, gap = voiceOn ? 62 : 22; // dengan suara, teks muncul seirama ucapan
                        (function tick() { i += step; d.textContent = text.slice(0, i); log.scrollTop = log.scrollHeight; if (i < text.length) setTimeout(tick, gap); else { d.classList.remove('is-typing'); res(); } })();
                    });
                }
                function unlock() { btn.disabled = false; ta.readOnly = false; }

                // Pembuka: browser tidak mengizinkan suara otomatis tanpa ketukan, jadi pewawancara mulai bicara begitu tombol Mulai diketuk.
                var openEl = document.getElementById('iv-open');
                if (openEl) {
                    var openText = openEl.textContent.trim();
                    openEl.remove(); btn.disabled = true; ta.readOnly = true;
                    var gate = document.createElement('div'); gate.className = 'iv-gate';
                    gate.innerHTML = '<p>Pewawancara sudah siap. Mulai wawancara sekarang?</p><div>'
                        + (synth ? '<button type="button" class="btn-primary" data-go="1">Mulai dengan suara</button><button type="button" class="btn-outline" data-go="0">Tanpa suara</button>'
                            : '<button type="button" class="btn-primary" data-go="0">Mulai wawancara</button>') + '</div>';
                    log.appendChild(gate);
                    gate.addEventListener('click', function (e) {
                        var g = e.target.closest('[data-go]'); if (!g) return;
                        if (synth) { voiceOn = g.dataset.go === '1'; try { localStorage.setItem('iv-voice', voiceOn ? '1' : '0'); } catch (x) {} paintVoice(); }
                        gate.remove();
                        speak(openText, 'neutral').then(function () { mood('listening'); unlock(); ta.focus(); });
                    });
                }

                ta.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); form.requestSubmit(); } });
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (listening && rec) rec.stop();
                    if (synth) synth.cancel();
                    var text = ta.value.trim();
                    if (text.length < 3) { err.textContent = 'Jawabanmu terlalu singkat. Tulis beberapa kata atau kalimat.'; return; }
                    err.textContent = ''; btn.disabled = true; ta.readOnly = true;
                    var prev = clearLive(); ta.value = ''; mood('thinking'); var dots = typing(); // teks lama hilang, tinggal indikator mengetik

                    fetch(form.action, { method: 'POST', credentials: 'same-origin', body: JSON.stringify({ answer: text }),
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) {
                            return r.json().then(function (j) {
                                if (!r.ok) throw new Error((j && (j.message || (j.errors && j.errors.answer && j.errors.answer[0]))) || 'Gagal mengirim.');
                                return j;
                            });
                        })
                        .then(function (j) {
                            var e = EMO[j.mood] || 'neutral';
                            return wait(500).then(function () { dots.remove(); return speak(j.reply, e); })
                                .then(function () {
                                    paint(j.count);
                                    if (pill) pill.textContent = j.count + ' jawaban terkirim';
                                    if (fin && j.count >= finishFrom) fin.hidden = false;
                                    var calm = e === 'happy' ? 'smile' : 'listening';
                                    if (j.done) {
                                        mood(e === 'neutral' ? 'smile' : calm);
                                        return wait(2400).then(function () { location.href = j.url; });
                                    }
                                    mood(calm); unlock(); ta.focus();
                                });
                        })
                        .catch(function (x) {
                            dots.remove(); prev.forEach(function (n) { log.appendChild(n); }); mood('listening');
                            err.textContent = x.message || 'Gagal mengirim. Coba lagi.'; ta.value = text; unlock();
                        });
                });
            })();
        </script>
    @endunless
@endsection
