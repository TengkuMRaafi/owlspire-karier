{{-- Lompat ke halaman tertentu. Hanya tampil di HP (CSS). $paginator, $live (true = daftar yang dimuat langsung tanpa reload). --}}
<form class="ui-pg-jump" data-pg-jump data-url="{{ $paginator->url(1) }}" @if (! empty($live)) data-live @endif>
    <label for="pgj-{{ $paginator->getPageName() }}">Ke halaman</label>
    <input id="pgj-{{ $paginator->getPageName() }}" type="number" inputmode="numeric" min="1" max="{{ $paginator->lastPage() }}" value="{{ $paginator->currentPage() }}" aria-label="Nomor halaman, 1 sampai {{ $paginator->lastPage() }}">
    <span>dari {{ $paginator->lastPage() }}</span>
    <button type="submit">Buka</button>
</form>
