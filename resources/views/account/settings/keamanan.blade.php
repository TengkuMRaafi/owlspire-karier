<section id="keamanan" class="pn-card ac-set-sec" aria-labelledby="h-keamanan">
    <header class="ac-set-h">
        <span class="ac-set-ic" aria-hidden="true"><x-icon name="lock" /></span>
        <div><h2 id="h-keamanan">Keamanan</h2><p>Kata sandi dan perangkat yang sedang masuk ke akunmu.</p></div>
    </header>

    @php $viaGoogle = $user->mustUseGoogle(); @endphp
    @if ($viaGoogle)
        <div class="ac-set-form">
            <h3 class="ac-set-sub">Masuk dengan Google</h3>
            <p class="ui-hint">Akunmu masuk lewat Google, jadi tidak ada kata sandi di sini. Kata sandi dan verifikasi 2 langkah dikelola di akun Google-mu (myaccount.google.com).</p>
        </div>
    @else
    <form method="POST" action="{{ route('account.settings.password') }}" class="ac-set-form">
        @csrf @method('PUT')
        @php $first = $user->needsFirstPassword(); @endphp
        <h3 class="ac-set-sub">{{ $first ? ($user->google_id !== null ? 'Buat kata sandi' : 'Ganti sandi acak') : 'Ganti kata sandi' }}</h3>
        @if ($first)<p class="ui-hint">{{ $user->google_id !== null ? 'Akunmu dibuat lewat Google, jadi belum punya kata sandi. Buat satu di sini supaya bisa masuk juga dengan email dan kata sandi.' : 'Akunmu dibuat otomatis saat pembelian dengan sandi acak (yang tampil di halaman pembayaran). Ganti dengan sandi yang mudah kamu ingat di sini, tanpa perlu sandi lama.' }}</p>@endif
        @unless ($first)<div class="ui-field"><label for="current_password">Kata sandi saat ini</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required></div>@endunless
        <div class="ui-form-grid">
            <div class="ui-field"><label for="password">Kata sandi baru</label><input id="password" type="password" name="password" autocomplete="new-password" class="ui-input" required><small>Minimal 8 karakter, campuran huruf dan angka. Perangkat lain ikut keluar setelah diganti.</small></div>
            <div class="ui-field"><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="ui-input" required></div>
        </div>
        <div class="ac-set-foot"><button class="btn-primary" type="submit">Simpan kata sandi</button></div>
    </form>
    @endif

    @if (! $viaGoogle || $twoFactor['enabled'])
    <div class="ac-set-split"></div>

    {{-- ===== Verifikasi 2 langkah (TOTP) ===== --}}
    <div class="ac-set-form" id="dua-langkah">
        <h3 class="ac-set-sub">Verifikasi 2 langkah</h3>
        @if (session('recovery_codes'))
            <div class="ui-hint" style="border:1px solid currentColor;border-radius:.75rem;padding:.9rem 1rem;margin-bottom:1rem">
                <b>Simpan kode pemulihan ini sekarang.</b> Tiap kode hanya bisa dipakai sekali, dan tidak akan ditampilkan lagi. Gunakan bila ponselmu hilang.
                <ul style="columns:2;margin:.75rem 0 0;padding-left:1.1rem;font-family:ui-monospace,monospace">
                    @foreach (session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if ($twoFactor['enabled'])
            <p class="ui-hint"><span class="ui-badge is-success">Aktif</span> Saat masuk, selain kata sandi kamu perlu kode 6 digit dari aplikasi autentikator. Sisa kode pemulihan: <b>{{ $twoFactor['recoveryLeft'] }}</b>.</p>
            @unless ($viaGoogle)
            <form method="POST" action="{{ route('account.settings.2fa.recovery') }}" class="ac-set-inline">
                @csrf
                <div class="ui-field"><label for="rec_password">Kata sandi saat ini</label><input id="rec_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required></div>
                <button class="btn-outline" type="submit">Buat kode pemulihan baru</button>
            </form>
            @endunless
            <form method="POST" action="{{ route('account.settings.2fa.disable') }}" class="ac-set-inline" data-confirm="Akunmu hanya terlindungi kata sandi setelah ini." data-confirm-title="Matikan verifikasi 2 langkah?" data-confirm-ok="Ya, matikan">
                @csrf @method('DELETE')
                @unless ($viaGoogle)
                <div class="ui-field"><label for="off_password">Kata sandi saat ini</label><input id="off_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required></div>
                @endunless
                <button class="btn-outline" type="submit">Matikan verifikasi 2 langkah</button>
            </form>
        @elseif ($twoFactor['pending'])
            <p class="ui-hint">1. Buka aplikasi autentikator (Google Authenticator, Authy, Microsoft Authenticator), pilih tambah akun manual, lalu masukkan kunci ini:</p>
            <p style="font-family:ui-monospace,monospace;font-size:1.05rem;letter-spacing:.08em;word-break:break-all;padding:.6rem .8rem;border:1px dashed currentColor;border-radius:.6rem;margin:.5rem 0">{{ trim(chunk_split($twoFactor['secret'], 4, ' ')) }}</p>
            <p class="ui-hint">Di ponsel yang sama, <a href="{{ $twoFactor['uri'] }}">buka langsung di aplikasi autentikator</a>.</p>
            <p class="ui-hint">2. Masukkan kode 6 digit yang muncul untuk mengonfirmasi:</p>
            <form method="POST" action="{{ route('account.settings.2fa.confirm') }}" class="ac-set-inline">
                @csrf
                <div class="ui-field"><label for="tf_code">Kode 6 digit</label><input id="tf_code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" class="ui-input" required style="letter-spacing:.2em"></div>
                <button class="btn-primary" type="submit">Aktifkan</button>
            </form>
        @else
            <p class="ui-hint">Tambahkan lapis kedua saat masuk: selain kata sandi, kamu perlu kode dari aplikasi autentikator di ponselmu. {{ auth()->user()->isInternal() ? 'Sangat disarankan untuk akun admin dan staf.' : '' }}</p>
            <form method="POST" action="{{ route('account.settings.2fa.begin') }}" class="ac-set-inline">
                @csrf
                <button class="btn-primary" type="submit">Aktifkan verifikasi 2 langkah</button>
            </form>
        @endif
    </div>
    @endif

    <div class="ac-set-split"></div>

    <div class="ac-set-form">
        <h3 class="ac-set-sub">Perangkat yang masuk</h3>
        @if (! $user->isInternal())
            <p class="ui-hint">Satu akun bisa dipakai masuk di maksimal {{ (int) config('platform.security.max_devices', 2) }} perangkat atau peramban sekaligus. Login baru akan mengeluarkan yang paling lama tidak aktif. Jangan bagikan akunmu ke orang lain.</p>
        @endif
        @if ($devicesSupported)
            <ul class="ac-set-devs">
                @forelse ($devices as $d)
                    <li>
                        <span class="ac-set-dev-ic" aria-hidden="true"><x-icon name="layout" /></span>
                        <span class="ac-set-dev-t"><b>{{ $d['browser'] }} di {{ $d['os'] }}</b><small>{{ $d['ip'] ?: 'IP tidak diketahui' }} &middot; aktif {{ $d['last_active']->translatedFormat('d M, H:i') }}</small></span>
                        @if ($d['current'])<span class="ui-badge is-success">Perangkat ini</span>@endif
                    </li>
                @empty
                    <li class="ui-hint">Belum ada sesi tercatat.</li>
                @endforelse
            </ul>
        @else
            <p class="ui-hint">Daftar perangkat butuh sesi yang disimpan di database (<code>SESSION_DRIVER=database</code>). Tombol di bawah tetap mengganti token "ingat saya".</p>
        @endif

        <form method="POST" action="{{ route('account.settings.devices.logout') }}" class="ac-set-inline" data-confirm="Semua perangkat lain akan keluar dari akunmu." data-confirm-title="Keluar dari perangkat lain?" data-confirm-ok="Ya, keluarkan" data-confirm-tone="logout">
            @csrf
            @unless ($viaGoogle)
            <div class="ui-field">
                <label for="logout_password">Kata sandi saat ini</label>
                <input id="logout_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required>
            </div>
            @endunless
            <button class="btn-outline" type="submit">Keluar dari perangkat lain</button>
        </form>
    </div>
</section>
