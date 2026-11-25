<?php

namespace Tests\Feature;

use App\Models\TelegramGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramSubscriberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.webhook_secret' => 'rahasia123', 'services.telegram.token' => 'tok']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Cache::flush();
    }

    private function kirim(string $text): void
    {
        $this->postJson('/api/telegram/webhook', ['message' => ['text' => $text, 'chat' => ['id' => 555, 'type' => 'private'], 'from' => ['id' => 555, 'first_name' => 'Rina']]], ['X-Telegram-Bot-Api-Secret-Token' => 'rahasia123'])->assertOk();
    }

    public function test_langganan_satu_baris_mengaktifkan_kiriman_pribadi(): void
    {
        $this->kirim('/langganan data analyst | excel, sql | Jakarta');

        $s = TelegramGroup::where('chat_id', '555')->firstOrFail();
        $this->assertTrue($s->is_active);
        $this->assertTrue($s->isPrivate());
        $this->assertSame(['data analyst'], $s->positions);
        $this->assertSame('wib', $s->zoneKey());
        Http::assertSent(fn ($r) => str_contains((string) $r['text'], 'Langganan aktif') && isset($r['reply_markup']));
    }

    public function test_langganan_dipandu_tiga_langkah_lalu_aktif(): void
    {
        $this->kirim('/langganan');
        $this->kirim('Staf Administrasi');
        $this->kirim('excel');
        $this->kirim('Pekanbaru');

        $s = TelegramGroup::where('chat_id', '555')->firstOrFail();
        $this->assertTrue($s->is_active);
        $this->assertSame(['Staf Administrasi'], $s->positions);
        $this->assertSame(['Pekanbaru'], $s->locations);
    }

    public function test_zona_dan_berhenti_tanpa_perlu_admin(): void
    {
        $this->kirim('/langganan admin | | Makassar');
        $this->kirim('/zona wita');
        $this->assertSame('wita', TelegramGroup::where('chat_id', '555')->value('zone'));

        $this->kirim('/berhenti');
        $this->assertFalse(TelegramGroup::where('chat_id', '555')->firstOrFail()->is_active);
    }

    public function test_belum_berlangganan_tidak_dikirimi_dan_contoh_minta_topik(): void
    {
        $this->kirim('/contoh');
        $this->assertFalse(TelegramGroup::where('chat_id', '555')->firstOrFail()->is_active);
        Http::assertSent(fn ($r) => str_contains((string) $r['text'], 'Atur topikmu dulu'));
    }
}
