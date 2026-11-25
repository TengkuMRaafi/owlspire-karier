<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Services\Careers\CareerTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_karier_lama_diarahkan_ke_simulasi(): void
    {
        $this->get('/karier')->assertRedirect('/simulasi#karier');
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('careers.index'));
    }

    public function test_simulasi_memuat_info_karier_dan_bisa_dicari_lewat_skill(): void
    {
        $total = Career::listed()->count();
        $this->assertGreaterThan(3, $total);

        $r = $this->get('/simulasi')->assertOk();
        $this->assertSame(3, substr_count($r->getContent(), 'class="sx-card"'));
        $r->assertSee('sx-div', false)->assertSee('Skill yang dicari', false)->assertSee('Cari arah kariermu');

        $this->get('/simulasi?q=akuntan')->assertOk()->assertSee('Akuntan &amp; Perpajakan', false)->assertDontSee('AI Engineer');
        $this->get('/simulasi?q=pytorch')->assertOk()->assertSee('AI Engineer');
    }

    public function test_tema_gambar_mengikuti_kata_kunci(): void
    {
        $t = app(CareerTheme::class);
        $by = fn ($slug) => $t->detect(Career::where('slug', $slug)->firstOrFail());

        $this->assertSame('it', $by('software-developer'));
        $this->assertSame('it', $by('ai-engineer'));
        $this->assertSame('uang', $by('akuntansi'));
        $this->assertSame('k3', $by('ehs-specialist'));
        $this->assertSame('mesin', $by('industrial-engineer'));
        $this->assertSame('logistik', $by('logistik'));
        $this->assertSame('sdm', $by('hr-talent'));

        // Karier baru buatan admin langsung dapat tema yang cocok.
        $baru = new Career(['name' => 'Staf Akuntansi', 'slug' => 'staf-akuntansi', 'division' => 'Keuangan', 'summary' => 'Mencatat transaksi.']);
        $this->assertSame('uang', $t->detect($baru));
        $this->assertSame('umum', $t->detect(new Career(['name' => 'Xyz', 'slug' => 'xyz', 'division' => 'Lainnya', 'summary' => 'Abc.'])));
    }

    public function test_tema_memakai_gambar_unggahan_admin_bila_ada(): void
    {
        $c = new Career(['name' => 'Apa saja', 'slug' => 'apa-saja', 'division' => 'X', 'summary' => 'Y', 'details' => ['image' => 'https://contoh.test/foto.jpg']]);
        $this->assertSame('https://contoh.test/foto.jpg', app(CareerTheme::class)->for($c)['image']);
    }
}
