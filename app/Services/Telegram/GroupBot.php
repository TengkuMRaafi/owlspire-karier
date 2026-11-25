<?php

namespace App\Services\Telegram;

use App\Jobs\SendGroupDigest;
use App\Models\TelegramGroup;
use App\Services\Notify\Notifier;
use Illuminate\Support\Facades\Cache;

/** Bot di grup: terdaftar saat bot dimasukkan, pengaturan zona waktu dan topik oleh admin grup. */
class GroupBot
{
    public function __construct(private Notifier $notifier)
    {
    }

    public static function commands(): array
    {
        return [
            ['command' => 'zona', 'description' => 'Atur zona waktu grup (WIB, WITA, WIT)'],
            ['command' => 'topik', 'description' => 'Atur topik: posisi | keahlian | lokasi'],
            ['command' => 'jenis', 'description' => 'Pilih kerja atau magang'],
            ['command' => 'jadwal', 'description' => 'Lihat pengaturan dan jadwal kiriman'],
            ['command' => 'contoh', 'description' => 'Kirim contoh info lowongan sekarang'],
            ['command' => 'berhenti', 'description' => 'Jeda kiriman ke grup ini'],
            ['command' => 'mulai', 'description' => 'Lanjutkan kiriman ke grup ini'],
        ];
    }

    // ---------------------------------------------------------------- chat pribadi (langganan per orang)

    /** Baris langganan untuk satu chat pribadi; belum aktif sampai orangnya menjalankan /langganan. */
    public function subscriber(string $chat, ?string $name = null): TelegramGroup
    {
        return TelegramGroup::firstOrCreate(['chat_id' => $chat], ['title' => mb_substr((string) $name, 0, 120), 'is_active' => false]);
    }

    /** Perintah pengaturan di chat pribadi. Tidak perlu cek admin: orang hanya mengatur kiriman miliknya sendiri. */
    public function privateCommand(string $chat, string $cmd, string $arg, ?string $name): void
    {
        $g = $this->subscriber($chat, $name);

        match ($cmd) {
            'zona' => $this->setZone($g, mb_strtolower($arg)),
            'topik' => $this->setTopic($g, $arg),
            'jenis' => $this->setKind($g, mb_strtolower($arg)),
            'jadwal' => $this->say($chat, $this->summary($g)),
            'berhenti' => $this->toggle($g, false),
            'contoh' => $this->preview($g),
        };
    }

    /** Simpan topik lalu aktifkan kiriman. Dipakai /langganan (satu baris maupun dipandu). */
    public function saveSubscription(string $chat, array $positions, array $skills, array $locations, ?string $name = null): void
    {
        if (! $positions && ! $skills) {
            $this->say($chat, "Isi minimal satu posisi atau keahlian. Contoh:\n/langganan data analyst | excel, sql | Jakarta");

            return;
        }
        $g = $this->subscriber($chat, $name);
        $g->update(['positions' => $positions ?: array_slice($skills, 0, 3), 'skills' => $skills, 'locations' => $locations, 'sent_urls' => null]);
        $this->activate($g);
    }

    /** Aktifkan kiriman dan tampilkan tombol zona waktu. */
    public function activate(TelegramGroup $g): void
    {
        if (! $g->positions) {
            $this->say($g->chat_id, "Atur topikmu dulu: /langganan data analyst | excel, sql | Jakarta\n(posisi | keahlian | lokasi)");

            return;
        }
        $g->update(['is_active' => true, 'fail_count' => 0, 'last_slot' => null]);

        $this->zonePicker($g, "✅ Langganan aktif!\n\nAku kirim info lowongan untuk \"".implode(', ', array_slice($g->positions, 0, 3))."\" setiap pukul 06.00 dan 18.00 waktu setempat.\n\nZona waktumu sekarang {$g->zoneLabel()}. Kalau lokasimu beda, pilih zona yang sesuai di bawah ini.\n\nUbah topik: /topik • Contoh sekarang: /contoh • Jeda: /berhenti • Lihat pengaturan: /jadwal");
    }

    /** Bot masuk atau keluar dari grup. */
    public function membership(array $m): void
    {
        $chat = data_get($m, 'chat');
        if (! in_array($chat['type'] ?? '', ['group', 'supergroup'], true)) {
            return;
        }
        $id = (string) $chat['id'];
        $status = (string) data_get($m, 'new_chat_member.status');

        if (in_array($status, ['member', 'administrator'], true)) {
            $g = TelegramGroup::firstOrNew(['chat_id' => $id]);
            $isNew = ! $g->exists || ! $g->is_active;
            $g->fill(['title' => mb_substr((string) ($chat['title'] ?? ''), 0, 120), 'is_active' => true, 'fail_count' => 0])->save();
            if ($isNew) {
                $this->welcome($g);
            }
        } elseif (in_array($status, ['left', 'kicked'], true)) {
            TelegramGroup::where('chat_id', $id)->update(['is_active' => false]);
        }
    }

