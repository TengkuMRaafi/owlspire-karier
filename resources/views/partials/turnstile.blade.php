{{-- Cloudflare Turnstile: tampil hanya bila TURNSTILE_SITE_KEY dan TURNSTILE_SECRET_KEY terisi. --}}
@php $ts = app(\App\Services\Security\Turnstile::class); @endphp
@if ($ts->enabled())
    <div class="cf-turnstile" data-sitekey="{{ $ts->siteKey() }}" data-theme="auto" style="min-height:65px"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
