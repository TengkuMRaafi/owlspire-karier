{{-- Strip "Seleksi terdekat" di landing. Tidak tampil bila belum ada jadwal yang terbit. Butuh $selectionEvents. --}}
@php $events = $selectionEvents ?? collect(); @endphp
@if ($events->isNotEmpty())
    <section class="ss" aria-labelledby="ss-t">
        <style>
            .ss { border-block: 1px solid var(--color-graphite-200, #e2e6ec); background: #fff; }
            .ss-in { max-width: 72rem; margin: 0 auto; padding: 2.2rem 1rem; }
            .ss-top { display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: .6rem 1rem; }
            .ss-top h2 { font-family: var(--font-display, inherit); font-size: 1.5rem; font-weight: 700; letter-spacing: -.01em; color: var(--color-graphite-900, #1b2638); }
            .ss-top p { margin-top: .2rem; font-size: .92rem; color: var(--color-graphite-500, #667794); }
            .ss-all { font-size: .86rem; font-weight: 800; color: var(--color-accent-700, #2f58b3); }
            .ss-all:hover { text-decoration: underline; text-underline-offset: 3px; }
            .ss-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: .9rem; margin-top: 1.2rem; }
            .ss-card { display: flex; flex-direction: column; gap: .5rem; padding: 1rem 1.1rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: 1.1rem; background: var(--color-surface, #f3f5f8); transition: border-color .15s, transform .15s; }
            .ss-card:hover { border-color: var(--color-accent-400, #7fa8e8); transform: translateY(-2px); }
            .ss-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
            .ss-chip { padding: .25rem .7rem; border-radius: 99px; background: var(--color-accent-600, #3b6cd4); font-size: .74rem; font-weight: 800; white-space: nowrap; color: #fff; }
            .ss-chip.is-live { background: #1f9d68; }
            .ss-card h3 { font-size: .98rem; font-weight: 800; line-height: 1.35; color: var(--color-graphite-800, #1f2a3d); }
            .ss-card small { font-size: .82rem; color: var(--color-graphite-500, #667794); }
        </style>
        <div class="ss-in">
            <div class="ss-top">
                <div>
                    <h2 id="ss-t">Seleksi terdekat</h2>
                    <p>Tanggal penting yang perlu kamu catat.</p>
                </div>
                <a href="{{ route('selection.index') }}" class="ss-all">Lihat semua jadwal &rarr;</a>
            </div>
            <div class="ss-grid">
                @foreach ($events as $e)
                    @php $w = $e->window(); @endphp
                    <a href="{{ route('selection.index', ['kategori' => $e->category]) }}" class="ss-card">
                        <div class="ss-row">
                            <span class="ui-badge is-accent">{{ $e->categoryLabel() }}</span>
                            <span @class(['ss-chip', 'is-live' => $w['state'] === 'live'])>{{ $w['label'] }}</span>
                        </div>
                        <h3>{{ $e->title }}</h3>
                        <small>@if ($e->stage){{ $e->stage }} &middot; @endif{{ $e->rangeLabel() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