    /** Pesan di grup: hanya perintah yang ditanggapi. */
    public function message(array $msg): void
    {
        $text = trim((string) ($msg['text'] ?? ''));
        $chat = (string) data_get($msg, 'chat.id');
        if ($chat === '' || ! preg_match('/^\/(\w+)(?:@\w+)?(?:\s+(.*))?$/su', $text, $m)) {
            return;
        }
        $cmd = mb_strtolower($m[1]);
        $arg = trim($m[2] ?? '');
        if (! in_array($cmd, ['start', 'help', 'zona', 'topik', 'jenis', 'jadwal', 'contoh', 'berhenti', 'mulai'], true)) {
            return;
        }

        $g = TelegramGroup::firstOrCreate(['chat_id' => $chat], ['title' => mb_substr((string) data_get($msg, 'chat.title', ''), 0, 120)]);

        if (in_array($cmd, ['start', 'help'], true)) {
            $this->welcome($g);

            return;
        }
        if ($cmd === 'jadwal') {
            $this->say($chat, $this->summary($g));

            return;
        }
        if (! $this->isAdmin($chat, $msg)) {
            $this->say($chat, 'Pengaturan bot hanya bisa diubah oleh admin grup.');

            return;
        }

        match ($cmd) {
            'zona' => $this->setZone($g, mb_strtolower($arg)),
            'topik' => $this->setTopic($g, $arg),
            'jenis' => $this->setKind($g, mb_strtolower($arg)),
            'berhenti' => $this->toggle($g, false),
            'mulai' => $this->toggle($g, true),
            'contoh' => $this->preview($g),
        };
    }

    /** Tombol pilih zona waktu. */
    public function callback(array $cb): void
    {
        $chat = (string) data_get($cb, 'message.chat.id');
        $data = (string) ($cb['data'] ?? '');
        if ($chat === '' || ! preg_match('/^zona:(wib|wita|wit)$/', $data, $m)) {
            return;
        }

        if (! $this->isAdminUser($chat, (string) data_get($cb, 'from.id'))) {
            $this->notifier->api('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Hanya admin grup yang bisa mengubah ini.', 'show_alert' => true]);

            return;
        }

        $g = TelegramGroup::firstOrCreate(['chat_id' => $chat]);
        $this->notifier->api('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'Zona waktu: '.strtoupper($m[1])]);
        $this->setZone($g, $m[1]);
    }

    // ---------------------------------------------------------------- pengaturan

    private function welcome(TelegramGroup $g): void
    {
        $text = implode("\n", [
            '👋 Halo! Aku bot OwlSpire.ID.',
            '',
            'Di grup ini aku akan membagikan info lowongan setiap pukul 06.00 dan 18.00 sesuai zona waktu grup.',
            '',
            'Pilih zona waktu grup lewat tombol di bawah (khusus admin). Sekarang: '.$g->zoneLabel().'.',
            '',
            'Admin juga bisa mengatur:',
            '/topik data analyst, admin | excel | Jakarta — posisi | keahlian | lokasi',
            '/jenis magang — atau /jenis kerja',
            '/jadwal — lihat pengaturan • /contoh — kirim contoh sekarang',
            '/berhenti — jeda kiriman • /mulai — lanjutkan',
        ]);

        $this->zonePicker($g, $text);
    }

