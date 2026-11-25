{{-- Bagian yang diganti saat pencarian/filter/halaman berubah (tanpa muat ulang seluruh halaman). --}}
<p class="ui-count" role="status" aria-live="polite">
    @if ($careerPage->total() === 0)
        Tidak ada simulasi yang cocok
    @else
        Menampilkan {{ $careerPage->firstItem() }}&ndash;{{ $careerPage->lastItem() }} dari {{ $careerPage->total() }} simulasi karier
    @endif
    @if ($q !== '') untuk &ldquo;<b>{{ $q }}</b>&rdquo; @endif
    @if ($bidang !== '' && isset($themeOptions[$bidang])) di <b>{{ $themeOptions[$bidang] }}</b> @endif
</p>

@if ($careerPage->count())
    <div class="sx-grid">
        @foreach ($careerPage as $i => $type)
            @php
                $theme = $careerThemes[$type->id] ?? ['key' => 'umum', 'label' => 'Karier', 'image' => asset('images/karier/umum.svg')];
                $trialQ = (int) $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count));
            @endphp
            <article class="sx-card" data-id="{{ $type->id }}" style="--i: {{ $i }}">
                <div class="sx-card-top">
                    <span class="sx-ic"><img src="{{ $theme['image'] }}" alt="" loading="lazy" decoding="async"></span>
                    <span class="sx-tag">{{ $theme['label'] }}</span>
                </div>
                @php $info = $careerInfo->get($type->id); @endphp
                @php $shortName = preg_replace('/^Simulasi\s+/u', '', $type->name); @endphp
                <h3>@if ($shortName !== $type->name)<span class="sx-pre">Simulasi </span>@endif{{ $shortName }}</h3>
                @if ($info)
                    <p class="sx-div">{{ $info->division }}</p>
                    <p class="sx-desc">{{ $info->summary }}</p>
                    @if ($info->skills->isNotEmpty())
                        <ul class="sx-skills" aria-label="Skill yang dicari">
                            @foreach ($info->skills->take(6) as $sk)<li>{{ $sk->name }}</li>@endforeach
                        </ul>
                    @endif
                @else
                    <p class="sx-desc">{{ $type->description }}</p>
                @endif
                <p class="sx-meta">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/></svg>{{ $trialQ }} soal</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 2h6"/></svg>{{ (int) ($type->trial_duration_seconds / 60) }} menit</span>
                </p>
                <a href="{{ $info ? route('careers.show', $info) : route('exam.show', $type) }}" class="sx-lnk">Coba simulasi <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </article>
        @endforeach
    </div>

    @include('partials.live-pager', ['paginator' => $careerPage, 'label' => 'Halaman simulasi karier'])

    @if ($showAll || $careerPage->hasPages())
        @php $base = array_filter(['q' => $q, 'bidang' => $bidang]); @endphp
        <div class="sx-all">
            @if ($showAll)
                <a href="{{ route('exam.index', $base) }}#karier" class="sx-all-btn" data-live-link data-scroll>
                    Tampilkan lebih sedikit
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
                </a>
            @else
                <a href="{{ route('exam.index', $base + ['all' => 1]) }}#karier" class="sx-all-btn" data-live-link>
                    Tampilkan semua {{ $careerPage->total() }} simulasi
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </a>
            @endif
        </div>
    @endif
@else
    <div class="ui-empty">
        <p class="ui-empty-t">Belum ada yang cocok{{ $q !== '' ? ' dengan “'.$q.'”' : '' }}.</p>
        <p>Coba kata kunci lain, misalnya <em>data</em>, <em>akuntansi</em>, <em>mesin</em>, atau <em>logistik</em>.</p>
        <a href="{{ route('exam.index') }}#karier" data-live-link data-reset class="btn-outline mt-4">Tampilkan semua simulasi</a>
    </div>
@endif
