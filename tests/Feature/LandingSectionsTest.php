<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Career;
use App\Services\Careers\CareerTrend;
use App\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LandingSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_landing_memuat_bagian_peringkat_dan_tanpa_bagian_lama(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Papan karier')
            ->assertSee('data-pk', false)
            ->assertSee('Siapa yang paling')
            ->assertSee('Cari arah kariermu')
            ->assertSee('data-guide-end', false)
            ->assertDontSee('data-of-side', false)
            ->assertDontSee('data-sx-stack', false)
            ->assertDontSee('Latihan yang bisa dipercaya');
    }

    public function test_papan_skor_memakai_data_nyata_dan_rating_hanya_dari_env(): void
    {
        // Mode nyata (demo mati): tanpa PLATFORM_RATING, kartu rating tidak tampil dan angka lain berasal dari database.
        config(['platform.scoreboard.demo' => false, 'platform.scoreboard.rating' => null]);
        $this->get('/')->assertOk()
            ->assertSee('data-sb', false)
            ->assertSee('data-sb-key="pengguna"', false)
            ->assertSee('data-sb-key="soal"', false)
            ->assertDontSee('data-sb-key="rating"', false)
            ->assertDontSee('850000', false);

        // Bila PLATFORM_RATING diisi, nilai itu yang dipakai.
        config(['platform.scoreboard.rating' => 4.5]);
        $this->get('/')->assertOk()->assertSee('data-sb-key="rating"', false)->assertSee('data-sb-val="4.5"', false);
    }

    public function test_papan_skor_mode_contoh_menampilkan_angka_fiktif_lengkap(): void
    {
        config(['platform.scoreboard.demo' => true, 'platform.scoreboard.rating' => null, 'platform.scoreboard.rating_count' => null]);
        $this->get('/')->assertOk()
            ->assertSee('data-sb-key="rating"', false)
            ->assertSee('data-sb-key="pengguna"', false)
            ->assertSee('data-sb-key="selesai"', false)
            ->assertSee('data-sb-key="soal"', false)
            ->assertSee('data-sb-val="4.8"', false)
            ->assertSee('data-sb-val="12480"', false);
    }

    public function test_papan_karier_urut_awal_saat_data_kosong(): void
    {
        $b = app(CareerTrend::class)->board(Carbon::parse('2026-10-23'));
        $this->assertFalse($b['live']);
        $this->assertSame(Career::listed()->pluck('id')->all(), $b['items']->pluck('career.id')->all());
    }

    public function test_urutan_berubah_mengikuti_simulasi_dan_target_nyata(): void
    {
        $last = Career::listed()->get()->last();
        $first = Career::listed()->first();
        $now = Carbon::parse('2026-10-23 12:00:00');
        $at = app(CareerTrend::class)->window($now)['start'];

        foreach (range(1, 4) as $i) {
            $u = User::factory()->create(['target_career_id' => $last->id, 'created_at' => $at->copy()->subDays(40)]);
            ExamAttempt::create(['user_id' => $u->id, 'exam_type_id' => $last->exam_type_id, 'mode' => 'trial',
                'started_at' => $at->copy()->subDays(2), 'finished_at' => $at->copy()->subDays(2)]);
        }

        $b = app(CareerTrend::class)->board($now);
        $this->assertTrue($b['live']);
        $this->assertSame($last->id, $b['items']->first()['career']->id);
        $this->assertSame(4, $b['items']->first()['sims']);
        $this->assertGreaterThan(1, $b['items']->firstWhere(fn ($x) => $x['career']->id === $first->id)['position']);
    }

    public function test_jendela_pembaruan_mengikuti_config(): void
    {
        config(['platform.trending.refresh_days' => 3]);
        $t = app(CareerTrend::class);
        $a = $t->window(Carbon::parse('2026-10-23 01:00:00'));
        $b = $t->window(Carbon::parse('2026-10-23 23:00:00'));
        $this->assertSame($a['bucket'], $b['bucket']);
        $this->assertEquals(3, $a['start']->diffInDays($a['next']));
    }

    public function test_pembaruan_default_mingguan_dan_lowongan_hanya_dari_sumber_terdaftar(): void
    {
        $this->assertSame(7, config('platform.trending.refresh_days'));

        $career = Career::listed()->first();
        $official = \App\Models\JobSource::create(['key' => 'jobicy', 'name' => 'Jobicy']);
        $linkedin = \App\Models\JobSource::create(['key' => 'linkedin', 'name' => 'LinkedIn']);
        $other = \App\Models\JobSource::create(['key' => 'sumber-lain', 'name' => 'Sumber Lain']); // tidak ada di daftar job_sources
        $now = Carbon::parse('2026-10-23 12:00:00');
        $at = app(CareerTrend::class)->window($now)['start'];
        foreach ([$official, $linkedin, $other] as $src) {
            \App\Models\JobListing::create(['job_source_id' => $src->id, 'external_id' => $src->key.'-1', 'title' => $career->name,
                'company' => 'PT Contoh', 'url' => 'https://contoh.test/'.$src->key, 'posted_at' => $at->copy()->subDays(2)]);
        }

        $row = app(CareerTrend::class)->board($now)['items']->firstWhere(fn ($x) => $x['career']->id === $career->id);
        $this->assertSame(2, $row['jobs']); // Jobicy + LinkedIn dihitung; sumber di luar daftar job_sources tidak
    }
}
