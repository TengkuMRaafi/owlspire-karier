<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExamSeeder::class);
    }

    /** Pengguna mengerjakan satu simulasi coba sampai selesai. */
    private function finishedAttempt(User $user): ExamAttempt
    {
        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'trial'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();
        $this->actingAs($user)->post("/simulasi/ujian/{$attempt->id}/selesai");

        return $attempt->fresh();
    }

    public function test_pemilik_bisa_membuat_tautan_dan_halaman_publiknya_aman(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $attempt = $this->finishedAttempt($user);

        $url = $this->actingAs($user)->postJson("/simulasi/hasil/{$attempt->id}/bagikan")
            ->assertOk()->json('url');
        $this->assertNotEmpty($attempt->fresh()->share_token);

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()
            ->assertSee('Budi S.')
            ->assertDontSee('Budi Santoso')
            ->assertDontSee('Pembahasan')
            ->assertSee('noindex', false)
            ->assertSee('og:title', false);
    }

    public function test_tautan_yang_dimatikan_tidak_bisa_dibuka(): void
    {
        $user = User::factory()->create();
        $attempt = $this->finishedAttempt($user);
        $url = $this->actingAs($user)->postJson("/simulasi/hasil/{$attempt->id}/bagikan")->json('url');

        $this->actingAs($user)->deleteJson("/simulasi/hasil/{$attempt->id}/bagikan")->assertOk();
        $this->assertNull($attempt->fresh()->share_token);

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertNotFound();
    }

    public function test_orang_lain_tidak_bisa_membagikan_hasil_bukan_miliknya(): void
    {
        $owner = User::factory()->create();
        $attempt = $this->finishedAttempt($owner);

        $this->actingAs(User::factory()->create())->postJson("/simulasi/hasil/{$attempt->id}/bagikan")->assertNotFound();
        $this->assertNull($attempt->fresh()->share_token);
    }

    public function test_token_asal_tidak_membuka_apa_apa(): void
    {
        $this->get('/bagikan/'.str_repeat('a', 32))->assertNotFound();
    }

    public function test_halaman_hasil_memuat_tombol_bagikan(): void
    {
        $user = User::factory()->create();
        $attempt = $this->finishedAttempt($user);

        $this->actingAs($user)->get("/simulasi/hasil/{$attempt->id}")->assertOk()->assertSee('data-share-open', false);
    }
}
