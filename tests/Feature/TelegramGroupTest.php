<?php

namespace Tests\Feature;

use App\Models\TelegramGroup;
use App\Services\Telegram\GroupDigest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.webhook_secret' => 'rahasia123', 'services.telegram.token' => 'tok']);
        Cache::flush();
    }

    private function fakeTelegram(string $role = 'administrator'): void
    {
        Http::fake([
            'api.telegram.org/*getChatMember' => Http::response(['ok' => true, 'result' => ['status' => $role]]),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    private function kirim(array $payload): void
    {
        $this->postJson('/api/telegram/webhook', $payload, ['X-Telegram-Bot-Api-Secret-Token' => 'rahasia123'])->assertOk();
    }

    private function perintah(string $text): void
    {
        $this->kirim(['message' => ['text' => $text, 'chat' => ['id' => -1001, 'type' => 'supergroup', 'title' => 'Info Loker'], 'from' => ['id' => 7]]]);
    }

    public function test_bot_masuk_grup_terdaftar_dan_menyapa(): void
    {
        $this->fakeTelegram();
        $this->kirim(['my_chat_member' => ['chat' => ['id' => -1001, 'type' => 'supergroup', 'title' => 'Info Loker'], 'new_chat_member' => ['status' => 'member']]]);

        $g = TelegramGroup::where('chat_id', '-1001')->firstOrFail();
        $this->assertSame('wib', $g->zone);
        $this->assertTrue($g->is_active);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendMessage') && str_contains((string) $r['text'], '06.00 dan 18.00'));
    }

    public function test_bot_dikeluarkan_menonaktifkan_grup(): void
    {
        $this->fakeTelegram();
        TelegramGroup::create(['chat_id' => '-1001']);
        $this->kirim(['my_chat_member' => ['chat' => ['id' => -1001, 'type' => 'supergroup'], 'new_chat_member' => ['status' => 'kicked']]]);
        $this->assertFalse(TelegramGroup::where('chat_id', '-1001')->firstOrFail()->is_active);
    }

    public function test_admin_mengatur_zona_waktu(): void
    {
        $this->fakeTelegram('administrator');
        $this->perintah('/zona wit');
        $this->assertSame('wit', TelegramGroup::where('chat_id', '-1001')->value('zone'));
    }

    public function test_bukan_admin_tidak_bisa_mengubah_pengaturan(): void
    {
        $this->fakeTelegram('member');
        $this->perintah('/zona wita');
        $this->assertSame('wib', TelegramGroup::where('chat_id', '-1001')->value('zone'));
    }

    public function test_topik_menyimpan_posisi_keahlian_dan_lokasi(): void
    {
        $this->fakeTelegram();
        $this->perintah('/topik data analyst, admin | excel, sql | Jakarta');
        $g = TelegramGroup::where('chat_id', '-1001')->firstOrFail();
        $this->assertSame(['data analyst', 'admin'], $g->positions);
        $this->assertSame(['excel', 'sql'], $g->skills);
        $this->assertSame(['Jakarta'], $g->locations);
    }

    public function test_digest_hanya_terkirim_pukul_enam_dan_delapan_belas_menurut_zona(): void
    {
        $wib = TelegramGroup::create(['chat_id' => '-1', 'zone' => 'wib']);
        $wit = TelegramGroup::create(['chat_id' => '-2', 'zone' => 'wit']);

        $this->mock(GroupDigest::class, function ($m) use ($wib) {
            $m->shouldReceive('send')->once()->withArgs(fn ($g) => $g->is($wib))->andReturn(true);
        });

        // 06.00 WIB = 08.00 WIT: hanya grup WIB yang dikirimi.
        $this->travelTo(Carbon::parse('2026-11-20 06:00:00', 'Asia/Jakarta'));
        $this->artisan('telegram:digest')->assertSuccessful();
        $this->assertNotNull($wib->fresh()->last_slot);
        $this->assertNull($wit->fresh()->last_slot);

        // Jalan ulang pada jam yang sama tidak mengirim dobel (mock hanya mengizinkan satu panggilan).
        $this->artisan('telegram:digest')->assertSuccessful();
    }
}
