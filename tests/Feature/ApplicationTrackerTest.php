<?php

namespace Tests\Feature;

use App\Models\JobAlert;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_masuk(): void
    {
        $this->get('/dashboard/lamaran')->assertRedirect();
        $this->postJson('/dashboard/lamaran', ['title' => 'X', 'company' => 'Y'])->assertUnauthorized();
    }

    public function test_lacak_dari_halaman_lowongan_tidak_menggandakan_tautan_yang_sama(): void
    {
        $u = User::factory()->create();
        $body = ['title' => 'Backend Dev', 'company' => 'Acme', 'url' => 'https://example.com/job/1', 'location' => 'Pekanbaru'];

        $first = $this->actingAs($u)->postJson('/dashboard/lamaran', $body)->assertOk()->json();
        $again = $this->actingAs($u)->postJson('/dashboard/lamaran', $body)->assertOk()->json();

        $this->assertFalse($first['duplicate']);
        $this->assertTrue($again['duplicate']);
        $this->assertSame(1, JobApplication::where('user_id', $u->id)->count());
        $this->assertSame('wishlist', JobApplication::first()->status);
    }

    public function test_tautan_berbahaya_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/dashboard/lamaran', ['title' => 'X', 'company' => 'Y', 'url' => 'javascript:alert(1)'])
            ->assertUnprocessable();
    }

    public function test_pindah_tahap_mengisi_tanggal_melamar_dan_tampil_di_papan(): void
    {
        $u = User::factory()->create();
        $app = JobApplication::create(['user_id' => $u->id, 'title' => 'Analis', 'company' => 'PT Maju', 'status' => 'wishlist']);

        $this->actingAs($u)->patch("/dashboard/lamaran/{$app->id}", ['status' => 'wawancara'])->assertRedirect();
        $app->refresh();
        $this->assertSame('wawancara', $app->status);
        $this->assertNotNull($app->applied_at);

        $this->actingAs($u)->get('/dashboard/lamaran')->assertOk()->assertSee('Analis')->assertSee('PT Maju');
    }

    public function test_lamaran_orang_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $owner = User::factory()->create();
        $app = JobApplication::create(['user_id' => $owner->id, 'title' => 'T', 'company' => 'C', 'status' => 'dilamar']);
        $other = User::factory()->create();

        $this->actingAs($other)->patch("/dashboard/lamaran/{$app->id}", ['status' => 'ditolak'])->assertNotFound();
        $this->actingAs($other)->delete("/dashboard/lamaran/{$app->id}")->assertNotFound();
        $this->assertSame('dilamar', $app->fresh()->status);
    }

    public function test_alert_dibatasi_dan_telegram_butuh_tautan(): void
    {
        $u = User::factory()->create();
        $make = fn (string $kw, string $ch = 'email') => $this->actingAs($u)->post('/dashboard/lamaran/alert', ['keywords' => $kw, 'kind' => 'kerja', 'channel' => $ch]);

        $make('laravel, php')->assertSessionHasNoErrors();
        $make('python')->assertSessionHasNoErrors();
        $make('golang')->assertSessionHasErrors('keywords'); // batas Gratis 2
        $this->assertSame(2, JobAlert::where('user_id', $u->id)->count());
        $this->assertSame(['laravel', 'php'], JobAlert::orderBy('id')->first()->keywords);

        $other = User::factory()->create();
        $this->actingAs($other)->post('/dashboard/lamaran/alert', ['keywords' => 'x', 'kind' => 'kerja', 'channel' => 'telegram'])->assertSessionHasErrors('channel');
    }
}
