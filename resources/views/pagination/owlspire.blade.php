{{-- Pagination Laravel bergaya Owlspire (sama dengan daftar Karier). Dipasang sebagai tampilan bawaan di AppServiceProvider. --}}
@if ($paginator->hasPages())
    @php
        $cur = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last, $cur - 1, $cur, $cur + 1])->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
    @endphp
    <nav class="ui-pager is-sm" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="ui-pg ui-pg-edge is-off" aria-disabled="true"><x-icon name="chevron-left" /><span class="ui-pg-t">Sebelumnya</span></span>
        @else
            <a class="ui-pg ui-pg-edge" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icon name="chevron-left" /><span class="ui-pg-t">Sebelumnya</span></a>
        @endif

        <span class="ui-pg-nums">
            @php $prev = 0; @endphp
            @foreach ($pages as $p)
                @if ($p - $prev > 1)<span class="ui-gap" aria-hidden="true">&hellip;</span>@endif
                @if ($p === $cur)
                    <span class="ui-pg is-cur" aria-current="page">{{ $p }}</span>
                @else
                    <a class="ui-pg" href="{{ $paginator->url($p) }}" aria-label="Halaman {{ $p }}">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach
        </span>

        @if ($paginator->hasMorePages())
            <a class="ui-pg ui-pg-edge" href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="ui-pg-t">Berikutnya</span><x-icon name="chevron-right" /></a>
        @else
            <span class="ui-pg ui-pg-edge is-off" aria-disabled="true"><span class="ui-pg-t">Berikutnya</span><x-icon name="chevron-right" /></span>
        @endif
    </nav>
@endif
