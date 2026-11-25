<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Jobs\Divisions;
use App\Services\Jobs\JobScorer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Menu Lowongan: halaman, pencarian multi-sumber, skor kecocokan, dan CV khusus Premium. */
class JobsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-J-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    public function test_halaman_lowongan_tampil_tanpa_kolom_cv_dan_input_file(): void
    {
        $this->get('/lowongan')->assertOk()->assertSee('banyak sumber.', false)->assertSee('lw-scene', false)
            ->assertDontSee('Isi otomatis dari CV')
            ->assertSee('t.me/jobfinder_id_bot', false) // tombol bot Telegram kini sengaja ada (hero + footer)
            ->assertDontSee('<input type="file"', false);
    }

    public function test_semua_pengguna_mendapat_seluruh_hasil_lowongan(): void
    {
        $jobs = [['legal' => 'abaikan']];
        foreach (range(1, 8) as $i) {
            $jobs[] = ['id' => $i, 'position' => "Data Analyst {$i}", 'company' => "PT {$i}", 'url' => "https://remoteok.com/{$i}",
                'date' => now()->subDay()->toIso8601String(), 'description' => '<p>python sql excel power bi</p>'];
        }
        Http::fake(['remoteok.com/*' => Http::response($jobs)]);
        $body = ['positions' => ['Data Analyst'], 'skills' => ['python', 'sql', 'excel'], 'locations' => ['Indonesia'],
            'hours' => 168, 'kind' => 'kerja', 'sources' => ['remoteok']];

        $id = $this->postJson('/lowongan/cari', $body)->assertOk()->json('id');
        $free = $this->getJson("/lowongan/status/{$id}")->assertOk()->json();
        $this->assertTrue($free['premium']);
        $this->assertCount(8, $free['results']);
        $this->assertSame(0, $free['locked']['count']);

        $id = $this->actingAs($this->premiumUser())->postJson('/lowongan/cari', $body)->assertOk()->json('id');
        $pro = $this->getJson("/lowongan/status/{$id}")->assertOk()->json();
        $this->assertTrue($pro['premium']);
        $this->assertCount(8, $pro['results']);
        $this->assertSame(0, $pro['locked']['count']);
    }

    public function test_cv_butuh_login_tetapi_tidak_butuh_premium(): void
    {
        $file = fn () => UploadedFile::fake()->createWithContent('cv.txt', "Budi\nData Analyst\n\nSkills: python, sql, excel, power bi\n");

        $this->postJson('/lowongan/cv', ['cv' => $file()])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/lowongan/cv', ['cv' => $file()])->assertOk();

        $res = $this->actingAs($this->premiumUser())->postJson('/lowongan/cv', ['cv' => $file()])->assertOk();
        $this->assertContains('python', $res->json('skills'));
        $this->assertNotEmpty($res->json('positions'));
    }

    public function test_pencarian_menggabungkan_sumber_dan_mengurutkan_menurut_kecocokan(): void
    {
        Http::fake(['remoteok.com/*' => Http::response([
            ['legal' => 'abaikan'],
            ['id' => 1, 'position' => 'Data Analyst', 'company' => 'Acme', 'url' => 'https://remoteok.com/a', 'date' => now()->subDay()->toIso8601String(), 'description' => '<p>python sql excel power bi</p>'],
            ['id' => 2, 'position' => 'Barista', 'company' => 'Kopi', 'url' => 'https://remoteok.com/b', 'date' => now()->subDay()->toIso8601String(), 'description' => 'seduh kopi'],
        ])]);

        $id = $this->postJson('/lowongan/cari', [
            'positions' => ['Data Analyst'], 'skills' => ['python', 'sql', 'excel'], 'locations' => ['Indonesia'],
            'hours' => 168, 'kind' => 'kerja', 'sources' => ['remoteok'],
        ])->assertOk()->json('id');

        $state = $this->getJson("/lowongan/status/{$id}")->assertOk()->json();
        $this->assertSame('done', $state['status']);
        $this->assertSame('Data Analyst', $state['results'][0]['title']);
        $this->assertGreaterThanOrEqual(70, $state['results'][0]['match']);
        $this->assertContains('python', $state['results'][0]['matched']);
        $this->assertArrayHasKey('tiers', $state['insights']);
    }

    public function test_pencarian_butuh_posisi_atau_skill(): void
    {
        $this->postJson('/lowongan/cari', ['sources' => ['remoteok']])->assertStatus(422);
    }

    public function test_skor_kecocokan_membedakan_judul_sebidang_dan_tidak_terkait(): void
    {
        $ctx = app(Divisions::class)->context(['Akuntan'], ['excel']);
        $scorer = app(JobScorer::class);
        $a = $scorer->score(['title' => 'Staff Akuntansi', 'description' => 'excel pajak'], ['excel'], $ctx);
        $b = $scorer->score(['title' => 'Barista', 'description' => 'excel'], ['excel'], $ctx);
        $this->assertGreaterThan($b['match'], $a['match']);
    }
}
