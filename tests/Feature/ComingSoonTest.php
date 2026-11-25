<?php

namespace Tests\Feature;

use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu Belajar terbuka secara bawaan; bisa ditutup (Segera Hadir) lewat config atau Pengaturan admin. */
class ComingSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function header(string $html): string
    {
        return substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));
    }

    public function test_belajar_menampilkan_segera_hadir_dengan_logo_murung_selama_belum_dibuka(): void
    {
        config(['platform.learning_open' => false]);

        $this->get('/belajar')->assertOk()
            ->assertSee('Segera Hadir')->assertSee('cs-sign', false)->assertSee('cs-owl', false)->assertSee('cs-tear', false)
            ->assertSee(route('careers.quiz'), false)->assertSee(route('exam.trial'), false)
            ->assertDontSee('Sumber belajar terbuka');
    }

    public function test_halaman_jalur_belajar_ikut_tertahan_selama_belum_dibuka(): void
    {
        config(['platform.learning_open' => false]);
        $course = Career::where('slug', 'data-analyst')->firstOrFail()->course;

        $this->get("/belajar/{$course->slug}")->assertOk()->assertSee('Segera Hadir');
    }

    public function test_navbar_menandai_belajar_segera_dan_lowongan_tidak(): void
    {
        config(['platform.learning_open' => false]);
        $header = $this->header($this->get('/simulasi')->assertOk()->getContent());

        // Tanda muncul dua kali di <header>: sekali di navbar desktop, sekali di menu ponsel.
        $this->assertSame(2, substr_count($header, 'nav-soon'), 'Hanya Belajar yang bertanda Segera (navbar desktop dan menu ponsel).');
        $this->assertGreaterThan(strpos($header, 'Belajar'), strpos($header, 'nav-soon'));
        $this->assertLessThan(strpos($header, 'Lowongan'), strpos($header, 'nav-soon'));
    }

    public function test_belajar_terbuka_secara_bawaan(): void
    {
        $this->assertTrue(config('platform.learning_open'));
        $this->get('/belajar')->assertOk()->assertDontSee('Segera Hadir');
    }

    public function test_belajar_terbuka_bila_dibuka_lewat_config(): void
    {
        config(['platform.learning_open' => true]);

        $this->get('/belajar')->assertOk()->assertDontSee('Segera Hadir');
        $this->assertSame(0, substr_count($this->header($this->get('/simulasi')->getContent()), 'nav-soon'));
    }

    public function test_lowongan_sudah_terbuka_bukan_coming_soon(): void
    {
        $this->get('/lowongan')->assertOk()->assertDontSee('Segera Hadir');
    }
}