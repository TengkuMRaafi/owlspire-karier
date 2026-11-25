<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\CareerTrendWeek;
use App\Models\User;
use App\Services\Careers\CareerDemandCollector;
use App\Services\Careers\CareerTrend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerTrendWeeklyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['career_trend.pause_ms' => 0]);
    }

    private function fakeApis(array $jobicy = [], array $remoteok = [], array $arbeitnow = []): void
    {
        Http::fake([
            'jobicy.com/*' => Http::response(['jobs' => $jobicy]),
            'remoteok.com/*' => Http::response(array_merge([['legal' => 'x']], $remoteok)),
            'www.arbeitnow.com/*' => Http::response(['data' => $arbeitnow]),
            'www.linkedin.com/*' => Http::response('', 200), // LinkedIn kosong: tidak mengubah hitungan sumber lain
        ]);
    }

    public function test_pengumpul_menghitung_lowongan_unik_dan_satu_lowongan_satu_karier(): void
    {
        $today = now()->toDateString();
        $this->fakeApis(
            jobicy: [['id' => 1, 'jobTitle' => 'Data Analyst', 'companyName' => 'A', 'pubDate' => $today, 'url' => 'x']],
            remoteok: [
                ['id' => 2, 'position' => 'Data Analyst', 'company' => 'A', 'date' => $today, 'url' => 'x'],      // duplikat Jobicy
                ['id' => 3, 'position' => 'Senior Software Engineer', 'company' => 'B', 'date' => $today, 'url' => 'x'],
                ['id' => 4, 'position' => 'AI Engineer', 'company' => 'C', 'date' => $today, 'url' => 'x'],       // bukan Software Engineer
                ['id' => 5, 'position' => 'Astronaut', 'company' => 'D', 'date' => $today, 'url' => 'x'],
            ],
        );

        $res = app(CareerDemandCollector::class)->collect(Career::listed()->get());
        $by = fn ($slug) => $res['counts'][Career::where('slug', $slug)->value('id')];

        $this->assertSame(1, $by('data-analyst')['jobs']);
        $this->assertSame(1, $by('software-developer')['jobs']);
        $this->assertSame(1, $by('ai-engineer')['jobs']);
        $this->assertSame(1, $res['unmatched']);
        $this->assertEqualsCanonicalizing(['jobicy', 'remoteok'], array_keys($by('data-analyst')['sources']));
    }

    public function test_papan_tersimpan_dan_landing_membaca_arsip_mingguan(): void
    {
        $today = now()->toDateString();
        $hr = Career::where('slug', 'hr-talent')->first();
        $this->fakeApis(remoteok: array_map(fn ($i) => ['id' => $i, 'position' => 'Recruiter '.$i, 'company' => 'C'.$i, 'date' => $today, 'url' => 'x'], range(1, 6)));

        $this->artisan('karier:kumpulkan-tren')->assertSuccessful();

        $this->assertSame(Career::count(), CareerTrendWeek::count());
        $b = app(CareerTrend::class)->board();
        $this->assertTrue($b['live']);
        $this->assertSame($hr->id, $b['items']->first()['career']->id);
        $this->assertSame(6, $b['items']->first()['jobs']);
        $this->assertSame(7, $b['days']);
        $this->get('/')->assertOk()->assertSee($hr->name);
    }

    public function test_dry_run_tidak_menyimpan_dan_semua_sumber_gagal_tidak_menimpa_arsip(): void
    {
        $this->fakeApis();
        $this->artisan('karier:kumpulkan-tren --dry-run')->assertSuccessful();
        $this->assertSame(0, CareerTrendWeek::count());

        $this->artisan('karier:kumpulkan-tren')->assertSuccessful();
        $before = CareerTrendWeek::count();

        $factory = new \Illuminate\Http\Client\Factory();
        Http::swap($factory);
        $factory->fake(['*' => Http::response('error', 500)]);
        $this->artisan('karier:kumpulkan-tren')->assertFailed();
        $this->assertSame($before, CareerTrendWeek::count());
    }

    public function test_minggu_baru_memberi_selisih_peringkat_dan_simulasi_target_ikut_dihitung(): void
    {
        $last = Career::listed()->get()->last();
        $trend = app(CareerTrend::class);

        $trend->rebuild([], Carbon::parse('2026-10-25 08:00:00', 'Asia/Jakarta'));   // Minggu 25 Okt
        foreach (range(1, 3) as $i) {
            User::factory()->create(['target_career_id' => $last->id, 'created_at' => '2026-10-01 00:00:00']);
        }
        $trend->rebuild([], Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));   // Minggu 1 Nov

        $weeks = CareerTrendWeek::query()->distinct()->orderBy('week_start')->pluck('week_start')->map(fn ($d) => substr((string) $d, 0, 10))->all();
        $this->assertSame(['2026-10-25', '2026-11-01'], $weeks);

        $b = $trend->board();
        $top = $b['items']->first();
        $this->assertSame($last->id, $top['career']->id);
        $this->assertSame(3, $top['targets']);
        $this->assertGreaterThan(0, $top['delta']); // naik dibanding minggu sebelumnya
    }

    public function test_jadwal_berjalan_tiap_minggu_pukul_nol_nol(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'karier:kumpulkan-tren'));
        $this->assertNotNull($event);
        $this->assertSame('0 0 * * 0', $event->expression);
        $this->assertSame('Asia/Jakarta', (string) $event->timezone);
    }
}
