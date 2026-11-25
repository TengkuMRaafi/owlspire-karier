@props([
    'locked' => false,
    'used' => 0,
    'limit' => 0,
    'premium' => false,
    'title' => 'Jatah hari ini sudah habis',
    'lead' => null,
    'keep' => null,
    'radius' => '1.1rem',
])

{{--
    Kunci bagian halaman saat jatah harian AI habis. Isi (slot) tetap dirender server tetapi tidak bisa disentuh (inert),
    lalu ditutup kartu penjelasan dengan hitung mundur. Server tetap menolak permintaan saat jatah habis, kunci ini hanya tampilan.
    Kunci bisa dipasang lewat JS tanpa muat ulang: root.dispatchEvent(new CustomEvent('quota:lock', { detail: { used, limit } })).
    Gaya: resources/css/components/quota-lock.css (awalan ql-). Perilaku: resources/js/quota-lock.js.
--}}
@php
    $reset = \App\Services\Ai\AiQuota::resetsAt();
    $uid = 'ql-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
    $lead ??= $premium
        ? 'Kamu sudah memakai seluruh jatah harian paket Premium. Jatah terisi lagi otomatis saat hari berganti.'
        : 'Jatah gratis harian sudah terpakai semua. Jatah terisi lagi otomatis, atau buka batas yang lebih longgar dengan Premium.';
@endphp
<div {{ $attributes->class(['ql', 'is-locked' => $locked]) }} style="--ql-r: {{ $radius }}" data-quota-lock data-reset="{{ $reset->toIso8601String() }}" data-now="{{ now()->toIso8601String() }}">
    <div class="ql-body" data-ql-body @if ($locked) inert aria-hidden="true" @endif>{{ $slot }}</div>

    <div class="ql-veil" data-ql-veil @unless ($locked) hidden @endunless>
        <section class="ql-card" tabindex="-1" role="status" aria-labelledby="{{ $uid }}-t" aria-describedby="{{ $uid }}-d" data-ql-card>
            <div class="ql-seal" aria-hidden="true">
                <svg class="ql-ring" viewBox="0 0 100 100">
                    <circle class="ql-ring-bg" cx="50" cy="50" r="44" />
                    <circle class="ql-ring-fg" cx="50" cy="50" r="44" data-ql-ring />
                </svg>
                <span class="ql-owl"><x-owl /><svg class="ql-lids" viewBox="0 0 120 140"><path d="M21 80Q36 93 51 80M69 80Q84 93 99 80" /></svg></span>
                <span class="ql-zz"><i>z</i><i>z</i><i>Z</i></span>
                <span class="ql-badge"><x-icon name="lock" /></span>
            </div>

            <p class="ql-chip"><span>Terpakai <b data-ql-used>{{ $used }}</b> dari <b data-ql-limit>{{ $limit }}</b></span></p>
            <h3 id="{{ $uid }}-t" data-ql-title>{{ $title }}</h3>
            <p id="{{ $uid }}-d" class="ql-lead" data-ql-lead>{{ $lead }}</p>

            <div class="ql-clock" data-ql-clock>
                <span class="ql-clock-l">Terisi lagi dalam</span>
                <div class="ql-digits" role="timer" aria-live="off">
                    <span><b data-ql-h>--</b><small>jam</small></span><i>:</i>
                    <span><b data-ql-m>--</b><small>menit</small></span><i>:</i>
                    <span><b data-ql-s>--</b><small>detik</small></span>
                </div>
                <span class="ql-at">Sekitar pukul <b data-ql-at>{{ $reset->copy()->setTimezone('Asia/Jakarta')->format('H.i') }} WIB</b></span>
            </div>

            <div class="ql-ready" data-ql-ready hidden>Jatahmu sudah terisi lagi. Memuat ulang halaman...</div>

            <div class="ql-act">
                @unless ($premium)
                    <a href="{{ route('account.subscription') }}" class="btn btn-primary"><x-icon name="crown" /> Lihat Premium</a>
                @endunless
                <button type="button" class="btn btn-outline" data-ql-reload hidden>Muat ulang</button>
                {{ $actions ?? '' }}
            </div>

            @if ($keep)
                <p class="ql-keep"><x-icon name="check-circle" /><span>{{ $keep }}</span></p>
            @endif
        </section>
    </div>
</div>
