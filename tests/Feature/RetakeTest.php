<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Exam\ExamStats;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ulangi simulasi: soal yang sama dikerjakan lagi di ruang ujian biasa (menggantikan halaman kartu "Ulangi soal"). */
class RetakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExamSeeder::class);
    }

    private function finishedTrial(User $user): ExamAttempt
    {
        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'trial'])->assertRedirect();
        $attempt = ExamAttempt::latest('id')->firstOrFail();
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        return $attempt->fresh();
    }

    public function test_ulangi_simulasi_memakai_soal_yang_sama_di_ruang_ujian(): void
    {
        $user = User::factory()->create();
        $old = $this->finishedTrial($user);

        $res = $this->post("/simulasi/hasil/{$old->id}/ulangi");

        $new = ExamAttempt::where('id', '!=', $old->id)->firstOrFail();
        $res->assertRedirect("/simulasi/ujian/{$new->id}");
        $this->assertSame($old->id, (int) $new->retake_of);
        $this->assertSame('trial', $new->mode);
        $this->assertNull($new->finished_at);
        $this->assertEqualsCanonicalizing(
            $old->answers()->pluck('question_id')->all(),
            $new->answers()->pluck('question_id')->all(),
        );
        $this->assertTrue($new->answers()->whereNotNull('selected')->doesntExist()); // jawaban lama tidak ikut terbawa

        // Urutan segmen tetap seperti simulasi sebelumnya.
        $segments = fn (ExamAttempt $a) => $a->answers()->with('question')->get()->pluck('question.segment_id')->unique()->values()->all();
        $this->assertSame($segments($old), $segments($new));

        $this->get("/simulasi/ujian/{$new->id}")->assertOk();
    }

    public function test_ulangi_saat_simulasi_sejenis_masih_berjalan_melanjutkan_yang_berjalan(): void
    {
        $user = User::factory()->create();
        $old = $this->finishedTrial($user);

        $this->post("/simulasi/hasil/{$old->id}/ulangi");
        $running = ExamAttempt::where('id', '!=', $old->id)->firstOrFail();

        $this->post("/simulasi/hasil/{$old->id}/ulangi")->assertRedirect("/simulasi/ujian/{$running->id}");
        $this->assertSame(2, ExamAttempt::count());
    }

    public function test_orang_lain_tidak_bisa_mengulang_hasil_milik_pengguna_lain(): void
    {
        $old = $this->finishedTrial(User::factory()->create());

        $this->actingAs(User::factory()->create())->post("/simulasi/hasil/{$old->id}/ulangi")->assertNotFound();
        $this->assertSame(1, ExamAttempt::count());
    }

    public function test_tamu_bisa_mengulang_simulasi_coba_miliknya(): void
    {
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $old = ExamAttempt::firstOrFail();
        $this->post("/simulasi/ujian/{$old->id}/selesai");

        $this->post("/simulasi/hasil/{$old->id}/ulangi")->assertRedirect();
        $new = ExamAttempt::where('id', '!=', $old->id)->firstOrFail();
        $this->assertNull($new->user_id);
        $this->assertNotNull($new->guest_token);
    }

    public function test_ulangi_ujian_penuh_memakai_jatah_dan_terkunci_saat_habis(): void
    {
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $tickets = app(EntitlementService::class);
        $tickets->grantExamPass($user->id, $type->id, 1);

        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertRedirect();
        $old = ExamAttempt::firstOrFail();
        $this->post("/simulasi/ujian/{$old->id}/selesai");
        $this->assertSame(1, $tickets->fullAttemptsAvailable($user->id, $type->id)); // jatah ke-1 terpakai

        // Ulangan ke-2 memakai jatah terakhir.
        $this->post("/simulasi/hasil/{$old->id}/ulangi")->assertRedirect();
        $this->assertSame(2, ExamAttempt::count());
        $this->assertSame(0, $tickets->fullAttemptsAvailable($user->id, $type->id));
        $this->post("/simulasi/ujian/".ExamAttempt::latest('id')->first()->id."/selesai");

        // Jatah habis: simulasi terkunci lagi, ulangan ditolak dan tidak ada percobaan baru.
        $this->post("/simulasi/hasil/{$old->id}/ulangi")->assertSessionHas('error');
        $this->assertSame(2, ExamAttempt::count());
        $this->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertSessionHas('error');
        $this->assertSame(2, ExamAttempt::count());

        // Beli tiket baru: terbuka lagi dengan 2 jatah.
        $tickets->grantExamPass($user->id, $type->id);
        $this->assertSame(2, $tickets->fullAttemptsAvailable($user->id, $type->id));
    }

    public function test_ulangan_tidak_ikut_peringkat_dan_skor_terbaik(): void
    {
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $base = ['user_id' => $user->id, 'exam_type_id' => $type->id, 'mode' => 'full', 'started_at' => now()->subHour(), 'deadline_at' => now()->addHour(), 'finished_at' => now()];

        $first = ExamAttempt::create($base + ['total_score' => 200]);
        ExamAttempt::create($base + ['total_score' => 450, 'retake_of' => $first->id]);

        $stats = app(ExamStats::class);
        $this->assertSame(200, (int) $stats->forUser($user->id)['terbaik']);
        $this->assertSame([200], $stats->leaderboard($type)->pluck('total_score')->map(fn ($v) => (int) $v)->all());
    }

    public function test_halaman_hasil_dan_riwayat_punya_tombol_ulangi_dan_halaman_kartu_lama_hilang(): void
    {
        $user = User::factory()->create();
        $old = $this->finishedTrial($user);

        $this->get("/simulasi/hasil/{$old->id}")->assertOk()->assertSee('Ulangi simulasi')->assertSee("/simulasi/hasil/{$old->id}/ulangi", false);
        $this->get('/dashboard/riwayat')->assertOk()->assertSee('Ulangi')->assertSee("/simulasi/hasil/{$old->id}/ulangi", false);
        $this->get('/dashboard')->assertOk()->assertDontSee('Ulangi simulasi')->assertDontSee('Soal diulang');

        $this->get('/dashboard/ulangi')->assertNotFound();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('account.review'));
    }

    public function test_jawaban_salah_tidak_lagi_membuat_antrean_kartu(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();

        foreach ($attempt->answers as $a) {
            $wrong = collect(array_keys($a->question->options))->first(fn ($k) => $k !== $a->question->answer);
            $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $a->question_id, 'selected' => $wrong])->assertOk();
        }
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        $this->assertSame(0, $attempt->fresh()->total_score);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('wrong_question_reviews'));
    }
}
