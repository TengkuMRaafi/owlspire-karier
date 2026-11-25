<?php

namespace App\Services\Telegram;

use App\Jobs\RunTelegramSearch;
use App\Models\TelegramLink;
use App\Services\Jobs\JobFinder;
use App\Services\Notify\Notifier;
use Illuminate\Support\Facades\Cache;

/**
 * Bot Telegram OwlSpire.ID (versi baru, menggantikan bot Python "Job Finder Indonesia").
 * Perintah: /start, /cari, /magang, /skill, /akun, /web, /batal, /help.
 * Satu pemanggilan webhook = satu pesan masuk; pencarian lowongan berjalan setelah respons terkirim.
 */
class OwlspireBot
{
    public const STATE_TTL = 600;   // pengisian /cari kedaluwarsa setelah 10 menit diam
    public const COOLDOWN = 60;     // jeda antar pencarian per chat (detik)

    private const PROMPTS = [
        'positions' => "1/3 Posisi apa yang dicari? Pisahkan dengan koma.\nContoh: Data Analyst, Staf Administrasi\n(ketik - kalau mau dilewati)",
        'skills' => "2/3 Keahlian kamu? Pisahkan dengan koma.\nContoh: excel, sql, power bi\n(ketik - kalau mau dilewati)",
        'locations' => "3/3 Lokasi? Maksimal 3, pisahkan dengan koma.\nContoh: Pekanbaru, Jakarta, Remote\n(ketik - untuk seluruh Indonesia)",
    ];

    public function __construct(private Notifier $notifier, private JobFinder $finder, private GroupBot $groups)
    {
    }

    /** Daftar perintah untuk setMyCommands (dipakai php artisan telegram:setup). */
    public static function commands(): array
    {
        return [
            ['command' => 'langganan', 'description' => 'Terima info lowongan otomatis jam 06.00 dan 18.00'],
            ['command' => 'zona', 'description' => 'Atur zona waktu (WIB, WITA, WIT)'],
            ['command' => 'topik', 'description' => 'Atur topik: posisi | keahlian | lokasi'],
            ['command' => 'cari', 'description' => 'Cari lowongan kerja'],
            ['command' => 'magang', 'description' => 'Cari lowongan magang'],
            ['command' => 'skill', 'description' => 'Cari cepat dari keahlian'],
            ['command' => 'jadwal', 'description' => 'Lihat pengaturan kirimanmu'],
            ['command' => 'berhenti', 'description' => 'Hentikan kiriman otomatis'],
            ['command' => 'akun', 'description' => 'Cek status akun yang tertaut'],
            ['command' => 'web', 'description' => 'Buka situs OwlSpire.ID'],
            ['command' => 'batal', 'description' => 'Batalkan pengisian pencarian'],
            ['command' => 'help', 'description' => 'Bantuan dan daftar perintah'],
        ];
    }

    public function handle(array $update): void
    {
        if (isset($update['my_chat_member'])) {
            $this->groups->membership($update['my_chat_member']);

            return;
        }
        if (isset($update['callback_query'])) {
            $this->groups->callback($update['callback_query']);

            return;
        }
        if (in_array(data_get($update, 'message.chat.type'), ['group', 'supergroup'], true)) {
            $this->groups->message((array) $update['message']);

            return;
        }

        $chat = data_get($update, 'message.chat.id');
        $text = trim((string) data_get($update, 'message.text', ''));
        if ($chat === null || $text === '' || data_get($update, 'message.chat.type', 'private') !== 'private') {
            return;
        }
        $chat = (string) $chat;

        if (! preg_match('/^\/(\w+)(?:@\w+)?(?:\s+(.*))?$/su', $text, $m)) {
            $state = Cache::get($this->stateKey($chat));
            $state ? $this->continueGuided($chat, $text, $state) : $this->say($chat, "Ketik /cari untuk mencari lowongan, atau /help untuk daftar perintah.");

            return;
        }

        $cmd = mb_strtolower($m[1]);
        $arg = trim($m[2] ?? '');
        $name = (string) data_get($update, 'message.from.first_name', '');

        match ($cmd) {
            'langganan', 'harian', 'mulai' => $this->subscribe($chat, $arg, $name),
            'zona', 'topik', 'jenis', 'jadwal', 'contoh', 'berhenti' => $this->groups->privateCommand($chat, $cmd, $arg, $name),
            'start' => $this->start($chat, $arg),
            'cari', 'input' => $this->searchCommand($chat, $arg, 'kerja'),
            'magang' => $this->searchCommand($chat, $arg, 'magang'),
            'skill', 'keahlian' => $this->skillCommand($chat, $arg),
            'akun' => $this->account($chat),
            'web' => $this->say($chat, 'Buka OwlSpire.ID di HP atau laptop.', $this->webButtons()),
            'batal' => $this->cancel($chat),
            default => $this->help($chat),
        };
    }

    // ---------------------------------------------------------------- perintah

