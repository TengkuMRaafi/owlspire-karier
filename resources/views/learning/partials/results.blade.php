{{-- Bagian yang diganti saat pencarian/filter/halaman berubah (tanpa muat ulang seluruh halaman). --}}
@php $fallbackTheme = ['key' => 'umum', 'label' => 'Karier', 'image' => asset('images/karier/umum.svg')]; @endphp
<p class="ui-count" role="status" aria-live="polite">
    @if ($courses->total() === 0)
        Tidak ada jalur yang cocok
    @else
        Menampilkan {{ $courses->firstItem() }}&ndash;{{ $courses->lastItem() }} dari {{ $courses->total() }} jalur
    @endif
    @if ($q !== '') untuk &ldquo;<b>{{ $q }}</b>&rdquo; @endif
    @if ($kategori !== '') di <b>{{ $kategori }}</b> @endif
</p>

@if ($courses->count())
        <div class="bl-grid">
            @foreach ($courses as $i => $c)
                @php
                    $theme = $c->career ? $themes->for($c->career) : (in_array($c->slug, ['jalur-cpns','jalur-bumn','jalur-tni-polri']) ? ['key' => $c->slug, 'label' => $c->category, 'image' => asset('images/simulasi/'.['jalur-cpns' => 'cpns', 'jalur-bumn' => 'bumn', 'jalur-tni-polri' => 'polri'][$c->slug].'.svg')] : $fallbackTheme);
                    $pct = (int) ($progress[$c->id] ?? 0);
                    $nodes = max(2, min(6, (int) ($c->sections_count ?: 4)));
                    $doneNodes = (int) floor($pct / 100 * $nodes);
                    $name = $c->career?->name ?? $c->title;
                    $sum = $c->career?->summary ?? $c->summary;
                @endphp
                <article class="bl-card" style="--i: {{ min($i, 12) }}">
                    <div class="bl-card-top">
                        <span class="bl-ic"><img src="{{ $theme['image'] }}" alt="" loading="lazy" decoding="async"></span>
                        <div class="bl-card-meta">
                            <p class="bl-cat">{{ $c->category }}</p>
                            <p class="bl-stat">{{ $c->sections_count ?: 4 }} topik{!! $c->lessons_count ? ' &middot; '.e($c->lessons_count).' materi' : '' !!}</p>
                        </div>
                        @if ($c->is_premium)
                            <span class="bl-badge">Premium</span>
                        @endif
                    </div>

                    {{-- Jalur mini: titik terisi = topik yang sudah selesai --}}
                    <div class="bl-mini" aria-hidden="true" style="--n: {{ $nodes }}">
                        @for ($n = 0; $n < $nodes; $n++)
                            <i @class(['is-done' => $n < $doneNodes, 'is-now' => $n === $doneNodes && $pct > 0 && $pct < 100]) style="--k: {{ $n }}"></i>
                        @endfor
                    </div>

                    <h3 class="font-display">{{ $name }}</h3>
                    <p class="bl-sum">{{ $sum }}</p>

                    @if ($pct > 0)
                        <div class="bl-prog" role="img" aria-label="Progres {{ $pct }} persen">
                            <span><b style="width: {{ $pct }}%"></b></span>
                            <small>{{ $pct }}% selesai</small>
                        </div>
                    @endif

                    <a href="{{ route('learning.show', $c) }}" class="bl-go">
                        <span>{{ $pct >= 100 ? 'Buka lagi jalur' : ($pct > 0 ? 'Lanjutkan jalur' : 'Buka jalur') }}</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </article>
            @endforeach
        </div>

    @include('partials.live-pager', ['paginator' => $courses, 'label' => 'Halaman jalur belajar'])
@elseif ($total === 0)
    <p class="bl-empty">Belum ada jalur belajar. Jalankan <code>php artisan db:seed</code>.</p>
@else
    <div class="ui-empty">
        <p class="ui-empty-t">Belum ada jalur yang cocok{{ $q !== '' ? ' dengan “'.$q.'”' : '' }}.</p>
        <p>Coba kata kunci lain, misalnya <em>data</em>, <em>desain</em>, atau <em>hukum</em>.</p>
        <a href="{{ route('learning.index') }}#jalur" data-live-link data-reset class="btn-outline mt-4">Tampilkan semua jalur</a>
    </div>
@endif
