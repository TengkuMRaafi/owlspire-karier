<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;

/** Cloudflare Turnstile (CAPTCHA tanpa teka-teki). Aktif hanya bila TURNSTILE_SITE_KEY dan TURNSTILE_SECRET_KEY terisi. */
class Turnstile
{
    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret'));
    }

    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function passes(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        if (blank($token)) {
            return false;
        }

        try {
            $res = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'), 'response' => $token, 'remoteip' => $ip,
            ]);

            return (bool) $res->json('success', false);
        } catch (\Throwable) {
            return false;
        }
    }
}
