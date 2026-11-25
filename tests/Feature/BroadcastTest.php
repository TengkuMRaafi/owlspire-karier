<?php

namespace Tests\Feature;

use App\Jobs\ProcessBroadcast;
use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Broadcast\BroadcastAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return $over + [
            'kind' => 'diskon', 'audience' => 'all', 'subject' => 'Diskon 30% Tes Premium', 'title' => 'Hemat 30%',
            'body' => "Selama promo, semua paket lebih murah.\n\nPakai kode di bawah.", 'highlight' => 'HEMAT30',
            'detail' => 'Berlaku sampai 22 November 2026', 'cta_label' => 'Beli sekarang', 'cta_url' => 'https://example.com/beli',
        ];
    }

    private function broadcast(array $over = []): EmailBroadcast
    {
        return EmailBroadcast::create($over + $this->payload() + ['status' => 'draft']);
    }

    public function test_hanya_admin_bisa_membuka_halaman_broadcast(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/broadcast')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'staff']))->get('/admin/broadcast')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/broadcast')->assertOk()->assertSee('Buat broadcast');
        $this->get('/admin/broadcast/buat')->assertOk()
            ->assertSee('Simpan draf')->assertSee('Kirim tes ke saya')->assertSee('Kirim ke semua');
    }

    public function test_penerima_hanya_akun_terverifikasi_yang_belum_berhenti(): void
    {
        User::factory()->create();
        User::factory()->unverified()->create();
        User::factory()->create(['broadcast_opt_out_at' => now()]);
        User::factory()->create(['suspended_at' => now()]);
        User::factory()->create(['role' => 'staff']);
        $premium = User::factory()->create();
        app(EntitlementService::class)->grantPremium($premium->id, 30);

        $audience = app(BroadcastAudience::class);
        $this->assertSame(2, $audience->count('all'));
        $this->assertSame(1, $audience->count('premium'));
        $this->assertSame(1, $audience->count('free'));
    }

    public function test_simpan_draf_tidak_mengirim_apa_pun(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/broadcast', $this->payload(['action' => 'draft']))->assertRedirect();

        $b = EmailBroadcast::firstOrFail();
        $this->assertSame('draft', $b->status);
        Queue::assertNothingPushed();
    }

    public function test_kirim_ke_semua_masuk_antrean_dan_tercatat(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();

        $this->actingAs($admin)->post('/admin/broadcast', $this->payload(['action' => 'send']))->assertRedirect('/admin/broadcast');

        $b = EmailBroadcast::firstOrFail();
        $this->assertSame('queued', $b->status);
        $this->assertSame(3, $b->recipients_count);
        $this->assertSame($admin->id, $b->user_id);
        Queue::assertPushed(ProcessBroadcast::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'broadcast.sent']);
    }

    public function test_broadcast_yang_sudah_dikirim_tidak_bisa_dikirim_atau_diubah_lagi(): void
    {
        Queue::fake();
        User::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $b = $this->broadcast();

        $this->put("/admin/broadcast/{$b->id}", $this->payload(['action' => 'send']))->assertRedirect('/admin/broadcast');
        $this->put("/admin/broadcast/{$b->id}", $this->payload(['action' => 'send', 'subject' => 'Diubah']))
            ->assertSessionHas('error');

        Queue::assertPushed(ProcessBroadcast::class, 1);
        $this->assertSame('Diskon 30% Tes Premium', $b->fresh()->subject);
    }

    public function test_antrean_mengirim_satu_email_per_penerima_lalu_selesai(): void
    {
        Mail::fake();
        User::factory()->count(2)->create();
        User::factory()->create(['broadcast_opt_out_at' => now()]);
        $b = $this->broadcast(['status' => 'queued', 'recipients_count' => 2]);

        // Antrean "sync" saat tes: tiap email langsung dikerjakan.
        (new ProcessBroadcast($b->id))->handle(app(BroadcastAudience::class));

        Mail::assertSent(BroadcastMail::class, 2);
        $b->refresh();
        $this->assertSame('sent', $b->status);
        $this->assertSame(2, $b->sent_count);
        $this->assertNotNull($b->sent_at);
    }

    public function test_email_tes_dikirim_ke_admin_sendiri(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/broadcast', $this->payload(['action' => 'test']))->assertRedirect();

        Mail::assertSent(BroadcastMail::class, fn ($m) => $m->hasTo($admin->email) && $m->isTest);
        $this->assertSame('draft', EmailBroadcast::firstOrFail()->status);
    }

    public function test_templat_email_memuat_logo_tagline_isi_dan_tautan_berhenti(): void
    {
        $b = $this->broadcast();
        $html = (new BroadcastMail($b, 'Raafi', 'https://owlspire.test/berhenti'))->render();

        $this->assertStringContainsString('images/email/owl-mark.png', $html);
        $this->assertStringContainsString('CAREER &amp; LEARNING FOR EVERYONE', $html);
        $this->assertStringContainsString('Halo, Raafi,', $html);
        $this->assertStringContainsString('HEMAT30', $html);
        $this->assertStringContainsString('Beli sekarang', $html);
        $this->assertStringContainsString('https://owlspire.test/berhenti', $html);
    }

    public function test_pratinjau_memakai_templat_yang_sama(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/broadcast/pratinjau', $this->payload())->assertOk()
            ->assertSee('Hemat 30%')->assertSee('KODE PROMO', false)->assertSee('Berhenti menerima info');
    }

    public function test_berhenti_menerima_info_lewat_tautan_bertanda_tangan(): void
    {
        $user = User::factory()->create();

        $this->get(BroadcastMail::unsubscribeUrlFor($user))->assertOk()->assertSee('Berhenti menerima info?');
        $this->get("/berhenti-info/{$user->id}")->assertForbidden();

        $this->post(URL::signedRoute('broadcast.unsubscribe.update', ['user' => $user->id]))->assertRedirect();
        $this->assertNotNull($user->fresh()->broadcast_opt_out_at);

        $this->post(URL::signedRoute('broadcast.unsubscribe.update', ['user' => $user->id]), ['aksi' => 'aktifkan'])->assertRedirect();
        $this->assertNull($user->fresh()->broadcast_opt_out_at);
    }
}
