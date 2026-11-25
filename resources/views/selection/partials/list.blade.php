{{-- Daftar jadwal seleksi. Dipakai halaman publik (/info-seleksi) dan versi di dalam akun. $events, $examCodes. --}}
@once
    <style>
        .sl-list { display: grid; gap: .8rem; }
        .sl-item { transition: border-color .15s, transform .15s; display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 1rem; align-items: start; padding: 1rem 1.2rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: 1.1rem; background: var(--pn-card, #fff); }
        .sl-item:hover { border-color: var(--color-accent-300, #a9c4f2); transform: translateY(-1px); }
        .sl-item.is-live { border-color: var(--color-accent-400, #7fa8e8); box-shadow: 0 0 0 3px rgb(79 130 230 / .12); }
        .sl-item.is-done { opacity: .7; }
        .sl-when { display: grid; place-items: center; width: 3.6rem; padding: .5rem 0; border-radius: .9rem; background: var(--color-accent-50, #eef4fd); color: var(--color-accent-700, #2f58b3); line-height: 1; }
        .sl-when b { font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .sl-when span { margin-top: .2rem; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        html[data-theme='dark'] .sl-when { background: rgb(79 130 230 / .16); color: var(--color-accent-400, #7fa8e8); }
        .sl-tags { display: flex; flex-wrap: wrap; gap: .4rem; }
        .sl-item h3 { margin-top: .45rem; font-size: 1rem; font-weight: 800; line-height: 1.35; color: var(--color-graphite-800, #1f2a3d); }
        .sl-range { display: flex; align-items: center; gap: .4rem; margin-top: .3rem; font-size: .84rem; color: var(--color-graphite-500, #667794); }
        .sl-note { margin-top: .5rem; font-size: .88rem; line-height: 1.55; color: var(--color-graphite-600, #4a5a73); }
        .sl-links { display: flex; flex-wrap: wrap; gap: .5rem 1.1rem; margin-top: .65rem; }
        .sl-link { display: inline-flex; align-items: center; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--color-accent-700, #2f58b3); }
        .sl-link:hover { text-decoration: underline; text-underline-offset: 3px; }
        .sl-chip { display: inline-block; padding: .3rem .8rem; border-radius: 99px; background: var(--color-accent-600, #3b6cd4); font-size: .78rem; font-weight: 800; white-space: nowrap; color: #fff; }
        .sl-chip.is-live { background: #1f9d68; }
        .sl-chip.is-done { background: var(--color-graphite-300, #c5cfde); color: var(--color-graphite-700, #34435c); }
        @media (max-width: 640px) {
            .sl-item { grid-template-columns: auto minmax(0, 1fr); }
            .sl-chip-wrap { grid-column: 2; }
        }
    </style>
@endonce
<div class="sl-list">
    @foreach ($events as $e)
        @php $w = $e->window(); @endphp
        <article @class(['sl-item', 'is-live' => $w['state'] === 'live', 'is-done' => $w['state'] === 'done'])>
            <div class="sl-when" aria-hidden="true"><b>{{ $e->starts_on->translatedFormat('d') }}</b><span>{{ $e->starts_on->translatedFormat('M') }}</span></div>
            <div>
                <div class="sl-tags">
                    <span class="ui-badge is-accent">{{ $e->categoryLabel() }}</span>
                    @if ($e->stage)<span class="ui-badge">{{ $e->stage }}</span>@endif
                </div>
                <h3>{{ $e->title }}</h3>
                <p class="sl-range"><x-icon name="calendar" class="size-4" /> {{ $e->rangeLabel() }}</p>
                @if ($e->note)<p class="sl-note">{{ $e->note }}</p>@endif
                <div class="sl-links">
                    @if ($e->source_url)<a href="{{ $e->source_url }}" class="sl-link" target="_blank" rel="noopener noreferrer nofollow">Pengumuman resmi <x-icon name="arrow-right" class="size-4" /></a>@endif
                    @if (in_array($e->category, $examCodes, true))<a href="{{ route('exam.show', $e->category) }}" class="sl-link">Latihan {{ $e->categoryLabel() }} <x-icon name="arrow-right" class="size-4" /></a>@endif
                </div>
            </div>
            <div class="sl-chip-wrap"><span @class(['sl-chip', 'is-live' => $w['state'] === 'live', 'is-done' => $w['state'] === 'done'])>{{ $w['label'] }}</span></div>
        </article>
    @endforeach
</div>
