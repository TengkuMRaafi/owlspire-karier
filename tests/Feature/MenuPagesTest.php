<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman menu Simulasi (sudah termasuk Karier), Belajar, dan Premium: tampil untuk tamu, dengan daftar yang */
class MenuPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_simulasi_menampilkan_tiket_seleksi_dan_simulasi_karier(): void
    {
        $this->get('/simulasi')
            ->assertOk()
            ->assertSee('Pilih ujian,', false)
            ->assertSee('Simulasi karier')
            ->assertSee('data-live-form', false)
            ->assertSee('Coba simulasi');
    }

    public function test_simulasi_karier_dipaginasi_tiga_per_halaman(): void
    {
        $res = $this->get('/simulasi')->assertOk();
        $page = $res->viewData('careerPage');
        $this->assertSame(3, $page->perPage());
        $this->assertLessThanOrEqual(3, $page->count());
        $this->assertGreaterThan(3, $page->total(), 'Seed harus punya lebih dari 3 simulasi karier.');

        $this->get('/simulasi?page=2')->assertOk()->assertSee('aria-current="page"', false);
    }

    public function test_simulasi_karier_bisa_dicari_dan_dimuat_sebagian(): void
    {
        $res = $this->get('/simulasi?q=data')->assertOk();
        $this->assertGreaterThan(0, $res->viewData('careerPage')->total());
        $this->assertLessThan($res->viewData('careerTotal'), $res->viewData('careerPage')->total());

        $partial = $this->get('/simulasi?q=data', ['X-Partial' => 'results'])->assertOk();
        $partial->assertSee('Data Analyst');
        $partial->assertDontSee('<html', false);

        $this->get('/simulasi?q=tidak-ada-karier-ini')->assertOk()->assertSee('Belum ada yang cocok');
    }

    public function test_halaman_belajar_menampilkan_jalur_filter_dan_sumber_terbuka(): void
    {
        $this->get('/belajar')
            ->assertOk()
            ->assertSee('tiap karier.', false)
            ->assertSee('Mulai dari bidang yang kamu tuju.')
            ->assertSee('data-live-form', false)
            ->assertSee('Buka jalur');
    }

    public function test_jalur_belajar_dipaginasi_dan_bisa_dicari(): void
    {
        $page = $this->get('/belajar')->assertOk()->viewData('courses');
        $this->assertSame(3, $page->perPage());
        $this->assertLessThanOrEqual(3, $page->count());
        $this->assertGreaterThan(3, $page->total(), 'Seed harus punya lebih dari 3 jalur belajar.');

        $found = $this->get('/belajar?q=data')->assertOk()->viewData('courses');
        $this->assertGreaterThan(0, $found->total());
        $this->assertLessThan($page->total(), $found->total());

        $this->get('/belajar?q=data', ['X-Partial' => 'results'])->assertOk()->assertDontSee('<html', false);
        $this->get('/belajar?q=tidak-ada-jalur-ini')->assertOk()->assertSee('Belum ada jalur yang cocok');
    }

    public function test_tamu_tidak_melihat_tombol_lanjutkan_belajar(): void
    {
        $this->get('/belajar')->assertOk()->assertDontSee('Lanjutkan belajar');
    }

    public function test_halaman_premium_masih_ada_tapi_tidak_di_navbar(): void
    {
        $nav = $this->get('/simulasi')->assertOk()->getContent();
        $header = substr($nav, strpos($nav, '<header'), strpos($nav, '</header>') - strpos($nav, '<header'));
        $this->assertStringNotContainsString(route('billing.index'), $header);
        $this->assertStringNotContainsString(url('/karier'), $header);
        $this->assertStringContainsString('Lowongan', $header);
        $this->get('/harga')->assertOk()->assertSee('pm-hero', false)->assertSee('Mulai gratis.', false)->assertSee('Paling lengkap');
    }

    public function test_menu_aktif_ditandai_di_navbar(): void
    {
        $this->get('/simulasi')->assertOk()->assertSee('aria-current="page"', false);
        $this->get('/belajar')->assertOk()->assertSee('aria-current="page"', false);
    }
}
