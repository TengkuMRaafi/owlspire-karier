<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Sidebar admin: Info seleksi sudah dihapus, Broadcast menempati posisinya di grup Konten. */
class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    private function items(): array
    {
        return collect(config('panel.admin.groups'))->flatMap(fn ($g) => $g['items'])->all();
    }

    public function test_grup_konten_urutannya_karier_video_belajar_broadcast(): void
    {
        $konten = collect(config('panel.admin.groups'))->firstWhere('label', 'Konten');

        $this->assertSame(['Karier', 'Video belajar', 'Broadcast'], array_column($konten['items'], 'label'));
    }

    public function test_menu_admin_tidak_punya_info_seleksi_dan_broadcast_tidak_dobel(): void
    {
        $labels = array_column($this->items(), 'label');

        $this->assertNotContains('Info seleksi', $labels);
        $this->assertSame(1, count(array_keys($labels, 'Broadcast', true)));
        foreach ($this->items() as $item) {
            $this->assertStringStartsNotWith('admin.selections', $item['route']);
        }
    }

    public function test_semua_menu_admin_menunjuk_rute_dan_area_yang_ada(): void
    {
        foreach ($this->items() as $item) {
            $this->assertTrue(Route::has($item['route']), "Rute {$item['route']} tidak ada.");
            if (! empty($item['area'])) {
                $this->assertArrayHasKey($item['area'], config('roles.areas'), "Area {$item['area']} tidak ada di roles.");
            }
        }
    }

    public function test_area_selections_sudah_hilang_dari_peran_dan_akses_staf(): void
    {
        $this->assertArrayNotHasKey('selections', config('roles.areas'));
        $this->assertArrayNotHasKey('selections', config('roles.area_labels'));
        $this->assertNotContains('selections', config('roles.staff_grantable'));
        $this->assertFalse(Route::has('admin.selections.index'));
        $this->assertSame([], array_filter(array_keys(config('roles.areas')), fn ($a) => ! isset(config('roles.area_labels')[$a])));
    }

    public function test_admin_melihat_semua_menu_di_sidebar_dan_tanpa_info_seleksi(): void
    {
        $this->seed(ExamSeeder::class);
        $res = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk();

        foreach ($this->items() as $item) {
            $res->assertSee(route($item['route']), false);
        }
        $res->assertSee('Broadcast')->assertDontSee('Info seleksi');
    }

    public function test_urutan_sidebar_admin_di_halaman_video_belajar_lalu_broadcast_lalu_pesanan(): void
    {
        $this->seed(ExamSeeder::class);
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk()->getContent();

        $pos = fn (string $name) => strpos($html, 'href="'.route($name).'"');
        $this->assertNotFalse($pos('admin.learning.index'));
        $this->assertLessThan($pos('admin.broadcast.index'), $pos('admin.learning.index'));
        $this->assertLessThan($pos('admin.orders.index'), $pos('admin.broadcast.index'));
    }

    public function test_staf_tidak_melihat_broadcast_dan_tidak_bisa_membukanya(): void
    {
        $this->seed(ExamSeeder::class);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/admin')->assertOk()
            ->assertDontSee(route('admin.broadcast.index'), false)
            ->assertDontSee('Info seleksi');
        $this->actingAs($staff)->get('/admin/broadcast')->assertForbidden();
    }

    public function test_halaman_broadcast_dan_rute_seleksi_lama(): void
    {
        $this->seed(ExamSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/broadcast')->assertOk();
        $this->actingAs($admin)->get('/admin/seleksi')->assertNotFound();
        $this->actingAs($admin)->get('/admin/seleksi/tambah')->assertNotFound();
    }
}