    private function start(string $chat, string $arg): void
    {
        if ($arg === '') {
            $this->help($chat);

            return;
        }
        if (! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $arg)) {
            $this->help($chat);

            return;
        }

        $link = TelegramLink::where('link_code', $arg)->first();
        if (! $link) {
            $this->say($chat, 'Kode tidak dikenali atau sudah dipakai. Buat kode baru dari halaman Rencana belajar di OwlSpire.ID.');

            return;
        }

        // Satu chat hanya untuk satu akun.
        TelegramLink::where('chat_id', $chat)->where('id', '!=', $link->id)->update(['chat_id' => null, 'linked_at' => null]);
        $link->update(['chat_id' => $chat, 'link_code' => null, 'linked_at' => now()]);
        $this->say($chat, "✅ Telegram terhubung ke akun OwlSpire.ID kamu.\n\nPengingat belajar dan alert lowongan akan dikirim ke sini. Atur di halaman Rencana belajar.\n\nMau cari lowongan sekarang? Ketik /cari.");
    }

    private function help(string $chat): void
    {
        $this->say($chat, implode("\n", [
            '👋 Halo! Aku bot OwlSpire.ID, teman belajar dan cari kerjamu.',
            '',
            'Cara mencari lowongan:',
            '• /cari — aku tanya posisi, keahlian, dan lokasi satu per satu',
            '• /cari data analyst | excel, sql | Jakarta — satu baris (posisi | keahlian | lokasi)',
            '• /magang — sama seperti /cari, khusus magang',
            '• /langganan — terima info lowongan otomatis pukul 06.00 dan 18.00 sesuai zonamu (WIB/WITA/WIT) dan topikmu',
            '• /skill python, flask | Bandung — cari cepat dari keahlian',
            '',
            'Lainnya:',
            '• /zona, /topik, /jenis, /jadwal, /contoh, /berhenti — atur kiriman otomatismu',
            '• /akun — cek apakah Telegram sudah tertaut ke akunmu',
            '• /web — buka OwlSpire.ID (simulasi CPNS, BUMN, Polri/TNI, belajar, arah karier, analisis CV)',
            '• /batal — batalkan pengisian yang sedang berjalan',
            '',
            'Hasil diambil dari beberapa situs lowongan (7 hari terakhir) dan diurutkan menurut persen kecocokan keahlian. Tiap hasil menyertakan link aslinya.',
            '',
            'Mau pengingat belajar dan alert lowongan harian? Tautkan akunmu dari halaman Rencana belajar di situs.',
        ]), $this->webButtons());
    }

    private function account(string $chat): void
    {
        $linked = TelegramLink::where('chat_id', $chat)->exists();
        $this->say($chat, $linked
            ? '✅ Telegram ini sudah tertaut ke akun OwlSpire.ID. Atur pengingat dan alert di halaman Rencana belajar dan Lamaran.'
            : 'Telegram ini belum tertaut ke akun. Buka halaman Rencana belajar di OwlSpire.ID, tekan Hubungkan Telegram, lalu tekan Start di sini.', $this->webButtons());
    }

    /** /langganan posisi | keahlian | lokasi, atau tanpa isi: pakai topik tersimpan, atau tanya satu per satu. */
    private function subscribe(string $chat, string $arg, string $name): void
    {
        if ($arg !== '') {
            $p = array_map('trim', explode('|', $arg));
            $this->groups->saveSubscription($chat, $this->clean($p[0] ?? '', 5), $this->clean($p[1] ?? '', 10), $this->clean($p[2] ?? '', 3), $name);

            return;
        }

        $g = $this->groups->subscriber($chat, $name);
        if ($g->positions) {
            $this->groups->activate($g);

            return;
        }

        Cache::put($this->stateKey($chat), ['step' => 'positions', 'kind' => 'langganan', 'data' => []], self::STATE_TTL);
        $this->say($chat, "Langganan info lowongan harian: aku kirim otomatis pukul 06.00 dan 18.00 sesuai zona waktumu. Ketik /batal kapan pun untuk berhenti.\n\n".self::PROMPTS['positions']);
    }

    private function cancel(string $chat): void
    {
        $had = Cache::pull($this->stateKey($chat)) !== null;
        $this->say($chat, $had ? 'Dibatalkan. Ketik /cari kapan pun untuk mulai lagi.' : 'Tidak ada pengisian yang sedang berjalan. Ketik /cari untuk mulai.');
    }

    // ---------------------------------------------------------------- pencarian

    /** /cari tanpa argumen = dipandu; /cari posisi | keahlian | lokasi = satu baris. */
    private function searchCommand(string $chat, string $arg, string $kind): void
    {
        if ($arg === '') {
            Cache::put($this->stateKey($chat), ['step' => 'positions', 'kind' => $kind, 'data' => []], self::STATE_TTL);
            $this->say($chat, ($kind === 'magang' ? "Cari magang. " : "Cari lowongan. ")."Ketik /batal kapan pun untuk berhenti.\n\n".self::PROMPTS['positions']);

            return;
        }

        $p = array_map('trim', explode('|', $arg));
        $this->search($chat, $kind, $this->clean($p[0] ?? '', 5), $this->clean($p[1] ?? '', 10), $this->clean($p[2] ?? '', 3));
    }

    /** /skill python, flask | Jakarta: posisi diambil dari tiga keahlian pertama. */
    private function skillCommand(string $chat, string $arg): void
    {
        $p = array_map('trim', explode('|', $arg));
        $skills = $this->clean($p[0] ?? '', 10);
        if (! $skills) {
            $this->say($chat, "Tulis keahlianmu, dipisah koma. Contoh:\n/skill python, flask, docker | Jakarta");

            return;
        }
        $this->search($chat, 'kerja', array_slice($skills, 0, 3), $skills, $this->clean($p[1] ?? '', 3));
    }

    private function continueGuided(string $chat, string $text, array $state): void
    {
        $step = $state['step'];
        $limits = ['positions' => 5, 'skills' => 10, 'locations' => 3];
        $state['data'][$step] = $text === '-' ? [] : $this->clean($text, $limits[$step]);

        $next = ['positions' => 'skills', 'skills' => 'locations', 'locations' => null][$step] ?? null;
        if ($next) {
            $state['step'] = $next;
            Cache::put($this->stateKey($chat), $state, self::STATE_TTL);
            $this->say($chat, self::PROMPTS[$next]);

            return;
        }

        Cache::forget($this->stateKey($chat));
        $d = $state['data'];
        if (($state['kind'] ?? '') === 'langganan') {
            $this->groups->saveSubscription($chat, $d['positions'] ?? [], $d['skills'] ?? [], $d['locations'] ?? []);

            return;
        }
        $this->search($chat, $state['kind'], $d['positions'] ?? [], $d['skills'] ?? [], $d['locations'] ?? []);
    }

    private function search(string $chat, string $kind, array $positions, array $skills, array $locations): void
    {
        if (! $positions && ! $skills) {
            $this->say($chat, "Isi minimal satu posisi atau keahlian. Contoh:\n/cari data analyst | excel, sql | Jakarta");

            return;
        }
        $positions = $positions ?: array_slice($skills, 0, 3);
        $locations = $locations ?: ['Indonesia'];

        $sources = $this->sources();
        if (! $sources) {
            $this->say($chat, 'Sumber lowongan belum aktif di server ini. Coba lewat situs: ketik /web.', $this->webButtons());

            return;
        }

        if (! Cache::add("tgbot:cool:{$chat}", 1, self::COOLDOWN)) {
            $this->say($chat, 'Tunggu sebentar ya, pencarian sebelumnya baru selesai. Coba lagi dalam satu menit.');

            return;
        }

        // Situs ini publik: batasi pencarian yang berjalan bersamaan (hitungan sama dengan halaman Lowongan).
        Cache::add('jobsearch:running', 0, 900);
        if (Cache::increment('jobsearch:running') > (int) config('platform.jobs.max_running', 3)) {
            Cache::decrement('jobsearch:running');
            Cache::forget("tgbot:cool:{$chat}");
            $this->say($chat, 'Server sedang sibuk. Coba lagi sebentar lagi.');

            return;
        }

        $params = [
            'positions' => $positions, 'skills' => $skills ?: $positions, 'locations' => $locations,
            'hours' => 168, 'kind' => $kind, 'sources' => $sources,
        ];
        $this->say($chat, '🔎 Mencari '.($kind === 'magang' ? 'magang' : 'lowongan').' "'.implode(', ', array_slice($positions, 0, 3)).'" di '.implode(', ', $locations).". Tunggu 1–2 menit ya.");

        RunTelegramSearch::dispatchAfterResponse($chat, $params);
    }

    /** Sumber bawaan yang aktif; cadangannya LinkedIn atau sumber pertama yang tersedia. */
    private function sources(): array
    {
        $default = array_values(array_map(fn ($s) => $s['id'], array_filter($this->finder->catalog(), fn ($s) => $s['default'] && $s['available'])));
        if ($default) {
            return $default;
        }
        $available = $this->finder->availableKeys();

        return array_values(array_intersect(['linkedin'], $available)) ?: array_slice($available, 0, 1);
    }

    // ---------------------------------------------------------------- bantuan

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

    private function stateKey(string $chat): string
    {
        return "tgbot:state:{$chat}";
    }

    private function say(string $chat, string $text, array $buttons = []): void
    {
        $this->notifier->telegram($chat, $text, $buttons);
    }

    /** Tombol ke situs (dan kanal bila diisi). Notifier membuang tombol yang URL-nya tidak valid, mis. localhost. */
    private function webButtons(): array
    {
        $buttons = [['🌐 Buka OwlSpire.ID', route('home')]];
        $channel = trim((string) config('platform.telegram.channel'));
        if ($channel !== '') {
            $buttons[] = ['📣 Kanal OwlSpire.ID', $channel];
        }

        return $buttons;
    }
}
