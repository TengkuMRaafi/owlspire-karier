<?php

namespace Tests\Feature;

use App\Models\SelectionEvent;
use App\Models\User;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $over = []): SelectionEvent
    {
        return SelectionEvent::create($over + [
            'title' => 'Pendaftaran CPNS',
            'category' => 'cpns',
            'stage' => 'Pendaftaran',
            'starts_on' => now()->addDays(10)->toDateString(),
            'is_published' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_hitung_mundur_menghitung_hari_dengan_benar(): void
    {
        $this->assertSame('H-10', $this->event()->window()['label']);
        $this->assertSame('Besok', $this->event(['starts_on' => now()->addDay()->toDateString()])->window()['label']);

        $live = $this->event(['starts_on' => now()->subDays(2)->toDateString(), 'ends_on' => now()->addDays(3)->toDateString()])->window();
        $this->assertSame('live', $live['state']);
        $this->assertSame('Sisa 3 hari', $live['label']);

        $done = $this->event(['starts_on' => now()->subDays(9)->toDateString(), 'ends_on' => now()->subDays(5)->toDateString()])->window();
        $this->assertSame('done', $done['state']);
    }

    public function test_halaman_publik_menampilkan_jadwal_terbit_saja(): void
    {
        $this->seed(ExamSeeder::class);
        $this->event(['title' => 'Jadwal Terbit']);
        $this->event(['title' => 'Jadwal Draf', 'is_published' => false]);

        $this->get('/info-seleksi')->assertOk()
            ->assertSee('Jadwal Terbit')
            ->assertDontSee('Jadwal Draf')
            ->assertSee('data-sx-count', false);
    }

    public function test_filter_kategori(): void
    {
        $this->event(['title' => 'Khusus BUMN', 'category' => 'bumn']);
        $this->event(['title' => 'Khusus CPNS', 'category' => 'cpns']);

        $this->get('/info-seleksi?kategori=bumn')->assertOk()->assertSee('Khusus BUMN')->assertDontSee('Khusus CPNS');
    }

    public function test_halaman_kosong_tetap_terbuka(): void
    {
        $this->get('/info-seleksi')->assertOk()->assertSee('Belum ada jadwal');
    }

    public function test_landing_menampilkan_strip_hanya_bila_ada_jadwal(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Seleksi terdekat');

        $this->event(['title' => 'Pendaftaran BUMN Batch 3']);
        $this->get('/')->assertOk()->assertSee('Seleksi terdekat')->assertSee('Pendaftaran BUMN Batch 3');
    }

    public function test_pengguna_login_melihat_versi_di_dalam_akun(): void
    {
        $this->event(['title' => 'Jadwal Akun']);

        $this->actingAs(User::factory()->create())->get('/dashboard/seleksi')->assertOk()->assertSee('Jadwal Akun');
    }

    public function test_panel_jadwal_seleksi_sudah_dihapus_dari_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin/seleksi')->assertNotFound();
    }
}
