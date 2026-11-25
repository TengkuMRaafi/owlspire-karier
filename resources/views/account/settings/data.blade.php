<section id="data" class="pn-card ac-set-sec" aria-labelledby="h-data">
    <header class="ac-set-h">
        <span class="ac-set-ic" aria-hidden="true"><x-icon name="file" /></span>
        <div><h2 id="h-data">Data dan akun</h2><p>Unduh salinan datamu, hapus data tertentu, atau tutup akun.</p></div>
    </header>

    <ul class="ac-set-acts">
        <li>
            <div><b>Unduh data saya</b><small>Profil, preferensi, riwayat ujian, topik selesai, CV, chat Asisten AI, dan pesanan dalam satu berkas JSON.</small></div>
            <a href="{{ route('account.settings.export') }}" class="btn-outline btn-sm">Unduh</a>
        </li>
        <li>
            <div><b>Riwayat chat Asisten AI</b><small>{{ $chatCount }} pesan tersimpan.</small></div>
            <form method="POST" action="{{ route('account.settings.chat.clear') }}" data-confirm="Hapus semua riwayat chat?">
                @csrf @method('DELETE')
                <button class="btn-outline btn-sm" type="submit" @disabled($chatCount === 0)>Hapus</button>
            </form>
        </li>
        <li>
            <div><b>Data analisis CV</b><small>{{ $hasCv ? 'Teks CV dan skill hasil analisis tersimpan.' : 'Belum ada CV yang dianalisis.' }}</small></div>
            <form method="POST" action="{{ route('account.settings.cv.clear') }}" data-confirm="Hapus data CV?">
                @csrf @method('DELETE')
                <button class="btn-outline btn-sm" type="submit" @disabled(! $hasCv)>Hapus</button>
            </form>
        </li>
    </ul>

    <div class="ac-set-danger">
        <h3 class="ac-set-sub"><x-icon name="alert" /> Hapus akun</h3>
        @if ($user->isAdmin())
            <p class="ui-hint">Akun admin tidak bisa dihapus dari halaman ini.</p>
        @else
            <p>Akun, preferensi, chat, CV, dan kemajuan belajarmu dihapus permanen. Riwayat pesanan dan hasil ujian tetap tersimpan tanpa nama untuk pembukuan. Premium yang masih aktif ikut hilang.</p>
            <form method="POST" action="{{ route('account.settings.destroy') }}" class="ac-set-form" data-confirm="Hapus akunmu secara permanen? Tindakan ini tidak bisa dibatalkan.">
                @csrf @method('DELETE')
                @if ($user->mustUseGoogle())
                    <div class="ui-field">
                        <label for="delete_email">Ketik emailmu ({{ $user->email }}) untuk mengonfirmasi</label>
                        <input id="delete_email" type="email" name="confirm_email" autocomplete="off" class="ui-input" required>
                        @error('confirm_email')<small role="alert">{{ $message }}</small>@enderror
                    </div>
                @else
                    <div class="ui-field">
                        <label for="delete_password">Kata sandi saat ini</label>
                        <input id="delete_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required>
                    </div>
                @endif
                <label class="ui-check ac-set-row"><input type="checkbox" name="confirm" value="1" required><i aria-hidden="true"></i><span><b>Saya mengerti akunku tidak bisa dipulihkan.</b></span></label>
                <div><button class="btn-danger" type="submit">Hapus akun permanen</button></div>
            </form>
        @endif
    </div>
</section>
