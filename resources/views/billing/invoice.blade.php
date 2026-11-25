@extends('layouts.app')

@section('title', 'Tagihan '.$order->invoice_no)

@section('content')
    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $claimed = ! empty($order->meta['claimed_at']);
        $paid = $order->status === 'paid';
        $dead = ! $paid && (in_array($order->status, ['expired', 'failed'], true) || ($order->expires_at && $order->expires_at->isPast()));
        $step = $paid ? 3 : 2;
        // Rincian diskon disimpan di tagihan saat dibuat, jadi tetap benar walau promo sudah berakhir.
        $orig = (int) ($order->meta['original_amount'] ?? 0);
        $cut = $orig > $order->amount ? ((int) ($order->meta['discount'] ?? 0) ?: $orig - (int) $order->amount) : 0;
        $promoLabel = $order->meta['promo_label'] ?? 'Diskon Premium';
    @endphp

    <div class="iv-page">
        <header class="iv-hero">
            <div class="iv-hero-in">
                <p class="iv-eyebrow"><span aria-hidden="true"></span>{{ $paid ? 'Pembayaran selesai' : 'Tagihan pembayaran' }}</p>
                <h1 class="iv-title font-display">{{ $order->meta['item_name'] ?? 'Tes Premium' }}</h1>
                <p class="iv-meta">
                    <span class="iv-chip">No. {{ $order->invoice_no }}</span>
                    @if ($email)<span class="iv-chip">{{ $email }}</span>@endif
                </p>

                <ol class="iv-steps" aria-label="Tahap pembelian">
                    <li class="is-done"><i>1</i><span>Pilih paket</span></li>
                    <li class="{{ $step > 2 ? 'is-done' : 'is-now' }}"><i>2</i><span>Bayar</span></li>
                    <li class="{{ $step > 2 ? 'is-now' : '' }}"><i>3</i><span>Premium aktif</span></li>
                </ol>
            </div>
            <span class="iv-orb iv-orb-a" aria-hidden="true"></span>
            <span class="iv-orb iv-orb-b" aria-hidden="true"></span>
        </header>

        <div class="iv-wrap">
            @if ($paid)
                <article class="iv-done">
                    <div class="iv-done-top">
                        <span class="iv-seal" aria-hidden="true">
                            <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="23"/><path d="M15 27l8 8 14-16"/></svg>
                        </span>
                        <h2 class="iv-done-title font-display">Pembayaran diterima</h2>
                        <p class="iv-done-text">{{ $order->item_type === 'plan' ? 'Premium kamu sudah aktif. Semua yang terkunci tadi sekarang sudah terbuka.' : 'Tiket kamu aktif. Kamu bisa langsung memulai ujian penuh.' }}</p>
                    </div>

                    <div class="iv-perf" aria-hidden="true"></div>

                    <div class="iv-done-body">
                        <dl class="iv-done-rec">
                            <div><dt>Paket</dt><dd>{{ $order->meta['item_name'] ?? 'Tes Premium' }}</dd></div>
                            <div><dt>No. tagihan</dt><dd>{{ $order->invoice_no }}</dd></div>
                            @if ($order->paid_at)
                                <div><dt>Dibayar</dt><dd>{{ $order->paid_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                            @endif
                            @if ($cut > 0)
                                <div><dt>Harga normal</dt><dd><s class="pr-old">{{ $fmt($orig) }}</s></dd></div>
                                <div class="is-cut"><dt>{{ $promoLabel }}</dt><dd>-{{ $fmt($cut) }}</dd></div>
                            @endif
                            <div class="is-total"><dt>Total</dt><dd>{{ $fmt($order->amount) }}</dd></div>
                        </dl>

                        @if ($order->item_type === 'plan')
                            <ul class="iv-perks" aria-label="Yang sudah terbuka">
                                <li><span><x-icon name="check" /></span>Ujian penuh tanpa batas</li>
                                <li><span><x-icon name="check" /></span>Analisis CV</li>
                                <li><span><x-icon name="check" /></span>Jalur belajar lengkap</li>
                            </ul>
                        @endif

                        @if (auth()->guest())
                            <p class="iv-done-note">{{ $order->item_type === 'exam_pass' ? 'Tiket simulasi aktif' : 'Premium aktif' }} di akun <strong>{{ $email }}</strong>. Masuk dengan email itu untuk memakainya.</p>
                            <div class="iv-done-actions"><a href="{{ route('login') }}" class="btn-primary">Masuk</a></div>
                        @elseif ($type)
                            <div class="iv-done-actions"><a href="{{ route('exam.show', $type) }}" class="btn-primary">Mulai ujian {{ $type->name }}</a></div>
                        @else
                            <div class="iv-done-actions">
                                <a href="{{ route('exam.index') }}" class="btn-primary">Mulai ujian penuh</a>
                                <a href="{{ route('cv.index') }}" class="btn-outline">Analisis CV</a>
                            </div>
                        @endif
                        @if ($initialPassword)
                            <section class="iv-cred" aria-labelledby="iv-cred-h">
                                <h3 id="iv-cred-h" class="iv-cred-h"><x-icon name="lock" /> Akunmu sudah dibuatkan</h3>
                                <p class="iv-cred-lead">Kamu tidak perlu daftar. Pakai email dan sandi ini untuk masuk lagi dari perangkat lain. Salin dan simpan sekarang.</p>
                                <div class="iv-cred-row">
                                    <label for="iv-cred-email">Email</label>
                                    <div class="iv-cred-box">
                                        <input id="iv-cred-email" type="text" value="{{ $email }}" readonly>
                                        <button type="button" class="iv-cred-copy" data-copy="#iv-cred-email" aria-label="Salin email"><x-icon name="copy" /><span>Salin</span></button>
                                    </div>
                                </div>
                                <div class="iv-cred-row">
                                    <label for="iv-cred-pass">Sandi acak</label>
                                    <div class="iv-cred-box">
                                        <input id="iv-cred-pass" type="text" value="{{ $initialPassword }}" readonly autocomplete="off" spellcheck="false">
                                        <button type="button" class="iv-cred-copy" data-copy="#iv-cred-pass" aria-label="Salin sandi"><x-icon name="copy" /><span>Salin</span></button>
                                    </div>
                                </div>
                                <p class="iv-cred-note">Mau sandi yang lebih mudah diingat? <a href="{{ route('account.settings') }}#keamanan"><strong>Ganti di Pengaturan</strong></a>, tanpa perlu sandi lama. Setelah diganti, sandi acak ini tidak tampil lagi.</p>
                            </section>
                        @elseif ($accountCreated && auth()->check())
                            <p class="iv-done-note">Kami membuatkan akun untuk <strong>{{ $email }}</strong> dan kamu sudah masuk di perangkat ini.</p>
                        @endif
                    </div>
                </article>
            @elseif ($dead)
                <div class="iv-result is-bad">
                    <svg class="iv-check" viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="23"/><path d="M18 18l16 16M34 18L18 34"/></svg>
                    <h2 class="iv-result-title">Tagihan ini sudah tidak berlaku</h2>
                    <p class="iv-result-text">Waktu pembayaran habis atau tagihan dibatalkan. Buat tagihan baru, tidak ada biaya yang terpotong.</p>
                    <div class="iv-actions"><a href="{{ route('billing.index') }}" class="btn-primary">Buat tagihan baru</a></div>
                </div>
            @else
                <article class="iv-ticket">
                    <div class="iv-pay">
                        <p class="iv-label">Total bayar</p>
                        <p class="iv-amount font-display">{{ $fmt($order->amount) }}</p>

                        @if ($order->expires_at)
                            <p class="iv-timer" role="timer">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 3h6"/></svg>
                                Bayar dalam <b data-countdown="{{ $order->expires_at->toIso8601String() }}">--:--</b>
                            </p>
                        @endif

                        <div class="iv-qr">
                            <span class="iv-corner c1" aria-hidden="true"></span><span class="iv-corner c2" aria-hidden="true"></span>
                            <span class="iv-corner c3" aria-hidden="true"></span><span class="iv-corner c4" aria-hidden="true"></span>
                            @if ($payUrl)
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;min-height:260px;padding:16px;text-align:center">
                                    <p style="font-size:.9rem;opacity:.8">Pilih metode bayar di halaman aman Midtrans: QRIS, e-wallet, atau virtual account bank.</p>
                                    <a href="{{ $payUrl }}" class="btn-primary px-6 py-3">Bayar sekarang</a>
                                </div>
                            @elseif ($dynamicQr)
                                @if (! empty($order->meta['qr_string']))
                                    <canvas data-qr="{{ $order->meta['qr_string'] }}" width="280" height="280" aria-label="Kode QRIS pembayaran"></canvas>
                                @else
                                    <img src="{{ $order->meta['qr_url'] }}" alt="Kode QRIS pembayaran">
                                @endif
                                <span class="iv-scan" aria-hidden="true"></span>
                            @elseif ($qris)
                                <img src="{{ $qris }}" alt="Kode QRIS pembayaran">
                            @else
                                <p class="iv-noqr">Gambar QRIS belum dipasang. Taruh berkas <code>public/images/qris.png</code>.</p>
                            @endif
                        </div>

                        @if (! $payUrl)
                            <p class="iv-qris"><b>QRIS</b> Semua bank dan e-wallet</p>
                            <p class="iv-exact">Bayar <strong>tepat {{ $fmt($order->amount) }}</strong>, jangan lebih atau kurang.</p>
                        @endif

                        @if ($dynamicQr && ! $payUrl)
                            <button type="button" class="iv-dl" data-qr-download
                                data-qr-string="{{ $order->meta['qr_string'] ?? '' }}"
                                data-qr-url="{{ $order->meta['qr_url'] ?? '' }}"
                                data-logo="{{ asset('images/logo-mark.svg') }}"
                                data-name="{{ $order->meta['item_name'] ?? 'Tes Premium' }}"
                                data-invoice="{{ $order->invoice_no }}"
                                data-amount="{{ $fmt($order->amount) }}"
                                data-expiry="{{ $order->expires_at?->translatedFormat('d M Y, H:i') }} WIB">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg>
                                <span>Unduh kartu QR</span>
                            </button>
                        @endif
                    </div>

                    <div class="iv-cut" aria-hidden="true"><span></span></div>

                    <div class="iv-side">
                        <h2 class="iv-how">Cara membayar</h2>
                        <ol class="iv-how-list">
                            @if ($payUrl)
                                <li><i>1</i><div><b>Tekan Bayar sekarang</b><span>Kamu diarahkan ke halaman pembayaran Midtrans.</span></div></li>
                                <li><i>2</i><div><b>Pilih metode</b><span>QRIS, e-wallet, atau virtual account bank, lalu selesaikan pembayaran {{ $fmt($order->amount) }}.</span></div></li>
                            @else
                                <li><i>1</i><div><b>Buka aplikasi</b><span>Bank atau e-wallet apa saja yang mendukung QRIS.</span></div></li>
                                <li><i>2</i><div><b>Pindai kode QR</b><span>Pastikan nominal {{ $fmt($order->amount) }}, lalu konfirmasi.</span></div></li>
                            @endif
                            @if ($dynamicQr || $payUrl)
                                <li><i>3</i><div><b>Selesai</b><span>Halaman ini mengecek otomatis. Premium aktif begitu pembayaran masuk, tanpa konfirmasi manual.</span></div></li>
                            @else
                                <li><i>3</i><div><b>Simpan bukti bayar</b><span>Lalu tekan tombol di bawah.</span></div></li>
                                <li><i>4</i><div><b>Admin memeriksa</b><span>Setelah dikonfirmasi, aksesmu aktif.</span></div></li>
                            @endif
                        </ol>

                        @if ($dynamicQr || $payUrl)
                            <div data-poll-status="{{ route('billing.status', $order) }}" class="iv-status" role="status">
                                <span class="iv-dot" aria-hidden="true"></span>
                                <span><span data-poll-text>Menunggu pembayaran</span><em aria-hidden="true">...</em><small data-poll-note style="display:block;opacity:.7"></small></span>
                            </div>
                        @elseif ($claimed)
                            <div data-poll-status="{{ route('billing.status', $order) }}" class="iv-status is-wait" role="status">
                                Menunggu verifikasi admin. Halaman ini bisa ditutup; tiket muncul otomatis begitu pembayaran dikonfirmasi.
                            </div>
                        @else
                            <form method="POST" action="{{ route('billing.claim', $order) }}" class="iv-claim">
                                @csrf
                                <button type="submit" class="btn-primary w-full py-3">Saya sudah bayar</button>
                            </form>
                        @endif

                        <dl class="iv-receipt">
                            <div><dt>Paket</dt><dd>{{ $order->meta['item_name'] ?? 'Tes Premium' }}</dd></div>
                            <div><dt>No. tagihan</dt><dd>{{ $order->invoice_no }}</dd></div>
                            @if ($cut > 0)
                                <div><dt>Harga normal</dt><dd><s class="pr-old">{{ $fmt($orig) }}</s></dd></div>
                                <div class="is-cut"><dt>{{ $promoLabel }}</dt><dd>-{{ $fmt($cut) }}</dd></div>
                            @endif
                            <div class="is-total"><dt>Total</dt><dd>{{ $fmt($order->amount) }}</dd></div>
                        </dl>

                        <p class="iv-secure">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
                            Pembayaran diproses aman oleh Midtrans. Kami tidak menyimpan data rekening atau e-walletmu.
                        </p>
                        @include('billing._refund-note', ['class' => 'iv-done-note'])

                        @if ($canSimulate)
                            <form method="POST" action="{{ route('billing.simulate', $order) }}" class="iv-dev">
                                @csrf
                                <button type="submit" class="btn-outline w-full text-xs">[Dev] Simulasikan pembayaran lunas</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endif
        </div>
    </div>
@endsection