    private function zonePicker(TelegramGroup $g, string $text): void
    {
        $this->notifier->api('sendMessage', [
            'chat_id' => $g->chat_id, 'text' => $text, 'disable_web_page_preview' => true,
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => 'WIB', 'callback_data' => 'zona:wib'],
                ['text' => 'WITA', 'callback_data' => 'zona:wita'],
                ['text' => 'WIT', 'callback_data' => 'zona:wit'],
            ]]],
        ]);
    }

    private function setZone(TelegramGroup $g, string $zone): void
    {
        if (! isset(TelegramGroup::ZONES[$zone])) {
            $this->say($g->chat_id, "Pilih salah satu: /zona wib, /zona wita, atau /zona wit.\nWIB = Sumatra, Jawa, Kalimantan Barat/Tengah. WITA = Bali, NTB, NTT, Sulawesi, Kalimantan Timur/Selatan/Utara. WIT = Maluku dan Papua.");

            return;
        }
        $g->update(['zone' => $zone, 'last_slot' => null]);
        $this->say($g->chat_id, '✅ Zona waktu '.$g->who().': '.strtoupper($zone).' ('.TelegramGroup::OFFSETS[$zone].'). Info lowongan dikirim pukul 06.00 dan 18.00 '.strtoupper($zone).'.');
    }

    /** /topik posisi | keahlian | lokasi. Tanpa isi = kembali ke topik bawaan. */
    private function setTopic(TelegramGroup $g, string $arg): void
    {
        if ($arg === '' && $g->isPrivate()) {
            $this->say($g->chat_id, "Tulis topikmu: /topik data analyst, admin | excel, sql | Jakarta\n(posisi | keahlian | lokasi)");

            return;
        }
        if ($arg === '') {
            $g->update(['positions' => null, 'skills' => null, 'locations' => null]);
            $this->say($g->chat_id, 'Topik dikembalikan ke bawaan: '.implode(', ', (array) config('platform.telegram.group_positions')).'. Untuk mengatur: /topik data analyst | excel, sql | Jakarta');

            return;
        }
        $p = array_map('trim', explode('|', $arg));
        $positions = $this->clean($p[0] ?? '', 5);
        $skills = $this->clean($p[1] ?? '', 10);
        if (! $positions && ! $skills) {
            $this->say($g->chat_id, "Isi minimal satu posisi atau keahlian. Contoh:\n/topik data analyst | excel, sql | Jakarta");

            return;
        }
        $g->update(['positions' => $positions ?: array_slice($skills, 0, 3), 'skills' => $skills, 'locations' => $this->clean($p[2] ?? '', 3), 'sent_urls' => null]);
        $this->say($g->chat_id, '✅ Topik '.$g->who()." diperbarui.\n\n".$this->summary($g->fresh()).($g->isPrivate() && ! $g->is_active ? "\n\nKetik /langganan untuk mulai menerima kiriman." : ''));
    }

    private function setKind(TelegramGroup $g, string $kind): void
    {
        if (! in_array($kind, ['kerja', 'magang'], true)) {
            $this->say($g->chat_id, 'Pilih: /jenis kerja atau /jenis magang.');

            return;
        }
        $g->update(['kind' => $kind, 'sent_urls' => null]);
        $this->say($g->chat_id, '✅ Jenis kiriman: '.$kind.'.');
    }

    private function toggle(TelegramGroup $g, bool $on): void
    {
        $g->update(['is_active' => $on, 'fail_count' => 0]);
        $this->say($g->chat_id, $on ? '▶️ Kiriman dilanjutkan.' : '⏸️ Kiriman dijeda. Ketik /mulai untuk melanjutkan.');
    }

    private function preview(TelegramGroup $g): void
    {
        if ($g->isPrivate() && ! $g->positions) {
            $this->say($g->chat_id, "Atur topikmu dulu: /langganan data analyst | excel, sql | Jakarta");

            return;
        }
        if (! Cache::add("tgbot:preview:{$g->chat_id}", 1, 600)) {
            $this->say($g->chat_id, 'Contoh baru bisa dikirim lagi setelah 10 menit.');

            return;
        }
        $this->say($g->chat_id, '🔎 Menyiapkan contoh info lowongan. Tunggu 1–2 menit ya.');
        SendGroupDigest::dispatchAfterResponse($g->id);
    }

    private function summary(TelegramGroup $g): string
    {
        $positions = $g->positions ?: ($g->isPrivate() ? [] : (array) config('platform.telegram.group_positions'));

        return implode("\n", [
            '📋 Pengaturan '.($g->isPrivate() ? 'kirimanmu' : 'grup'),
            'Status: '.($g->is_active ? 'aktif' : ($g->isPrivate() ? 'belum berlangganan (ketik /langganan)' : 'dijeda')),
            'Zona waktu: '.$g->zoneLabel().' ('.$g->offsetLabel().')',
            'Jadwal kiriman: 06.00 dan 18.00 '.$g->zoneLabel(),
            'Jenis: '.($g->kind ?: 'kerja'),
            'Posisi: '.($positions ? implode(', ', $positions).($g->positions ? '' : ' (bawaan)') : '-'),
            'Keahlian: '.($g->skills ? implode(', ', $g->skills) : '-'),
            'Lokasi: '.($g->locations ? implode(', ', $g->locations) : 'seluruh Indonesia'),
        ]);
    }

    // ---------------------------------------------------------------- bantuan

    /** Admin atau pemilik grup. Admin anonim (mengirim atas nama grup) juga dihitung. */
    private function isAdmin(string $chat, array $msg): bool
    {
        if ((string) data_get($msg, 'sender_chat.id') === $chat) {
            return true;
        }

        return $this->isAdminUser($chat, (string) data_get($msg, 'from.id'));
    }

    private function isAdminUser(string $chat, string $user): bool
    {
        if ((int) $chat > 0) {
            return true; // chat pribadi: orang itu mengatur kirimannya sendiri
        }

        $res = $this->notifier->api('getChatMember', ['chat_id' => $chat, 'user_id' => $user]);

        return in_array(data_get($res, 'result.status'), ['creator', 'administrator'], true);
    }

    private function clean(string $text, int $limit): array
    {
        $out = [];
        foreach (preg_split('/[,;\n]/u', $text) ?: [] as $x) {
            $x = mb_substr(trim($x), 0, 60);
            if ($x !== '' && $x !== '-' && ! in_array(mb_strtolower($x), array_map('mb_strtolower', $out), true)) {
                $out[] = $x;
            }
        }

        return array_slice($out, 0, $limit);
    }

    private function say(string $chat, string $text): void
    {
        $this->notifier->telegram($chat, $text);
    }
}
