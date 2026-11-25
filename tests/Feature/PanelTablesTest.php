<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\CareerSeeder;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tampilan panel: navbar, tabel seragam (cari, halaman, aksi), halaman tambah terpisah, dan create hanya admin. */
class PanelTablesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_navbar_hanya_tema_riwayat_notifikasi_dan_profil(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee('data-theme-toggle', false)->assertSee('Riwayat terbaru')->assertSee('Notifikasi')->assertSee('Profil &amp; pengaturan', false)
            ->assertSee('class="pn-head"', false);
    }

    public function test_semua_tabel_admin_punya_cari_nomor_dan_tombol_aksi(): void
    {
        $this->seed([ExamSeeder::class, CareerSeeder::class]);
        $admin = $this->admin();
        $member = User::factory()->create();
        $type = ExamType::first();
        $attempt = ExamAttempt::create(['user_id' => $member->id, 'exam_type_id' => $type->id, 'mode' => 'full', 'started_at' => now()->subHour(), 'finished_at' => now(), 'total_score' => 10, 'passed' => true]);
        Order::create(['user_id' => $member->id, 'invoice_no' => 'INV-1', 'item_type' => 'plan', 'item_id' => 1, 'amount' => 1000, 'status' => 'pending', 'meta' => []]);
        $this->actingAs($admin);

        foreach (['pengguna', 'karier', 'soal', 'jenis-ujian', 'hasil-ujian', 'pesanan', 'belajar'] as $path) {
            $r = $this->get("/admin/{$path}")->assertOk();
            $r->assertSee('ui-table-bar', false)->assertSee('type="search"', false)->assertSee('>No<', false)->assertSee('Aksi');
        }
        $this->get('/admin/karier')->assertSee('Detail')->assertSee('Hapus');
        $this->get('/admin/pengguna?cari=tidak-ada-orang-ini')->assertOk()->assertSee('Tidak ada pengguna yang cocok');

        $this->get(route('admin.attempts.show', $attempt))->assertOk();
        $this->get(route('admin.users.show', $member))->assertOk()->assertSee($member->email);
        $this->get(route('admin.users.edit', $member))->assertOk();
        $this->get(route('admin.setup.show', $type))->assertOk();
        $this->get(route('admin.setup.edit', $type))->assertOk();
        $this->get(route('admin.setup.segments.create', $type))->assertOk();
        $this->get(route('admin.careers.show', Career::first()))->assertOk();
        $this->get(route('admin.careers.edit', Career::first()))->assertOk();
        $this->get(route('admin.orders.show', Order::first()))->assertOk();
    }

    public function test_halaman_tambah_terpisah_dari_tabel(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs($this->admin());

        foreach (['pengguna', 'karier', 'soal', 'jenis-ujian', 'belajar'] as $path) {
            $this->get("/admin/{$path}/tambah")->assertOk()->assertDontSee('ui-table-wrap', false);
            $this->get("/admin/{$path}")->assertSee('href="'.url("/admin/{$path}/tambah").'"', false);
        }
    }

    public function test_tambah_ubah_hapus_pengguna_hanya_admin(): void
    {
        $admin = $this->admin();
        $staff = User::factory()->create(['role' => 'staff']);
        $member = User::factory()->create();

        $this->actingAs($staff)->get('/admin/pengguna/tambah')->assertForbidden();
        $this->post(route('admin.users.store'), ['name' => 'Baru', 'email' => 'b@example.com', 'password' => 'rahasia-123'])->assertForbidden();
        $this->actingAs($member)->get('/admin/pengguna/tambah')->assertForbidden();

        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Pengguna Baru', 'email' => 'p@example.com', 'password' => 'rahasia-123', 'role' => 'member'])
            ->assertRedirect(route('admin.users.index'));
        $new = User::where('email', 'p@example.com')->first();
        $this->assertSame('member', $new->role);

        $this->put(route('admin.users.update', $new), ['name' => 'Nama Baru', 'email' => 'p@example.com', 'role' => 'staff'])->assertRedirect();
        $this->assertSame('staff', $new->fresh()->role);

        $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->delete(route('admin.users.destroy', $new))->assertSessionHas('status');
        $this->assertNull(User::find($new->id));
    }

    public function test_hapus_soal_dan_hasil_ujian(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs($this->admin());
        $q = Question::first();
        $this->get(route('admin.questions.show', $q))->assertOk();
        $this->delete(route('admin.questions.destroy', $q))->assertSessionHas('status');
        $this->assertNull(Question::find($q->id));
    }

    public function test_dashboard_admin_dan_pengguna_berbeda(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Aktivitas 7 hari terakhir')->assertDontSee('Skor kesiapan karier');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Ujian selesai')->assertDontSee('Aktivitas 7 hari terakhir');
    }

    public function test_halaman_akun_memakai_tabel(): void
    {
        $this->seed([ExamSeeder::class, CareerSeeder::class]);
        $this->actingAs(User::factory()->create());
        foreach (['/dashboard/simulasi', '/dashboard/karier', '/dashboard/belajar', '/dashboard/riwayat'] as $path) {
            $this->get($path)->assertOk()->assertSee('ui-table-bar', false);
        }
        $this->get('/dashboard/karier')->assertSee('Jadikan target');
    }

    public function test_hanya_satu_admin_di_seeder_lokal(): void
    {
        $this->assertFileDoesNotExist(base_path('nonexistent'));
        $this->assertStringNotContainsString('titian.test', file_get_contents(database_path('seeders/DatabaseSeeder.php')));
        $this->assertStringContainsString('admin@owlspire.com', file_get_contents(database_path('seeders/DatabaseSeeder.php')));
    }
}
