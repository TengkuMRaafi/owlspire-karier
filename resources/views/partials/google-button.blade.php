{{-- Tombol "Lanjutkan dengan Google": tampil hanya bila GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET terisi. --}}
@if (app(\App\Services\Auth\GoogleOAuth::class)->enabled())
    <style>
        .au-google{display:flex;align-items:center;justify-content:center;gap:.6rem;width:100%;padding:.7rem .75rem;white-space:nowrap;border-radius:.75rem;border:1px solid rgba(100,116,139,.45);background:transparent;color:inherit;font-weight:700;font-size:.9rem;text-decoration:none}
        .au-google:hover{background:rgba(100,116,139,.08)}
        .au-google svg{width:1.15rem;height:1.15rem;flex:none}
        .au-or{display:flex;align-items:center;gap:.75rem;margin:1.1rem 0;font-size:.8rem;color:rgba(100,116,139,.95)}
        .au-or::before,.au-or::after{content:"";flex:1;height:1px;background:rgba(100,116,139,.3)}
    </style>
    @if (($sep ?? null) !== false && ($sepFirst ?? false))
        <div class="au-or" role="separator"><span>{{ $sep ?? 'atau pakai email' }}</span></div>
    @endif
    <a href="{{ route('login.google') }}" class="au-google" rel="nofollow">
        <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.9-6.1A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
        <span>{{ $label ?? 'Lanjutkan dengan Google' }}</span>
    </a>
    @if (($sep ?? null) !== false && ! ($sepFirst ?? false))
        <div class="au-or" role="separator"><span>{{ $sep ?? 'atau pakai email' }}</span></div>
    @endif
@endif
