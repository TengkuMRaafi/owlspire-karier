<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\ChatMessage;
use App\Models\CvProfile;
use App\Models\Entitlement;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\User;
use App\Services\Account\Preferences;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Pengaturan akun: profil, karier target, preferensi per menu (dan efeknya), keamanan, serta data dan akun. */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function career(string $name = 'Data Analyst'): Career
    {
        return Career::create(['slug' => str($name)->slug(), 'name' => $name, 'division' => 'Data', 'summary' => 'Ringkasan', 'rank' => 1]);
    }

    private function save(User $u, string $group, array $data = [])
    {
        return $this->actingAs($u)->put(route('account.settings.preferences', $group), $data);
    }

    // ---------------------------------------------------------------- halaman

    public function test_tamu_diarahkan_masuk(): void
    {
        $this->get(route('account.settings'))->assertRedirect(route('login'));
        $this->put(route('account.settings.preferences', 'exam'), [])->assertRedirect(route('login'));
        $this->delete(route('account.settings.destroy'))->assertRedirect(route('login'));
    }

    public function test_halaman_memuat_semua_bagian(): void
    {
        $this->actingAs(User::factory()->create())->get(route('account.settings'))
            ->assertOk()
            ->assertSee('Profil')->assertSee('Karier target')->assertSee('Keamanan')
            ->assertSee('Simulasi')->assertSee('Belajar')->assertSee('Asisten AI')
            ->assertSee('Notifikasi')->assertSee('Dashboard')->assertSee('Privasi')
            ->assertSee('Data dan akun')->assertSee('Hapus akun');
    }

    public function test_halaman_memakai_navigasi_tab_dan_dropdown_kustom(): void
    {
        $this->actingAs(User::factory()->create())->get(route('account.settings'))
            ->assertOk()
            ->assertSee('data-set-tab="dashboard"', false)
            ->assertSee('data-dd', false)
            ->assertDontSee('<select', false);
    }

    // ---------------------------------------------------------------- profil dan karier

    public function test_profil_menyimpan_nama_dan_keterangan(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put(route('account.settings.profile'), ['name' => '  Budi Santoso ', 'headline' => 'Fresh graduate TI'])
            ->assertRedirect(route('account.settings').'#profil');

        $this->assertSame('Budi Santoso', $u->fresh()->name);
        $this->assertSame('Fresh graduate TI', $u->fresh()->headline);
    }

    public function test_profil_menolak_nama_kosong_dan_keterangan_terlalu_panjang(): void
    {
        $u = User::factory()->create(['name' => 'Asli']);
        $this->actingAs($u)->put(route('account.settings.profile'), ['name' => '', 'headline' => str_repeat('a', 121)])
            ->assertSessionHasErrors(['name', 'headline']);
        $this->assertSame('Asli', $u->fresh()->name);
    }

    public function test_karier_target_bisa_dipasang_dan_dihapus(): void
    {
        $c = $this->career();
        $u = User::factory()->create();

        $this->actingAs($u)->put(route('account.settings.career'), ['career_id' => $c->id])->assertSessionHasNoErrors();
        $this->assertSame($c->id, $u->fresh()->target_career_id);

        $this->put(route('account.settings.career'), ['career_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull($u->fresh()->target_career_id);

        $this->put(route('account.settings.career'), ['career_id' => 9999])->assertSessionHasErrors('career_id');
    }

    // ---------------------------------------------------------------- preferensi

    public function test_preferensi_tersimpan_dan_dibaca_kembali(): void
    {
        $u = User::factory()->create();
        $this->assertSame('always', $u->pref('exam.timer')); // bawaan

        $this->save($u, 'exam', ['timer' => 'last5', 'confirm_finish' => 'unanswered', 'auto_next' => '1', 'large_text' => '0'])
            ->assertRedirect(route('account.settings').'#exam');

        $u = $u->fresh();
        $this->assertSame('last5', $u->pref('exam.timer'));
        $this->assertSame('unanswered', $u->pref('exam.confirm_finish'));
        $this->assertTrue($u->pref('exam.auto_next'));
        $this->assertFalse($u->pref('exam.large_text'));
    }

    public function test_saklar_yang_tidak_dikirim_dianggap_mati(): void
    {
        $u = User::factory()->create();
        $this->save($u, 'notifications', ['billing' => '1']);

        $this->assertTrue($u->fresh()->pref('notifications.billing'));
        $this->assertFalse($u->fresh()->pref('notifications.exam_running'));
    }

    public function test_pilihan_di_luar_skema_ditolak(): void
    {
        $u = User::factory()->create();
        $this->save($u, 'exam', ['timer' => 'hilang', 'confirm_finish' => 'always'])->assertSessionHasErrors('timer');
        $this->save($u, 'learning', ['weekly_goal' => 99])->assertSessionHasErrors('weekly_goal');
        $this->assertNull($u->fresh()->preferences);
    }

    public function test_grup_tidak_dikenal_menghasilkan_404(): void
    {
        $this->save(User::factory()->create(), 'ngawur', ['x' => 1])->assertNotFound();
    }

    public function test_grup_bisa_dikembalikan_ke_bawaan(): void
    {
        $u = User::factory()->create();
        $this->save($u, 'exam', ['timer' => 'last5', 'confirm_finish' => 'always']);
        $this->save($u, 'assistant', ['style' => 'detail', 'tone' => 'formal']);

        $this->save($u, 'exam', ['reset' => '1']);

        $u = $u->fresh();
        $this->assertSame('always', $u->pref('exam.timer'));
        $this->assertSame('detail', $u->pref('assistant.style')); // grup lain tidak ikut terhapus
    }

    public function test_nilai_rusak_di_database_jatuh_ke_bawaan(): void
    {
        $u = User::factory()->create(['preferences' => ['exam' => ['timer' => ['bukan', 'skalar'], 'auto_next' => 'ya']]]);

        $this->assertSame('always', $u->pref('exam.timer'));
        $this->assertFalse($u->pref('exam.auto_next'));
        $this->assertSame('always', app(Preferences::class)->get(null, 'exam.timer')); // tamu
    }

    // ---------------------------------------------------------------- efek ke menu lain

    public function test_ruang_ujian_membawa_preferensi_pengguna(): void
    {
        $this->seed(ExamSeeder::class);
        $u = User::factory()->create(['preferences' => ['exam' => ['timer' => 'last5', 'large_text' => true]]]);

        $this->actingAs($u)->post('/simulasi/cpns/mulai', ['mode' => 'trial'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();

        $this->get("/simulasi/ujian/{$attempt->id}")->assertOk()
            ->assertSee('xr-lg', false)
            ->assertSee('"timer":"last5"', false);
    }

    public function test_ruang_ujian_tamu_memakai_nilai_bawaan(): void
    {
        $this->seed(ExamSeeder::class);
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();

        $this->get("/simulasi/ujian/{$attempt->id}")->assertOk()
            ->assertDontSee('xr-lg', false)
            ->assertSee('"timer":"always"', false);
    }

    public function test_asisten_ai_mengikuti_gaya_dan_privasi_profil(): void
    {
        config(['services.groq.key' => 'k']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Jawaban.']]]])]);
        $c = $this->career('Analis Data');
        $u = User::factory()->create(['name' => 'Rahasia Nama', 'headline' => 'Lulusan Statistika', 'target_career_id' => $c->id]);

        // Bawaan: profil ikut dikirim, gaya ringkas.
        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Halo'])->assertOk();
        Http::assertSent(function ($r) {
            $system = $r['messages'][0]['content'];

            return str_contains($system, 'Rahasia Nama') && str_contains($system, 'Lulusan Statistika')
                && str_contains($system, 'Analis Data') && ! str_contains($system, 'rinci') && ! str_contains($system, 'formal');
        });

        // Profil dimatikan, gaya rinci dan formal.
        $this->save($u, 'assistant', ['style' => 'detail', 'tone' => 'formal', 'use_profile' => '0']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Jawaban.']]]])]);
        $this->postJson(route('account.chat.send'), ['message' => 'Halo lagi'])->assertOk();
        Http::assertSent(function ($r) {
            $system = $r['messages'][0]['content'];

            return ! str_contains($system, 'Rahasia Nama') && ! str_contains($system, 'Analis Data')
                && str_contains($system, 'rinci') && str_contains($system, 'formal')
                && $r['max_completion_tokens'] > (int) config('platform.chatbot.max_tokens');
        });
    }

    public function test_pengguna_bisa_menyembunyikan_diri_dari_papan_peringkat(): void
    {
        $this->seed(ExamSeeder::class);
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        $shown = User::factory()->create(['name' => 'Tampilkan Saya']);
        $hidden = User::factory()->create(['name' => 'Sembunyikan Saya', 'preferences' => ['privacy' => ['leaderboard' => false]]]);

        foreach ([$shown, $hidden] as $u) {
            ExamAttempt::create(['user_id' => $u->id, 'exam_type_id' => $type->id, 'mode' => 'full', 'started_at' => now()->subHour(),
                'deadline_at' => now()->addHour(), 'finished_at' => now(), 'total_score' => 400, 'passed' => true]);
        }

        $this->get(route('exam.leaderboard', ['jenis' => 'cpns']))->assertOk()
            ->assertSee('Tampilkan')->assertDontSee('Sembunyikan');
    }

    public function test_lonceng_mengikuti_pilihan_notifikasi(): void
    {
        $u = User::factory()->create();
        Order::create(['user_id' => $u->id, 'invoice_no' => 'INV-T1', 'item_type' => 'plan', 'amount' => 29000, 'status' => 'pending']);
        Entitlement::create(['user_id' => $u->id, 'type' => 'subscription', 'expires_at' => now()->addDays(3)]);

        $this->actingAs($u)->get(route('dashboard.index'))->assertOk()
            ->assertSee('Ada tagihan yang belum dibayar')->assertSee('Premium berakhir');

        $this->save($u, 'notifications', ['exam_running' => '1']); // tagihan, ulang, dan premium dimatikan
        $this->get(route('dashboard.index'))->assertOk()
            ->assertDontSee('Ada tagihan yang belum dibayar')->assertDontSee('Premium berakhir');
    }

    public function test_bagian_dashboard_bisa_disembunyikan(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get(route('dashboard.index'))
            ->assertSee('Riwayat terakhir')->assertSee('Pintasan', false)->assertSee('Pilih karier target');

        $this->save($u, 'dashboard', []); // semua saklar mati
        $this->get(route('dashboard.index'))->assertOk()
            ->assertDontSee('Riwayat terakhir')->assertDontSee('Pintasan', false)->assertDontSee('Pilih karier target')
            ->assertSee('Ujian selesai'); // angka ringkasan selalu tampil
    }

    public function test_target_belajar_mingguan_tampil_di_halaman_belajar(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get(route('account.learning'))->assertOk()->assertSee('Belum ada target belajar mingguan');

        $this->save($u, 'learning', ['weekly_goal' => '3']);
        $this->get(route('account.learning'))->assertOk()->assertSee('Target minggu ini')->assertSee('0/3 topik');
    }

    // ---------------------------------------------------------------- keamanan

    public function test_ganti_kata_sandi_butuh_sandi_lama(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put(route('account.settings.password'), ['current_password' => 'salah', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertSessionHasErrors('current_password');

        $this->put(route('account.settings.password'), ['current_password' => 'password', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertSessionHasNoErrors()->assertRedirect(route('account.settings').'#keamanan');
        $this->assertTrue(\Hash::check('baru-12345', $u->fresh()->password));
    }

    public function test_keluar_dari_perangkat_lain_menghapus_sesi_lain_saja(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create(['remember_token' => 'lama']);
        $other = User::factory()->create();
        foreach (['perangkat-a', 'perangkat-b'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $u->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => now()->getTimestamp()]);
        }
        DB::table('sessions')->insert(['id' => 'milik-orang-lain', 'user_id' => $other->id, 'ip_address' => '2.2.2.2', 'user_agent' => '', 'payload' => '', 'last_activity' => now()->getTimestamp()]);

        $this->actingAs($u)->post(route('account.settings.devices.logout'), ['current_password' => 'salah'])->assertSessionHasErrors('current_password');
        $this->assertSame(3, DB::table('sessions')->where('user_id', $u->id)->count()); // 2 perangkat lain + sesi ini

        $this->post(route('account.settings.devices.logout'), ['current_password' => 'password'])->assertSessionHasNoErrors();

        $this->assertNull(DB::table('sessions')->where('id', 'perangkat-a')->first());
        $this->assertNull(DB::table('sessions')->where('id', 'perangkat-b')->first());
        $this->assertNotNull(DB::table('sessions')->where('id', 'milik-orang-lain')->first());
        $this->assertNotSame('lama', $u->fresh()->remember_token);
    }

    public function test_keluar_dari_perangkat_lain_menyisakan_sesi_yang_dipakai(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create();
        foreach (['aktif', 'lain-1', 'lain-2'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $u->id, 'ip_address' => '1.1.1.1', 'user_agent' => '', 'payload' => '', 'last_activity' => now()->getTimestamp()]);
        }

        $deleted = app(\App\Services\Account\SessionManager::class)->revokeOthers($u, 'aktif');

        $this->assertSame(2, $deleted);
        $this->assertSame(['aktif'], DB::table('sessions')->where('user_id', $u->id)->pluck('id')->all());
    }

    public function test_daftar_perangkat_menampilkan_peramban_dan_sistem_operasi(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'ponsel', 'user_id' => $u->id, 'ip_address' => '3.3.3.3', 'payload' => '', 'last_activity' => now()->getTimestamp(),
            'user_agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36']);

        $this->actingAs($u)->get(route('account.settings'))->assertOk()->assertSee('Chrome di Android')->assertSee('3.3.3.3');
    }

    // ---------------------------------------------------------------- data dan akun

    public function test_unduh_data_berisi_milik_pengguna_saja(): void
    {
        $u = User::factory()->create(['email' => 'saya@contoh.test', 'headline' => 'Pelamar CPNS']);
        $other = User::factory()->create(['email' => 'lain@contoh.test']);
        ChatMessage::create(['user_id' => $u->id, 'role' => 'user', 'content' => 'Pertanyaan saya']);
        ChatMessage::create(['user_id' => $other->id, 'role' => 'user', 'content' => 'Pertanyaan orang lain']);

        $res = $this->actingAs($u)->get(route('account.settings.export'))->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('content-disposition'));

        $json = json_decode($res->streamedContent(), true);
        $this->assertSame('saya@contoh.test', $json['profil']['email']);
        $this->assertSame('Pelamar CPNS', $json['profil']['keterangan']);
        $this->assertSame(['Pertanyaan saya'], array_column($json['chat_asisten'], 'isi'));
        $this->assertArrayHasKey('exam', $json['preferensi']);
        $this->assertStringNotContainsString('lain@contoh.test', $res->streamedContent());
    }

    public function test_hapus_riwayat_chat_dan_cv(): void
    {
        $u = User::factory()->create();
        $other = User::factory()->create();
        ChatMessage::create(['user_id' => $u->id, 'role' => 'user', 'content' => 'a']);
        ChatMessage::create(['user_id' => $other->id, 'role' => 'user', 'content' => 'b']);
        CvProfile::create(['user_id' => $u->id, 'raw_text' => 'isi cv', 'parsed' => ['skills' => []]]);

        $this->actingAs($u)->delete(route('account.settings.chat.clear'))->assertRedirect(route('account.settings').'#data');
        $this->delete(route('account.settings.cv.clear'))->assertRedirect(route('account.settings').'#data');

        $this->assertSame(0, ChatMessage::where('user_id', $u->id)->count());
        $this->assertSame(1, ChatMessage::where('user_id', $other->id)->count());
        $this->assertSame(0, CvProfile::where('user_id', $u->id)->count());
    }

    public function test_hapus_akun_butuh_sandi_dan_centang(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->delete(route('account.settings.destroy'), ['current_password' => 'salah', 'confirm' => '1'])->assertSessionHasErrors('current_password');
        $this->delete(route('account.settings.destroy'), ['current_password' => 'password'])->assertSessionHasErrors('confirm');
        $this->assertNotNull($u->fresh());
    }

    public function test_hapus_akun_membersihkan_data_dan_menyisakan_pesanan_tanpa_pemilik(): void
    {
        $u = User::factory()->create();
        ChatMessage::create(['user_id' => $u->id, 'role' => 'user', 'content' => 'a']);
        $order = Order::create(['user_id' => $u->id, 'invoice_no' => 'INV-HAPUS', 'item_type' => 'plan', 'amount' => 29000, 'status' => 'paid']);

        $this->actingAs($u)->delete(route('account.settings.destroy'), ['current_password' => 'password', 'confirm' => '1'])
            ->assertRedirect(route('home'));

        $this->assertNull(User::find($u->id));
        $this->assertSame(0, ChatMessage::where('user_id', $u->id)->count());
        $this->assertNull($order->fresh()->user_id);
        $this->assertGuest();
    }

    public function test_akun_admin_tidak_bisa_dihapus_dari_sini(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin)->delete(route('account.settings.destroy'), ['current_password' => 'password', 'confirm' => '1'])->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }
}