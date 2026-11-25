<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Masuk dengan Google lewat alur OAuth 2.0 "authorization code" + PKCE, tanpa paket tambahan.
 * Server menukar kode langsung ke Google lewat HTTPS, lalu membaca profil dari endpoint userinfo.
 */
class GoogleOAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /** Pengguna biasa (bukan admin/staf) wajib daftar dan masuk lewat Google. Hanya berlaku bila Google sudah terpasang. */
    public function membersGoogleOnly(): bool
    {
        return $this->enabled() && (bool) config('platform.auth.members_google_only', true);
    }

    public function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('login.google.callback');
    }

    /**
     * @return array{url:string,state:string,verifier:string}
     */
    public function begin(): array
    {
        $state = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $url = self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return ['url' => $url, 'state' => $state, 'verifier' => $verifier];
    }

    /**
     * Tukar kode dengan profil Google. Mengembalikan null bila Google menolak atau datanya tidak lengkap.
     *
     * @return array{sub:string,email:string,email_verified:bool,name:string}|null
     */
    public function profile(string $code, string $verifier): ?array
    {
        $token = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $verifier,
        ]);

        $access = $token->successful() ? $token->json('access_token') : null;
        if (! is_string($access) || $access === '') {
            return null;
        }

        $info = Http::withToken($access)->timeout(10)->get(self::USERINFO_URL);
        if (! $info->successful()) {
            return null;
        }

        $sub = (string) $info->json('sub');
        $email = Str::lower(trim((string) $info->json('email')));
        if ($sub === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Google mengirim boolean; sebagian respons lama berupa string "true". Hanya nilai benar-benar true yang dianggap terverifikasi.
        $verified = $info->json('email_verified');

        return [
            'sub' => $sub,
            'email' => $email,
            'email_verified' => $verified === true || $verified === 'true',
            'name' => trim((string) $info->json('name')) ?: Str::headline(Str::before($email, '@')),
        ];
    }
}
