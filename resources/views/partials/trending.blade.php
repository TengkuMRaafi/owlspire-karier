{{-- PAPAN KARIER: urutan dari data nyata (lowongan, simulasi selesai, karier target), diperbarui tiap N --}}
@php
    $tbItems = $trending['items']->take(5);
    $tbFirst = $tbItems->first();
    $tbOthers = $tbItems->slice(1);
    $tbFmt = fn ($d) => $d->copy()->locale('id')->translatedFormat('j M Y');
@endphp

<div id="papan-karier" class="tb" data-tb>
    <div class="duo-head" data-reveal>
        <div class="duo-top">
            <p class="eyebrow">Papan karier</p>
            <a href="{{ route('exam.index').'#karier' }}" class="pk-all">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4zM9 7V5h6v2M4 12h16"/></svg>
                <span>Lihat semua karier</span>
                <svg class="pk-all-ar" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
        <h2 class="font-display tracking-tight text-ink">Top 5 karier favorit saat ini.</h2>
        <p class="duo-sub">Diperbarui otomatis tiap {{ $trending['days'] }} hari dari lowongan di situs resmi sumbernya, simulasi yang selesai, dan karier yang dipilih pengguna sebagai target.</p>
    </div>

    @if ($tbFirst)
        @php $c = $tbFirst['career']; @endphp
        <div class="tb-board" data-reveal>
            <a href="{{ route('careers.show', $c) }}" class="tb-lead">
                <span class="tb-lead-top">
                    <svg class="tb-crown" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 7.6 7 11.4 12 3.5l5 7.9 4.8-3.8-2 11.4H4.2z"/><rect x="4.4" y="20.2" width="15.2" height="1.8" rx=".9"/></svg>
                    <span class="tb-badge">Peringkat teratas</span>
                    @include('partials.trending-delta', ['d' => $tbFirst['delta']])
                </span>
                <span class="tb-no">{{ str_pad($tbFirst['position'], 2, '0', STR_PAD_LEFT) }}</span>
                <span class="tb-lead-body">
                    <span class="tb-name">{{ $c->name }}</span>
                    <span class="tb-div">{{ $c->division }}</span>
                </span>
                <span class="tb-heat"><i style="--w: {{ $tbFirst['heat'] }}%"></i></span>
                <span class="tb-stats">
                    <span><b data-n="{{ $tbFirst['jobs'] }}">{{ $tbFirst['jobs'] }}</b><small>lowongan</small></span>
                    <span><b data-n="{{ $tbFirst['sims'] }}">{{ $tbFirst['sims'] }}</b><small>simulasi</small></span>
                    <span><b data-n="{{ $tbFirst['targets'] }}">{{ $tbFirst['targets'] }}</b><small>target</small></span>
                </span>
                <span class="tb-more">Lihat karier</span>
            </a>

            <ol class="tb-list" start="2">
                @foreach ($tbOthers as $it)
                    @php $c = $it['career']; @endphp
                    <li style="--k: {{ $loop->iteration }}">
                        <a href="{{ route('careers.show', $c) }}" class="tb-item">
                            <span class="tb-ino">{{ str_pad($it['position'], 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="tb-itxt"><b>{{ $c->name }}</b><small>{{ $c->division }}</small></span>
                            <span class="tb-mini">
                                <span><b data-n="{{ $it['jobs'] }}">{{ $it['jobs'] }}</b><small>lowongan</small></span>
                                <span><b data-n="{{ $it['sims'] }}">{{ $it['sims'] }}</b><small>simulasi</small></span>
                                <span><b data-n="{{ $it['targets'] }}">{{ $it['targets'] }}</b><small>target</small></span>
                            </span>
                            @include('partials.trending-delta', ['d' => $it['delta']])
                            <span class="tb-heat"><i style="--w: {{ $it['heat'] }}%"></i></span>
                        </a>
                    </li>
                @endforeach
            </ol>

            <div class="tb-meta">
                <span class="tb-live{{ $trending['live'] ? ' is-live' : '' }}">{{ $trending['live'] ? 'Data langsung' : 'Urutan awal, menunggu data' }}</span>
                <span>Diperbarui {{ $tbFmt($trending['updated_at']) }}</span>
                <span>Berikutnya {{ $tbFmt($trending['next_at']) }}</span>
                @if ($trending['live'])
                    <span class="tb-tot">{{ number_format($trending['totals']['jobs'], 0, ',', '.') }} lowongan &middot; {{ number_format($trending['totals']['sims'], 0, ',', '.') }} simulasi &middot; {{ number_format($trending['totals']['targets'], 0, ',', '.') }} target</span>
                @endif
                <details class="tb-note">
                    <summary>Cara hitung</summary>
                    <p>1 lowongan = 1 poin, 1 simulasi selesai = {{ (int) config('platform.trending.weight_sim') }} poin, 1 pengguna yang menargetkan = {{ (int) config('platform.trending.weight_target') }} poin. Panah menunjukkan naik atau turun dibanding pembaruan sebelumnya.</p>
                </details>
            </div>
        </div>
    @endif

</div>

<script>
    (function () {
        var root = document.querySelector('[data-tb]'); if (!root) return;
        var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        function run() {
            root.classList.add('is-go');
            [].forEach.call(root.querySelectorAll('[data-n]'), function (el) {
                var to = +el.dataset.n, t0 = performance.now();
                if (reduce || !to) return;
                (function f(now) { var k = Math.min(1, (now - t0) / 1400); el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(f); })(t0);
            });
        }
        if (!('IntersectionObserver' in window)) return run();
        var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { run(); io.disconnect(); } }, { threshold: .15 });
        io.observe(root);
    })();
</script>
