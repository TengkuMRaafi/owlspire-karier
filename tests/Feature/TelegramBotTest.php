<?php

namespace Tests\Feature;

use App\Models\TelegramLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.webhook_secret' => 'rahasia123', 'services.telegram.token' => 'tok']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Cache::flush();
    }

    private function kirim(string $text, int $chat = 555): void
    {
        $this->postJson('/api/telegram/webhook', ['message' => ['text' => $text, 'chat' => ['id' => $chat, 'type' => 'private']]], ['X-Telegram-Bot-Api-Secret-Token' => 'rahasia123'])->assertOk();
    }

    private function terkirim(string $needle): bool
    {
        return Http::recorded(fn ($req) => str_contains((string) $req['text'], $needle))->isNotEmpty();
    }

    public function test_start_tanpa_kode_menampilkan_bantuan_owlspire(): void
    {
        $this->kirim('/start');
        $this->assertTrue($this->terkirim('bot OwlSpire.ID'));
    }

    public function test_cari_dipandu_bertanya_tiga_langkah(): void
    {
        $this->kirim('/cari');
        $this->assertTrue($this->terkirim('1/3'));
        $this->kirim('Data Analyst');
        $this->assertTrue($this->terkirim('2/3'));
        $this->kirim('excel, sql');
        $this->assertTrue($this->terkirim('3/3'));
    }

    public function test_batal_menghapus_pengisian(): void
    {
        $this->kirim('/cari');
        $this->kirim('/batal');
        $this->assertTrue($this->terkirim('Dibatalkan'));
        $this->kirim('Data Analyst');
        $this->assertTrue($this->terkirim('Ketik /cari'));
    }

    public function test_skill_tanpa_isi_diminta_menulis_keahlian(): void
    {
        $this->kirim('/skill');
        $this->assertTrue($this->terkirim('Tulis keahlianmu'));
    }

    public function test_akun_menunjukkan_status_tautan(): void
    {
        $this->kirim('/akun');
        $this->assertTrue($this->terkirim('belum tertaut'));

        $u = User::factory()->create();
        TelegramLink::create(['user_id' => $u->id, 'chat_id' => '555']);
        $this->kirim('/akun');
        $this->assertTrue($this->terkirim('sudah tertaut'));
    }

    public function test_tombol_localhost_dibuang_agar_pesan_tetap_terkirim(): void
    {
        $this->kirim('/web');
        $this->assertTrue(Http::recorded(fn ($req) => ! isset($req['reply_markup']))->isNotEmpty());
    }
}
