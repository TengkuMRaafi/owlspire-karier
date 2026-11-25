<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tidak_disimpan_cache_dan_ada_header_keamanan(): void
    {
        $user = User::factory()->create();
        $res = $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertSee('name="app-secure"', false);
    }

    public function test_setelah_logout_dashboard_diarahkan_ke_masuk(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/keluar')->assertRedirect('/');
        $this->assertGuest();

        $this->get('/dashboard')->assertRedirect('/masuk');
    }

    public function test_form_masuk_tidak_disimpan_cache(): void
    {
        $this->assertStringContainsString('no-store', $this->get('/masuk')->headers->get('Cache-Control'));
    }

    public function test_ruang_ujian_selesai_diarahkan_ke_hasil_dan_selesai_lewat_json(): void
    {
        $this->seed(ExamSeeder::class);
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();

        $room = $this->get("/simulasi/ujian/{$attempt->id}")->assertOk();
        $this->assertStringContainsString('no-store', $room->headers->get('Cache-Control'));

        $this->postJson("/simulasi/ujian/{$attempt->id}/selesai")
            ->assertOk()->assertJson(['ok' => true, 'redirect' => route('exam.result', $attempt)]);

        // Buka ruang soal lagi (mis. lewat Back): server mengarahkan ke hasil.
        $this->get("/simulasi/ujian/{$attempt->id}")->assertRedirect(route('exam.result', $attempt));
        $result = $this->get("/simulasi/hasil/{$attempt->id}")->assertOk();
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
    }
}
