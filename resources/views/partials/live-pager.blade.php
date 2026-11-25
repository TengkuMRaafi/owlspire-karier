{{-- Pagination bersama untuk daftar yang dimuat langsung (Belajar, Simulasi). --}}
@if ($paginator->hasPages())
    @php
        $cur = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last, $cur - 1, $cur, $cur + 1])->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
        $keep = $cur < $last ? [$cur, $cur + 1] : [$cur - 1, $cur]; // di HP hanya 2 nomor, sisanya lewat panah
        $arrow = fn ($d) => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="'.($d === 'l' ? 'M15 6l-6 6 6 6' : 'M9 6l6 6-6 6').'"/></svg>';
    @endphp
    <nav class="ui-pager" aria-label="{{ $label ?? 'Halaman' }}">
        @if ($paginator->onFirstPage())
            <span class="ui-pg ui-pg-edge is-off" aria-disabled="true">{!! $arrow('l') !!}<span class="ui-pg-t">Sebelumnya</span></span>
        @else
            <a class="ui-pg ui-pg-edge" href="{{ $paginator->previousPageUrl() }}" data-live-link rel="prev">{!! $arrow('l') !!}<span class="ui-pg-t">Sebelumnya</span></a>
        @endif

        <span class="ui-pg-nums">
            @php $prev = 0; @endphp
            @foreach ($pages as $p)
                @if ($p - $prev > 1)<span class="ui-gap" aria-hidden="true">&hellip;</span>@endif
                @if ($p === $cur)
                    <span class="ui-pg is-cur" aria-current="page">{{ $p }}</span>
                @else
                    <a class="ui-pg{{ in_array($p, $keep, true) ? '' : ' ui-pg-x' }}" href="{{ $paginator->url($p) }}" data-live-link aria-label="Halaman {{ $p }}">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach
        </span>

        @if ($paginator->hasMorePages())
            <a class="ui-pg ui-pg-edge" href="{{ $paginator->nextPageUrl() }}" data-live-link rel="next"><span class="ui-pg-t">Berikutnya</span>{!! $arrow('r') !!}</a>
        @else
            <span class="ui-pg ui-pg-edge is-off" aria-disabled="true"><span class="ui-pg-t">Berikutnya</span>{!! $arrow('r') !!}</span>
        @endif
    </nav>
@endif
