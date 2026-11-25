{{-- Tautan portal resmi + pengingat untuk selalu mengecek pengumuman resmi. $portals. --}}
@once
    <style>
        .sl-portals { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: .7rem; margin-top: .8rem; }
        .sl-portal { display: block; padding: .85rem 1rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: .95rem; background: var(--pn-card, #fff); transition: border-color .15s; }
        .sl-portal:hover { border-color: var(--color-accent-400, #7fa8e8); }
        .sl-portal b { display: block; font-size: .92rem; color: var(--color-graphite-800, #1f2a3d); }
        .sl-portal span { font-size: .8rem; color: var(--color-graphite-500, #667794); }
        .sl-portal-go { display: none; }
        @media (max-width: 639px) {
            /* Mobile: satu kartu satu baris, teks di kiri, ikon panah di kanan sebagai penanda tautan keluar */
            body .sl-portals { grid-template-columns: minmax(0, 1fr); gap: .5rem; }
            body .sl-portal { display: flex; align-items: center; gap: .75rem; padding: .7rem .85rem; border-radius: .9rem; }
            body .sl-portal:active { border-color: var(--color-accent-400, #7fa8e8); }
            body .sl-portal-t { flex: 1 1 auto; min-width: 0; }
            body .sl-portal b { font-size: .9rem; line-height: 1.3; }
            body .sl-portal span { display: block; margin-top: .1rem; font-size: .75rem; line-height: 1.35; }
            body .sl-portal-go { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 1.9rem; height: 1.9rem; border-radius: 50%; background: rgb(59 108 212 / .1); color: var(--color-accent-600, #3b6cd4); }
            body .sl-portal-go svg { width: 1rem; height: 1rem; }
        }
        .sl-disc { margin-top: .8rem; font-size: .82rem; line-height: 1.55; color: var(--color-graphite-500, #667794); }
    </style>
@endonce
@if (count($portals))
    <div class="sl-portals">
        @foreach ($portals as $p)
            <a href="{{ $p['url'] }}" class="sl-portal" target="_blank" rel="noopener noreferrer nofollow">
                <div class="sl-portal-t"><b>{{ $p['name'] }}</b><span>{{ $p['desc'] }}</span></div>
                <i class="sl-portal-go" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg></i>
            </a>
        @endforeach
    </div>
@endif
<p class="sl-disc">Jadwal di sini dirangkum dari pengumuman resmi dan bisa berubah. Selalu cek portal resmi untuk tanggal dan syarat terbaru sebelum mendaftar.</p>
