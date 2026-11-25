{{-- Pilihan kategori. $route = nama rute halaman yang memanggil, $category = kategori aktif. --}}
@once
    <style>
        .sl-filter { display: flex; flex-wrap: wrap; gap: .45rem; }
        .sl-pill { padding: .4rem .95rem; border: 1.5px solid var(--color-graphite-200, #e2e6ec); border-radius: 99px; background: var(--pn-card, #fff); font-size: .82rem; font-weight: 800; color: var(--color-graphite-700, #34435c); transition: border-color .15s, background .15s, color .15s; }
        .sl-pill:hover { border-color: var(--color-accent-300, #a9c4f2); }
        .sl-pill.is-on { border-color: var(--color-accent-600, #3b6cd4); background: var(--color-accent-600, #3b6cd4); color: #fff; }
        @media (max-width: 639px) {
            /* Mobile: pill lebih ringkas dan melebar mengisi tiap baris, jadi tidak ada ruang kosong di kanan */
            body .sl-filter { gap: .4rem; }
            body .sl-pill { flex: 1 1 auto; padding: .42rem .7rem; font-size: .8rem; line-height: 1.2; text-align: center; white-space: nowrap; }
        }
    </style>
@endonce
<nav class="sl-filter" aria-label="Kategori seleksi">
    <a href="{{ route($route) }}" @class(['sl-pill', 'is-on' => $category === '']) @if ($category === '') aria-current="true" @endif>Semua</a>
    @foreach (\App\Models\SelectionEvent::CATEGORIES as $code => $label)
        <a href="{{ route($route, ['kategori' => $code]) }}" @class(['sl-pill', 'is-on' => $category === $code]) @if ($category === $code) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</nav>
