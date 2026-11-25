@props(['label' => 'Bot Telegram'])
@php
    // Tautan publik biasa ke t.me: tanpa login dan tanpa webhook.
    // Username dibaca dari TELEGRAM_BOT_USERNAME; kalau kosong atau config ter-cache lama, pakai bot bawaan supaya tombol tidak hilang.
    $bot = trim((string) (config('platform.telegram.bot') ?: 'jobfinder_id_bot'));
    $bot = $bot !== '' ? preg_replace('~^(https?://)?t\.me/~i', '', ltrim($bot, '@')) : null;
    // Kelas bawaan hanya dipakai bila pemanggil tidak memberi kelas sendiri. Sebelumnya keduanya digabung, sehingga
    // "btn-outline" (teks gelap) menimpa "btn-outline-light" (teks putih) dan tombol jadi gelap-di-atas-gelap di footer.
    $cls = $attributes->get('class') ?: 'btn-outline btn-sm';
@endphp
@if ($bot)
    <a href="https://t.me/{{ $bot }}" target="_blank" rel="noopener" {{ $attributes->except('class') }} class="{{ $cls }}">{{ $label }}</a>
@endif
