<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Plan;
use App\Services\Billing\EntitlementService;
use App\Services\Learning\YouTubeCcClient;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['platform.payment.driver' => 'midtrans', 'platform.payment.midtrans.server_key' => self::KEY, 'platform.payment.midtrans.method' => 'snap', 'platform.payment.midtrans.is_production' => false]);
        $this->app->forgetInstance(\App\Contracts\PaymentGateway::class);
    }

    private const SNAP = ['token' => 'tok-1', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/tok-1'];

    private function sign(string $orderId, string $code, string $gross): string
    {
        return hash('sha512', $orderId.$code.$gross.self::KEY);
    }

    private function beli(User $user, string $code = 'premium-1m'): Order
    {
        $plan = Plan::where('code', $code)->firstOrFail();
        $this->actingAs($user)->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_beli_premium_membuat_tagihan_snap_di_midtrans(): void
    {
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201)]);

        $user = User::factory()->create();
        $order = $this->beli($user);

        $this->assertSame(29000, (int) $order->amount);
        $this->assertSame('tok-1', $order->gateway_ref);
        $this->assertSame(self::SNAP['redirect_url'], $order->meta['pay_url']);

        Http::assertSent(function ($req) use ($order) {
            return $req->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $req['transaction_details']['order_id'] === $order->invoice_no
                && $req['transaction_details']['gross_amount'] === 29000
                && $req['callbacks']['finish'] === route('billing.invoice', $order)
                && $req->hasHeader('Authorization', 'Basic '.base64_encode(self::KEY.':'));
        });

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee(self::SNAP['redirect_url'], false)->assertSee('Bayar sekarang')->assertSee('Menunggu pembayaran');
        // Tagihan yang sama dipakai ulang, tidak membuat tagihan baru.
        $this->post('/harga/langganan', ['plan_id' => $order->item_id])->assertRedirect("/harga/tagihan/{$order->invoice_no}");
        $this->assertSame(1, Order::count());
    }

    public function test_qris_tampil_langsung_di_halaman_tagihan_bila_channel_aktif(): void
    {
        config(['platform.payment.midtrans.method' => 'qris']);
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201', 'transaction_id' => 'trx-1', 'qr_string' => '00020101021226QRISCONTOH',
            'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-1/qr-code']],
        ], 201)]);

        $order = $this->beli(User::factory()->create());

        $this->assertSame('trx-1', $order->gateway_ref);
        $this->assertSame('00020101021226QRISCONTOH', $order->meta['qr_string']);
        Http::assertSent(fn ($req) => $req->url() === 'https://api.sandbox.midtrans.com/v2/charge' && $req['payment_type'] === 'qris');
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/snap/v1/transactions'));

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('data-qr="00020101021226QRISCONTOH"', false);
    }

    public function test_qris_belum_aktif_otomatis_jatuh_ke_snap(): void
    {
        config(['platform.payment.midtrans.method' => 'qris']);
        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '402', 'status_message' => 'Payment channel is not activated.'], 402),
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201),
        ]);

        $order = $this->beli(User::factory()->create());

        $this->assertSame(self::SNAP['redirect_url'], $order->meta['pay_url']);
        $this->assertArrayNotHasKey('qr_string', $order->meta);
    }

    public function test_kunci_salah_tidak_jatuh_ke_snap_dan_tidak_ada_pesanan_menggantung(): void
    {
        config(['platform.payment.midtrans.method' => 'qris']);
        Http::fake(['*' => Http::response(['status_code' => '401', 'status_message' => 'Unauthorized'], 401)]);

        $this->actingAs(User::factory()->create())->post('/harga/langganan', ['plan_id' => Plan::first()->id])->assertSessionHas('error');
        $this->assertSame(0, Order::count());
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/snap/v1/transactions'));
    }

    public function test_midtrans_menolak_maka_tidak_ada_pesanan_menggantung(): void
    {
        Http::fake(['*' => Http::response(['status_code' => '401', 'status_message' => 'Unauthorized'], 401)]);
        $user = User::factory()->create();
        $plan = Plan::first();
        $this->actingAs($user)->post('/harga/langganan', ['plan_id' => $plan->id])->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_webhook_settlement_mengaktifkan_premium(): void
    {
        Http::fake(['*' => Http::response(self::SNAP, 201)]);
        $user = User::factory()->create();
        $order = $this->beli($user);
        $this->assertFalse(app(EntitlementService::class)->isPremium($user->id));

        $body = ['order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'settlement'];
        $this->postJson('/api/webhooks/payment/midtrans', $body + ['signature_key' => $this->sign($order->invoice_no, '200', '29000.00')])->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertTrue(app(EntitlementService::class)->isPremium($user->id));
        // Notifikasi ganda tidak memperpanjang dua kali.
        $until = app(EntitlementService::class)->premiumUntil($user->id);
        $this->postJson('/api/webhooks/payment/midtrans', $body + ['signature_key' => $this->sign($order->invoice_no, '200', '29000.00')])->assertOk();
        $this->assertTrue($until->equalTo(app(EntitlementService::class)->premiumUntil($user->id)));
    }

    public function test_webhook_tanda_tangan_palsu_atau_nominal_salah_ditolak(): void
    {
        Http::fake(['*' => Http::response(self::SNAP, 201)]);
        $user = User::factory()->create();
        $order = $this->beli($user);

        $this->postJson('/api/webhooks/payment/midtrans', ['order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00',
            'transaction_status' => 'settlement', 'signature_key' => 'palsu'])->assertStatus(400);

        $this->postJson('/api/webhooks/payment/midtrans', ['order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '1000.00',
            'transaction_status' => 'settlement', 'signature_key' => $this->sign($order->invoice_no, '200', '1000.00')])->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertFalse(app(EntitlementService::class)->isPremium($user->id));
    }

    public function test_polling_status_menyelesaikan_pesanan_bila_webhook_terlambat(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201),
        ]);
        $user = User::factory()->create();
        $order = $this->beli($user);

        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'settlement',
            'signature_key' => $this->sign($order->invoice_no, '200', '29000.00'),
        ])]);

        $this->getJson("/harga/tagihan/{$order->invoice_no}/status")->assertJson(['status' => 'paid']);
        $this->assertTrue(app(EntitlementService::class)->isPremium($user->id));
        // Orang lain tidak boleh mengintip status tagihan.
        $this->actingAs(User::factory()->create())->getJson("/harga/tagihan/{$order->invoice_no}/status")->assertNotFound();
    }

    public function test_membuka_halaman_tagihan_langsung_menampilkan_lunas_bila_sudah_dibayar(): void
    {
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201)]);
        $user = User::factory()->create();
        $order = $this->beli($user);

        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'settlement',
            'signature_key' => $this->sign($order->invoice_no, '200', '29000.00'),
        ])]);

        $this->actingAs($user)->get(route('billing.invoice', $order))->assertOk()->assertSee('Pembayaran diterima');
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_cek_status_menerima_jawaban_tanpa_signature_tapi_webhook_tetap_wajib_signature(): void
    {
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201)]);
        $user = User::factory()->create();
        $order = $this->beli($user);

        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'settlement',
        ])]);
        $this->actingAs($user)->getJson("/harga/tagihan/{$order->invoice_no}/status")->assertJson(['status' => 'paid', 'gateway_ok' => true]);

        $order2 = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-T-WEBHK', 'item_type' => 'plan', 'item_id' => $order->item_id, 'amount' => 29000, 'status' => 'pending', 'gateway' => 'midtrans', 'expires_at' => now()->addHour(), 'meta' => []]);
        $this->postJson('/api/webhooks/payment/midtrans', ['order_id' => $order2->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'settlement'])->assertStatus(400);
        $this->assertSame('pending', $order2->fresh()->status);
    }

    public function test_membeli_lagi_memperpanjang_dari_tanggal_berakhir(): void
    {
        Http::fake(['*' => Http::response(self::SNAP, 201)]);
        $user = User::factory()->create();
        foreach ([1, 2] as $n) {
            $order = $this->beli($user);
            $this->postJson('/api/webhooks/payment/midtrans', ['order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00',
                'transaction_status' => 'settlement', 'signature_key' => $this->sign($order->invoice_no, '200', '29000.00')])->assertOk();
        }
        $days = now()->diffInDays(app(EntitlementService::class)->premiumUntil($user->id));
        $this->assertGreaterThanOrEqual(58, $days); // ~2 bulan, bukan 1
    }

    private function beliSebagaiTamu(string $email, string $code = 'premium-1m'): Order
    {
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(self::SNAP, 201)]);

        $plan = Plan::where('code', $code)->firstOrFail();
        $this->post('/harga/langganan', ['plan_id' => $plan->id, 'email' => $email])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    private function lunasi(Order $order): void
    {
        $gross = number_format((int) $order->amount, 2, '.', '');
        $this->postJson('/api/webhooks/payment/midtrans', [
            'order_id' => $order->invoice_no, 'status_code' => '200', 'gross_amount' => $gross, 'transaction_status' => 'settlement',
            'signature_key' => $this->sign($order->invoice_no, '200', $gross),
        ])->assertOk();
    }

    public function test_tamu_cukup_isi_email_dan_langsung_melihat_tombol_bayar(): void
    {
        $order = $this->beliSebagaiTamu('Budi@Example.com');

        $this->assertNull($order->user_id);
        $this->assertSame('budi@example.com', $order->meta['email']);
        $this->assertGuest();

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()
            ->assertSee(self::SNAP['redirect_url'], false)->assertSee('budi@example.com')->assertSee('Menunggu pembayaran');

        // Klik ulang dengan email yang sama memakai tagihan yang sama.
        $this->post('/harga/langganan', ['plan_id' => $order->item_id, 'email' => 'budi@example.com'])->assertRedirect("/harga/tagihan/{$order->invoice_no}");
        $this->assertSame(1, Order::count());
    }

    public function test_tamu_wajib_email_yang_valid(): void
    {
        $plan = Plan::first();
        $this->post('/harga/langganan', ['plan_id' => $plan->id])->assertSessionHasErrors('email');
        $this->post('/harga/langganan', ['plan_id' => $plan->id, 'email' => 'bukan-email'])->assertSessionHasErrors('email');
        $this->assertSame(0, Order::count());
    }

    public function test_tagihan_tamu_tidak_bisa_dibuka_dari_sesi_lain(): void
    {
        $order = $this->beliSebagaiTamu('budi@example.com');

        $this->flushSession();
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertNotFound();
        $this->getJson("/harga/tagihan/{$order->invoice_no}/status")->assertNotFound();
    }

    public function test_tamu_email_baru_dibuatkan_akun_premium_aktif_dan_langsung_masuk(): void
    {
        $order = $this->beliSebagaiTamu('baru@example.com');
        $this->assertNull(User::where('email', 'baru@example.com')->first());

        $this->lunasi($order);

        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertSame($user->id, $order->fresh()->user_id);
        $this->assertTrue(app(EntitlementService::class)->isPremium($user->id));

        // Halaman tagihan (dimuat ulang oleh polling) memasukkan pembeli ke akun yang baru dibuat.
        $this->assertGuest();
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('Premium kamu sudah aktif');
        $this->assertAuthenticatedAs($user);
    }

    public function test_pembeli_baru_melihat_sandi_acak_yang_bisa_dipakai_masuk_lalu_hilang_setelah_diganti(): void
    {
        $order = $this->beliSebagaiTamu('sandi@example.com');
        $this->lunasi($order);

        // Halaman tagihan memasukkan pembeli dan menampilkan sandi acak (format 4-4-4) beserta tombol salin.
        $page = $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('data-copy', false);
        $user = User::where('email', 'sandi@example.com')->firstOrFail();
        $plain = app(\App\Services\Billing\PurchasePassword::class)->reveal($order->fresh(), $user);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $plain);
        $page->assertSee($plain);
        $this->assertTrue($user->auto_password);
        $this->assertNotSame($plain, $order->fresh()->meta['initial_password']); // tersimpan terenkripsi, bukan teks biasa

        // Sandi itu benar-benar bisa dipakai masuk dari perangkat lain walau email belum diverifikasi.
        $this->post('/keluar');
        $this->flushSession();
        $this->post('/masuk', ['email' => 'sandi@example.com', 'password' => $plain])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Setelah pemilik membuat sandi sendiri, sandi acak tidak tampil dan tidak tersimpan lagi.
        $this->put('/dashboard/pengaturan/sandi', ['password' => 'SandiBaru123', 'password_confirmation' => 'SandiBaru123'])->assertSessionHasNoErrors();
        $this->assertNull(app(\App\Services\Billing\PurchasePassword::class)->reveal($order->fresh(), $user->fresh()));
        $this->assertArrayNotHasKey('initial_password', $order->fresh()->meta);
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertDontSee($plain);
    }

    public function test_sandi_acak_tidak_tampil_untuk_email_yang_sudah_punya_akun(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $order = $this->beliSebagaiTamu('ada@example.com');
        $this->lunasi($order);

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertDontSee('data-copy', false);
        $this->assertArrayNotHasKey('initial_password', $order->fresh()->meta);
    }

    public function test_email_yang_sudah_punya_akun_dapat_premium_tapi_tidak_di_login_otomatis(): void
    {
        $korban = User::factory()->create(['email' => 'ada@example.com']);
        $order = $this->beliSebagaiTamu('ada@example.com');

        $this->lunasi($order);

        $this->assertTrue(app(EntitlementService::class)->isPremium($korban->id));
        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('Masuk dengan email itu');
        $this->assertGuest();
    }

    public function test_youtube_hanya_menerima_video_cc_embeddable_dan_publik(): void
    {
        config(['services.youtube.key' => 'k']);
        Http::fake([
            'googleapis.com/youtube/v3/search*' => Http::response(['items' => [['id' => ['videoId' => 'AAAAAAAAAAA']], ['id' => ['videoId' => 'BBBBBBBBBBB']], ['id' => ['videoId' => 'CCCCCCCCCCC']]]]),
            'googleapis.com/youtube/v3/videos*' => Http::response(['items' => [
                ['id' => 'AAAAAAAAAAA', 'snippet' => ['title' => 'CC ok', 'channelTitle' => 'Kanal A'], 'contentDetails' => ['duration' => 'PT1H2M'], 'status' => ['license' => 'creativeCommon', 'embeddable' => true, 'privacyStatus' => 'public']],
                ['id' => 'BBBBBBBBBBB', 'snippet' => ['title' => 'Standar', 'channelTitle' => 'B'], 'contentDetails' => ['duration' => 'PT5M'], 'status' => ['license' => 'youtube', 'embeddable' => true, 'privacyStatus' => 'public']],
                ['id' => 'CCCCCCCCCCC', 'snippet' => ['title' => 'Tak bisa embed', 'channelTitle' => 'C'], 'contentDetails' => ['duration' => 'PT5M'], 'status' => ['license' => 'creativeCommon', 'embeddable' => false, 'privacyStatus' => 'public']],
            ]]),
        ]);

        $res = app(YouTubeCcClient::class)->search('python pemula');
        $this->assertCount(1, $res);
        $this->assertSame('AAAAAAAAAAA', $res[0]['video_id']);
        $this->assertSame(62, $res[0]['minutes']);
        $this->assertSame('Kanal A', $res[0]['channel']);

        $this->artisan('belajar:ambil-youtube', ['--karier' => 'data-analyst', '--per' => 1])->assertSuccessful();
        $this->assertGreaterThan(0, \App\Models\Lesson::where('provider', 'youtube')->where('is_approved', false)->count());

        $this->assertSame('dQw4w9WgXcQ', YouTubeCcClient::parseId('https://youtu.be/dQw4w9WgXcQ?t=3'));
        $this->assertNull(YouTubeCcClient::parseId('bukan video'));
    }

    public function test_admin_tidak_bisa_menandai_lunas_pesanan_yang_sudah_ditolak(): void
    {
        // Verifikasi manual hanya berlaku tanpa gateway (dengan Midtrans, status diputuskan otomatis oleh gateway).
        config(['platform.payment.driver' => 'fake']);
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $mk = fn () => Order::create([
            'user_id' => $buyer->id, 'invoice_no' => 'INV-T-'.strtoupper(\Illuminate\Support\Str::random(5)),
            'item_type' => 'plan', 'item_id' => Plan::first()->id, 'amount' => 29000,
            'status' => 'pending', 'gateway' => 'midtrans', 'expires_at' => now()->addHour(), 'meta' => [],
        ]);

        $ditolak = $mk();
        $this->actingAs($admin)->post(route('admin.orders.reject', $ditolak))->assertSessionHas('status');
        $this->post(route('admin.orders.paid', $ditolak))->assertSessionHas('error');
        $this->assertSame('failed', $ditolak->fresh()->status);

        $lunas = $mk();
        $this->post(route('admin.orders.paid', $lunas))->assertSessionHas('status');
        $this->post(route('admin.orders.reject', $lunas))->assertSessionHas('error');
        $this->assertSame('paid', $lunas->fresh()->status);

        // Sudah lunas: status terkunci, ditandai lunas lagi pun ditolak.
        $this->post(route('admin.orders.paid', $lunas))->assertSessionHas('error');
        $this->assertSame('paid', $lunas->fresh()->status);
    }

    public function test_dengan_midtrans_admin_tidak_bisa_mengubah_status_pesanan_manual(): void
    {
        config(['platform.payment.driver' => 'midtrans']);
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $o = Order::create([
            'user_id' => $buyer->id, 'invoice_no' => 'INV-T-'.strtoupper(\Illuminate\Support\Str::random(5)),
            'item_type' => 'plan', 'item_id' => Plan::first()->id, 'amount' => 29000,
            'status' => 'pending', 'gateway' => 'midtrans', 'expires_at' => now()->addHour(), 'meta' => [],
        ]);

        $this->actingAs($admin)->post(route('admin.orders.paid', $o))->assertSessionHas('error');
        $this->post(route('admin.orders.reject', $o))->assertSessionHas('error');
        $this->assertSame('pending', $o->fresh()->status);
    }

    public function test_pembayaran_gagal_setelah_dipindai_tidak_menutup_tagihan_dan_tagihan_lewat_waktu_ditutup(): void
    {
        $buyer = User::factory()->create();
        $o = Order::create([
            'user_id' => $buyer->id, 'invoice_no' => 'INV-T-'.strtoupper(\Illuminate\Support\Str::random(5)),
            'item_type' => 'plan', 'item_id' => Plan::first()->id, 'amount' => 29000,
            'status' => 'pending', 'gateway' => 'midtrans', 'expires_at' => now()->addMinutes(30), 'meta' => [],
        ]);
        $settler = app(\App\Services\Billing\OrderSettler::class);

        // Dipindai tapi gagal (deny): tetap menunggu, hanya dicatat.
        $settler->apply($o, ['invoice_no' => $o->invoice_no, 'status' => 'pending', 'amount' => 29000, 'attempt' => 'failed']);
        $this->assertSame('pending', $o->fresh()->status);
        $this->assertNotEmpty($o->fresh()->meta['attempt_failed_at']);
        $this->assertFalse($settler->expireIfDue($o->fresh()));

        // Batas waktu lewat: tagihan ditutup, tidak ada akses diberikan.
        $o->update(['expires_at' => now()->subMinute()]);
        $this->assertTrue($settler->expireIfDue($o->fresh()));
        $this->assertSame('expired', $o->fresh()->status);

        // Hanya settlement yang dianggap lunas.
        $gw = new \App\Services\Billing\Gateways\MidtransGateway();
        config(['platform.payment.midtrans.server_key' => 'k']);
        $sig = fn ($st) => hash('sha512', $o->invoice_no.'200'.'29000.00'.'k');
        $deny = $gw->handleWebhook(['order_id' => $o->invoice_no, 'status_code' => '200', 'gross_amount' => '29000.00', 'transaction_status' => 'deny', 'signature_key' => $sig(1)], []);
        $this->assertSame('pending', $deny['status']);
        $this->assertSame('failed', $deny['attempt']);
    }
}
