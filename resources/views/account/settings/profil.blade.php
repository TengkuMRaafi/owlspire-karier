<section id="profil" class="pn-card ac-set-sec" aria-labelledby="h-profil">
    <header class="ac-set-h">
        <span class="ac-set-ic" aria-hidden="true"><x-icon name="user" /></span>
        <div><h2 id="h-profil">Profil</h2><p>Nama dan keterangan singkat tentang dirimu.</p></div>
    </header>

    {{-- Ringkasan paket: tempat user Premium mengelola langganan. --}}
    <div @class(['ac-set-plan', 'is-premium' => $premium])>
        <span class="ac-set-ic" aria-hidden="true"><x-icon :name="$user->isInternal() ? 'shield' : ($premium ? 'crown' : 'sparkle')" /></span>
        <div>
            <b>{{ $user->isInternal() ? 'Peran '.$user->roleLabel() : 'Paket '.($premium ? 'Premium' : 'Gratis') }}</b>
            <small>
                @if ($user->isInternal())
                    Akses penuh ke semua fitur, tanpa paket Gratis atau Premium.
                @elseif ($premium)
                    {{ $premiumUntil ? 'Aktif sampai '.$premiumUntil->translatedFormat('d M Y').'.' : 'Aktif tanpa batas waktu.' }}
                @else
                    Ujian penuh, analisis CV, dan semua topik belajar terbuka di Premium.
                @endif
            </small>
        </div>
        @if ($user->isInternal())
            <a href="{{ route('admin.dashboard') }}" class="btn-outline btn-sm">Buka panel</a>
        @else
            <a href="{{ route('account.subscription') }}" @class(['btn-sm', 'btn-outline' => $premium, 'btn-primary' => ! $premium])>{{ $premium ? 'Kelola langganan' : 'Lihat paket Premium' }}</a>
        @endif
    </div>

    <form method="POST" action="{{ route('account.settings.profile') }}" class="ac-set-form">
        @csrf @method('PUT')
        <div class="ui-form-grid">
            <div class="ui-field">
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="ui-input" required maxlength="80" autocomplete="name">
            </div>
            <div class="ui-field">
                <label for="email">Email</label>
                <input id="email" value="{{ $user->email }}" class="ui-input" disabled>
                <small>Email tidak bisa diubah di sini.</small>
            </div>
            <div class="ui-field is-wide">
                <label for="headline">Keterangan profil <span class="ui-hint">(opsional)</span></label>
                <input id="headline" name="headline" value="{{ old('headline', $user->headline) }}" class="ui-input" maxlength="120" placeholder="Mis. Fresh graduate, mengincar CPNS">
                <small>Dipakai Asisten AI untuk menyesuaikan saran bila "Personalisasi jawaban" aktif. Tidak tampil ke pengguna lain.</small>
            </div>
        </div>
        <div class="ac-set-foot">
            <button class="btn-primary" type="submit">Simpan profil</button>
        </div>
    </form>
</section>
