<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Billing\EntitlementService;
use Database\Seeders\ExamSeeder;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExamSeeder::class);
    }

    public function test_tamu_bisa_mencoba_lalu_melihat_hasil_dan_pembahasan(): void
    {
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial'])->assertRedirect();

        $attempt = ExamAttempt::firstOrFail();
        $this->assertSame('trial', $attempt->mode);
        $this->assertNull($attempt->user_id);
        $this->assertSame(15, $attempt->answers()->count()); // 5 soal x 3 segmen

        $room = $this->get("/simulasi/ujian/{$attempt->id}")->assertOk();
        $room->assertDontSee('\u0022answer\u0022', false); // kunci tidak boleh bocor ke browser
        $room->assertDontSee('explanation');

        $first = $attempt->answers()->first();
        $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $first->question_id, 'selected' => $first->question->answer])
            ->assertOk()->assertJson(['ok' => true, 'finished' => false]);

        $this->post("/simulasi/ujian/{$attempt->id}/selesai")->assertRedirect("/simulasi/hasil/{$attempt->id}");

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        $this->assertSame(5, $attempt->total_score); // hanya 1 soal benar x 5 poin
        $this->assertNull($attempt->passed);          // mode coba tanpa status lulus
        $this->get("/simulasi/hasil/{$attempt->id}")->assertOk()->assertSee('Pembahasan');
    }

    public function test_orang_lain_tidak_bisa_membuka_percobaan_milik_tamu_lain(): void
    {
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();

        $this->flushSession();
        $this->get("/simulasi/ujian/{$attempt->id}")->assertNotFound();
    }

    public function test_waktu_habis_otomatis_menilai(): void
    {
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();
        $attempt->update(['deadline_at' => now()->subMinute()]);

        $this->get("/simulasi/ujian/{$attempt->id}")->assertRedirect("/simulasi/hasil/{$attempt->id}");
        $this->assertNotNull($attempt->fresh()->finished_at);
    }

    public function test_jawaban_setelah_waktu_habis_ditolak_dan_ujian_dinilai(): void
    {
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();
        $attempt->update(['deadline_at' => now()->subMinutes(5)]);
        $qid = $attempt->answers()->value('question_id');

        $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $qid, 'selected' => 'A'])
            ->assertOk()->assertJson(['finished' => true]);
        $this->assertNull($attempt->answers()->where('question_id', $qid)->value('selected'));
    }

    public function test_ujian_penuh_butuh_tiket_dan_tiket_berkurang_satu_tiap_mulai(): void
    {
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();

        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertSessionHas('error');
        $this->assertSame(0, ExamAttempt::count());

        app(EntitlementService::class)->grantExamPass($user->id, $type->id, 1);
        $this->assertSame(2, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));

        $this->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();
        $this->assertSame('full', $attempt->mode);
        $this->assertSame($user->id, $attempt->user_id);
        // Satu ujian penuh memakai 1 dari 2 jatah.
        $this->assertSame(1, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));

        // Memulai lagi saat ujian masih berjalan melanjutkan yang sama, tidak membuat percobaan baru dan tidak memakai jatah.
        $this->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertRedirect("/simulasi/ujian/{$attempt->id}");
        $this->assertSame(1, ExamAttempt::count());
        $this->assertSame(1, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));
    }

    public function test_pembelian_tiket_lalu_verifikasi_admin_mengaktifkan_tiket(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $type = ExamType::where('code', 'bumn')->firstOrFail();

        $this->actingAs($user)->post('/harga/beli', ['exam_type_id' => $type->id])->assertRedirect();
        $order = \App\Models\Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(9999, (int) $order->amount);

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee($order->invoice_no);
        $this->post("/harga/tagihan/{$order->invoice_no}/konfirmasi")->assertRedirect();

        $this->actingAs($admin)->post("/admin/pesanan/{$order->id}/lunas")->assertRedirect();
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(2, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));

        // Melunasi dua kali tidak menggandakan tiket.
        $this->post("/admin/pesanan/{$order->id}/lunas");
        $this->assertSame(2, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));
    }
}
