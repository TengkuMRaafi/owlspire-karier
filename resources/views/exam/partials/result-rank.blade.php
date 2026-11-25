{{-- Peringkat peserta: kartu gelap yang menonjol, di bawah grafik dan di atas tombol belajar/ujian penuh. Non-Premium: angka contoh diburamkan + tombol buka Premium. --}}
@php
    $rk = $insights['rank'];
    $rkTrial = $attempt->mode !== 'full';
    $rkAbove = $rk && $rk['of'] > 1 ? round(($rk['of'] - $rk['pos']) / ($rk['of'] - 1) * 100) : null;
@endphp
@if ($rk)
<style>
    .rr { position: relative; overflow: hidden; display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: 1rem 1.6rem; margin-top: 1rem; padding: 1.3rem 1.6rem; border-radius: 1.4rem; color: #fff;
        background: radial-gradient(22rem 12rem at 0% 50%, rgb(246 196 69 / .22), transparent 70%), radial-gradient(30rem 16rem at 100% 0%, rgb(79 130 230 / .5), transparent 65%), linear-gradient(120deg, #141d2f 20%, #1f2f52);
        box-shadow: 0 24px 44px -28px rgb(20 29 47 / .9); }
    .rr-pod { width: 4.6rem; height: auto; flex: none; filter: drop-shadow(0 0 14px rgb(246 196 69 / .45)); }
    .rr-eyebrow { font-size: .68rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #f6c445; }
    .rr-h { margin-top: .2rem; font-family: var(--font-display, inherit); font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .rr-sub { margin-top: .2rem; font-size: .84rem; color: #b9c9e6; }
    .rr-big { display: block; margin-top: .25rem; font-family: var(--font-display, inherit); font-size: 2.4rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
    .rr-big em { font-style: normal; font-size: .95rem; font-weight: 600; color: #b9c9e6; }
    .rr-bar { display: flex; align-items: center; gap: .7rem; max-width: 24rem; margin-top: .7rem; }
    .rr-bar > div { flex: 1; height: .5rem; border-radius: 99px; background: rgb(255 255 255 / .16); }
    .rr-bar i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #6ea0ff, #f6c445); }
    .rr-bar p { font-size: .82rem; color: #dbe5f6; white-space: nowrap; }
    .rr-tease { margin-top: .6rem; filter: blur(6px); pointer-events: none; user-select: none; }
    .rr-tease .rr-big { margin-top: 0; font-size: 1.9rem; }
    .rr-cta { display: inline-flex; align-items: center; gap: .55rem; padding: .85rem 1.6rem; border-radius: .95rem; background: #f6c445; font-size: .98rem; font-weight: 800; color: #162033; text-decoration: none; white-space: nowrap; box-shadow: 0 14px 26px -12px rgb(246 196 69 / .7); transition: transform .15s, filter .15s; }
    .rr-cta::after { content: ""; position: absolute; inset: 0; border-radius: inherit; }
    .rr-cta:hover { transform: translateY(-1px); filter: brightness(1.05); }
    .rr-cta svg { width: 1.05rem; height: 1.05rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
    .rr-go { padding: .75rem 1.3rem; border: 1.5px solid rgb(255 255 255 / .3); border-radius: .9rem; font-size: .9rem; font-weight: 700; color: #fff; text-decoration: none; white-space: nowrap; transition: background .15s; }
    .rr-go:hover { background: rgb(255 255 255 / .12); }
    @media (max-width: 760px) { .rr { grid-template-columns: auto 1fr; padding: 1.1rem 1.1rem 1.2rem; } .rr-cta, .rr-go { grid-column: 1 / -1; justify-content: center; text-align: center; } .rr-pod { width: 3.6rem; } .rr-bar p { white-space: normal; } }
</style>
@php $pod = '<svg class="rr-pod" viewBox="0 0 64 60" aria-hidden="true"><path d="M32 2l2.6 5.3 5.8.8-4.2 4.1 1 5.8L32 15.2l-5.2 2.8 1-5.8-4.2-4.1 5.8-.8z" fill="#f6c445"/><rect x="24" y="22" width="16" height="36" rx="3" fill="#f6c445"/><rect x="5" y="34" width="16" height="24" rx="3" fill="#6ea0ff"/><rect x="43" y="40" width="16" height="18" rx="3" fill="#4f82e6"/></svg>'; @endphp
@if ($isPremium)
    <section class="rr">
        {!! $pod !!}
        <div>
            <p class="rr-eyebrow">Peringkat kamu</p>
            <b class="rr-big">#{{ $rk['pos'] }} <em>dari {{ $rk['of'] }} peserta</em></b>
            @if ($rkAbove !== null)<div class="rr-bar"><div><i style="width: {{ $rkAbove }}%"></i></div><p>Skormu di atas {{ $rkAbove }}% peserta lain.</p></div>@endif
        </div>
        @unless ($rkTrial)<a class="rr-go" href="{{ route('exam.leaderboard', ['jenis' => $attempt->type->code]) }}">Papan peringkat &rarr;</a>@endunless
    </section>
@else
    <section class="rr">
        {!! $pod !!}
        <div>
            <p class="rr-eyebrow">Peringkat kamu</p>
            <h3 class="rr-h">Kamu ada di urutan berapa?</h3>
            <div class="rr-tease" aria-hidden="true">
                <b class="rr-big">#12 <em>dari 340 peserta</em></b>
                <div class="rr-bar"><div><i style="width: 96%"></i></div><p>Skormu di atas 96% peserta lain.</p></div>
            </div>
        </div>
        <a class="rr-cta" href="{{ route('billing.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Buka dengan Premium</a>
    </section>
@endif
@endif
