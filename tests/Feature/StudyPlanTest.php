<?php

namespace Tests\Feature;

use App\Models\DailyActivity;
use App\Models\ExamType;
use App\Models\StudyProfile;
use App\Models\TelegramLink;
use App\Models\User;
use App\Services\Study\StudyActivity;
use App\Services\Study\StudyPlanner;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudyPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.premium_only.plan' => false]); // kunci Premium diuji terpisah di PremiumGateTest
    }

    private function day(User $u, int $daysAgo, int $reviews = 1): void
    {
        DailyActivity::create(['user_id' => $u->id, 'day' => StudyActivity::today()->subDays($daysAgo)->toDateString(), 'reviews' => $reviews, 'exams' => 0]);
    }

    public function test_streak_menghitung_hari_berurutan_dan_tetap_hidup_bila_hari_ini_belum_belajar(): void
    {
        $u = User::factory()->create();
        foreach ([1, 2, 3, 5] as $d) {
            $this->day($u, $d);
        }

        $s = app(StudyActivity::class)->streak($u->id);
        $this->assertSame(3, $s['current']);     // kemarin, 2, 3 hari lalu
        $this->assertFalse($s['active_today']);
        $this->assertSame(3, $s['best']);

        $this->day($u, 0);
        $this->assertSame(4, app(StudyActivity::class)->streak($u->id)['current']);
    }

    public function test_streak_putus_bila_kemarin_terlewat(): void
    {
        $u = User::factory()->create();
        $this->day($u, 2);
        $this->assertSame(0, app(StudyActivity::class)->streak($u->id)['current']);
    }

    public function test_record_menambah_hitungan_hari_ini(): void
    {
        $u = User::factory()->create();
        app(StudyActivity::class)->record($u->id, 'review');
        app(StudyActivity::class)->record($u->id, 'review');
        app(StudyActivity::class)->record($u->id, 'exam');

        $this->assertSame(['reviews' => 2, 'exams' => 1], app(StudyActivity::class)->todayCounts($u->id));
    }

    public function test_planner_menyusun_tugas_sesuai_fase(): void
    {
        $this->seed(ExamSeeder::class);
        $u = User::factory()->create();
        $type = ExamType::firstOrFail();

        $p = StudyProfile::create(['user_id' => $u->id, 'exam_type_id' => $type->id, 'exam_date' => now()->addDays(40)->toDateString(), 'daily_goal' => 5]);
        $plan = app(StudyPlanner::class)->build($p->fresh());
        $this->assertSame('Fondasi', $plan['phase']);
        $this->assertSame('baseline', $plan['today'][0]['key']); // belum pernah simulasi
        $this->assertCount(7, $plan['week']);

        $p->update(['exam_date' => now()->toDateString()]);
        $plan = app(StudyPlanner::class)->build($p->fresh());
        $this->assertSame('Hari ujian', $plan['phase']);
        $this->assertSame('exam', $plan['today'][0]['key']);
    }

    public function test_halaman_rencana_dan_simpan_target(): void
    {
        $this->seed(ExamSeeder::class);
        $u = User::factory()->create();
        $type = ExamType::firstOrFail();

        $this->actingAs($u)->get('/dashboard/rencana')->assertOk()->assertSee('Rencana belajar');

        $date = now()->addDays(20)->toDateString();
        $this->actingAs($u)->put('/dashboard/rencana', ['daily_goal' => 10, 'exam_type_id' => $type->id, 'exam_date' => $date])->assertRedirect(route('account.plan'));
        $p = StudyProfile::where('user_id', $u->id)->firstOrFail();
        $this->assertSame(10, (int) $p->daily_goal);
        $this->assertSame($date, $p->exam_date->toDateString());

        $this->actingAs($u)->put('/dashboard/rencana', ['daily_goal' => 7])->assertSessionHasErrors('daily_goal');
        $this->actingAs($u)->put('/dashboard/rencana', ['daily_goal' => 5, 'exam_date' => now()->subDay()->toDateString()])->assertSessionHasErrors('exam_date');
    }

    public function test_pengingat_telegram_butuh_tautan_dulu(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put('/dashboard/rencana/pengingat', ['remind_channel' => 'telegram', 'remind_time' => '19:00'])->assertSessionHasErrors('remind_channel');
        $this->actingAs($u)->put('/dashboard/rencana/pengingat', ['remind_channel' => 'email', 'remind_time' => '19:00'])->assertRedirect();
        $this->assertSame('email', StudyProfile::where('user_id', $u->id)->value('remind_channel'));
    }

    public function test_perintah_pengingat_mengirim_sekali_dan_melewati_yang_sudah_belajar(): void
    {
        Mail::fake();
        $this->travelTo(now(config('platform.study.timezone'))->setTime(19, 30));

        $lazy = User::factory()->create();
        $busy = User::factory()->create();
        foreach ([$lazy, $busy] as $u) {
            StudyProfile::create(['user_id' => $u->id, 'remind_channel' => 'email', 'remind_time' => '19:00']);
        }
        app(StudyActivity::class)->record($busy->id, 'review');

        $this->artisan('study:remind')->assertSuccessful();
        $this->artisan('study:remind')->assertSuccessful(); // jalan kedua tidak mengirim lagi

        $this->assertSame(today(config('platform.study.timezone'))->toDateString(), StudyProfile::where('user_id', $lazy->id)->value('last_reminded_on')->toDateString());
        $this->assertSame(today(config('platform.study.timezone'))->toDateString(), StudyProfile::where('user_id', $busy->id)->value('last_reminded_on')->toDateString()); // yang sudah belajar ditandai tanpa dikirimi
    }

    public function test_webhook_telegram_menautkan_akun_dengan_secret_yang_benar(): void
    {
        config(['services.telegram.webhook_secret' => 'rahasia123', 'services.telegram.token' => 'tok']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $u = User::factory()->create();
        TelegramLink::create(['user_id' => $u->id, 'link_code' => 'ABCDEF123456']);
        $payload = ['message' => ['text' => '/start ABCDEF123456', 'chat' => ['id' => 99887766]]];

        $this->postJson('/api/telegram/webhook', $payload)->assertForbidden();
        $this->postJson('/api/telegram/webhook', $payload, ['X-Telegram-Bot-Api-Secret-Token' => 'salah'])->assertForbidden();
        $this->postJson('/api/telegram/webhook', $payload, ['X-Telegram-Bot-Api-Secret-Token' => 'rahasia123'])->assertOk();

        $link = TelegramLink::where('user_id', $u->id)->firstOrFail();
        $this->assertSame('99887766', $link->chat_id);
        $this->assertNull($link->link_code);
    }
}
