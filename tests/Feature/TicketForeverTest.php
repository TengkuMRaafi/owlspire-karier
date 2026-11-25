<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Exam\ExamStats;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tiket simulasi: jatah ujian penuh terbatas (2x), boleh dibeli tanpa daftar, aktivitas terekam, ikut peringkat. */
class TicketForeverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExamSeeder::class);
    }

    private function beliSebagaiTamu(ExamType $type, array $extra = []): Order
    {
        $this->post('/harga/beli', ['exam_type_id' => $type->id, 'ranking_shown' => 1] + $extra)->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    private function lunasi(Order $order): void
    {
        $this->post("/harga/tagihan/{$order->invoice_no}/simulasi-lunas")->assertRedirect();
    }

    public function test_tamu_beli_tiket_cukup_email_lalu_langsung_masuk_dan_bisa_ujian_penuh(): void
    {
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $order = $this->beliSebagaiTamu($type, ['email' => 'Budi@Example.com', 'display_name' => 'Budi S.', 'show_in_ranking' => 1]);

        $this->assertNull($order->user_id);
        $this->assertSame('budi@example.com', $order->meta['email']);
        $this->assertGuest();

        $this->lunasi($order);
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk();

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Budi S.', $user->name);
        $this->assertSame(2, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $type->id));

        // Simulasi lain tidak ikut terbuka: tiket berlaku per simulasi.
        $other = ExamType::where('id', '!=', $type->id)->firstOrFail();
        $this->assertSame(0, app(EntitlementService::class)->fullAttemptsAvailable($user->id, $other->id));

        $this->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertRedirect();
        $this->assertSame(1, ExamAttempt::where('user_id', $user->id)->where('mode', 'full')->count());
    }

    public function test_tamu_wajib_email_yang_valid_untuk_beli_tiket(): void
    {
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $this->post('/harga/beli', ['exam_type_id' => $type->id])->assertSessionHasErrors('email');
        $this->post('/harga/beli', ['exam_type_id' => $type->id, 'email' => 'bukan-email'])->assertSessionHasErrors('email');
        $this->assertSame(0, Order::count());
    }

    public function test_email_yang_sudah_punya_akun_dapat_tiket_tapi_pembeli_tidak_otomatis_masuk(): void
    {
        $owner = User::factory()->create(['email' => 'ada@example.com']);
        $type = ExamType::where('code', 'cpns')->firstOrFail();

        $order = $this->beliSebagaiTamu($type, ['email' => 'ada@example.com']);
        $this->lunasi($order);
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk();

        $this->assertGuest(); // email yang diketik belum terbukti milik pembeli
        $this->assertSame(2, app(EntitlementService::class)->fullAttemptsAvailable($owner->id, $type->id));
    }

    public function test_tidak_ikut_peringkat_disimpan_di_akun_baru(): void
    {
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $order = $this->beliSebagaiTamu($type, ['email' => 'diam@example.com']); // kotak peringkat tidak dicentang
        $order->update(['meta' => array_merge($order->meta, ['show_in_ranking' => false])]);
        $this->lunasi($order);

        $user = User::where('email', 'diam@example.com')->firstOrFail();
        $this->assertFalse($user->pref('privacy.leaderboard'));
    }

    public function test_jatah_dua_kali_lalu_terkunci_dan_beli_lagi_menambah_jatah(): void
    {
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $svc = app(EntitlementService::class);

        $svc->grantExamPass($user->id, $type->id);
        $this->assertSame(1, $svc->passCount($user->id));
        $this->assertSame(2, $svc->fullAttemptsAvailable($user->id, $type->id));

        $this->assertTrue($svc->useForFullAttempt($user->id, $type->id));
        $this->assertTrue($svc->useForFullAttempt($user->id, $type->id));
        $this->assertSame(0, $svc->fullAttemptsAvailable($user->id, $type->id));
        $this->assertSame(0, $svc->passCount($user->id));
        $this->assertFalse($svc->useForFullAttempt($user->id, $type->id)); // kembali terkunci

        // Beli lagi: dapat 2x baru. Tiket simulasi lain tidak ikut berubah.
        $svc->grantExamPass($user->id, $type->id);
        $this->assertSame(2, $svc->fullAttemptsAvailable($user->id, $type->id));
        $other = ExamType::where('id', '!=', $type->id)->firstOrFail();
        $this->assertSame(0, $svc->fullAttemptsAvailable($user->id, $other->id));
    }

    public function test_premium_tidak_memakai_jatah_tiket(): void
    {
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $svc = app(EntitlementService::class);
        $svc->grantPremium($user->id, 30);

        foreach (range(1, 5) as $_) {
            $this->assertTrue($svc->useForFullAttempt($user->id, $type->id));
        }
    }

    public function test_halaman_harga_punya_tabel_tiga_kolom_dan_tombol_mulai_ada_peringatan(): void
    {
        $this->get('/harga')->assertOk()->assertSee('cmp-t', false)->assertSee('Gratis')->assertSee('Tiket')->assertSee('Premium');

        // Harga di tabel mengikuti pengaturan admin: tiket dari pengaturan, Premium dari paket aktif.
        config(['platform.exam.pass_price' => 12345]);
        \App\Models\Plan::create(['code' => 'premium-bulanan', 'name' => 'Premium Bulanan', 'price' => 77777, 'interval' => 'month', 'is_active' => true]);
        $html = $this->get('/harga')->assertOk()->getContent();
        $section = substr($html, strpos($html, 'id="bandingkan"'), 4000);
        $this->assertStringContainsString('Rp 12.345', $section);
        $this->assertStringContainsString('Rp 77.777', $section);

        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        app(EntitlementService::class)->grantExamPass($user->id, $type->id);
        $this->actingAs($user)->get(route('account.exams'))->assertOk()->assertSee('data-confirm', false)->assertSee('Pakai 1 kesempatan tiket?');
    }

    public function test_jejak_jawaban_tercatat_dan_pemilik_tiket_masuk_peringkat(): void
    {
        $user = User::factory()->create(['name' => 'Siti Aminah']);
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        app(EntitlementService::class)->grantExamPass($user->id, $type->id);

        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'full'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();
        $this->assertNotNull($attempt->fresh()->started_at);

        $row = $attempt->answers()->first();
        $options = array_keys($row->question->options);
        $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $row->question_id, 'selected' => $options[0]])->assertOk();
        $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $row->question_id, 'selected' => $options[1]])->assertOk();

        $row->refresh();
        $this->assertNotNull($row->first_answered_at);
        $this->assertSame(1, (int) $row->changes);

        $this->post("/simulasi/ujian/{$attempt->id}/selesai")->assertRedirect();
        $this->assertSame(1, app(ExamStats::class)->rankOf($attempt->fresh()));
        $this->get("/simulasi/hasil/{$attempt->id}")->assertOk()->assertSee('Peringkat #1');
    }

    public function test_akun_dari_pembelian_tamu_bisa_buat_sandi_pertama_tanpa_sandi_lama(): void
    {
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $order = $this->beliSebagaiTamu($type, ['email' => 'baru@example.com']);
        $this->lunasi($order);
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk();

        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertTrue($user->needsFirstPassword());

        $this->put('/dashboard/pengaturan/sandi', ['password' => 'Rahasia12345', 'password_confirmation' => 'Rahasia12345'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->needsFirstPassword());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Rahasia12345', $user->fresh()->password));
    }
}
