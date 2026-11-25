<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatUsage;
use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Asisten AI di dashboard: akses login, kuota harian Gratis vs Premium, dan kegagalan Groq tidak memakan kuota. */
class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.key' => 'test-key', 'platform.chatbot.free_daily' => 2, 'platform.chatbot.premium_daily' => 4]);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Halo, ini jawaban **uji**.']]]])]);
    }

    private function premium(User $u): void
    {
        Entitlement::create(['user_id' => $u->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);
    }

    public function test_tamu_diarahkan_masuk(): void
    {
        $this->get(route('account.chat'))->assertRedirect(route('login'));
        $this->postJson(route('account.chat.send'), ['message' => 'Halo'])->assertUnauthorized();
    }

    public function test_halaman_chat_tampil_dengan_sisa_kuota(): void
    {
        $this->actingAs(User::factory()->create())->get(route('account.chat'))
            ->assertOk()->assertSee('Asisten AI')->assertSee('Sisa pesan hari ini');
    }

    public function test_pesan_dijawab_dan_disimpan_tanpa_markdown(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Halo'])
            ->assertOk()->assertJson(['answer' => 'Halo, ini jawaban uji.', 'remaining' => 1, 'limit' => 2]);

        $this->assertSame(2, ChatMessage::where('user_id', $u->id)->count());
    }

    public function test_pengguna_gratis_dibatasi_kuota_harian(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->postJson(route('account.chat.send'), ['message' => 'Satu'])->assertOk();
        $this->postJson(route('account.chat.send'), ['message' => 'Dua'])->assertOk();
        $this->postJson(route('account.chat.send'), ['message' => 'Tiga'])->assertStatus(429)->assertJson(['remaining' => 0]);
    }

    public function test_premium_mendapat_kuota_lebih_besar(): void
    {
        $u = User::factory()->create();
        $this->premium($u);
        $this->actingAs($u);
        foreach (['a', 'b', 'c', 'd'] as $m) {
            $this->postJson(route('account.chat.send'), ['message' => $m])->assertOk();
        }
        $this->postJson(route('account.chat.send'), ['message' => 'e'])->assertStatus(429);
    }

    public function test_kuota_kemarin_tidak_dihitung(): void
    {
        $u = User::factory()->create();
        ChatMessage::create(['user_id' => $u->id, 'role' => 'user', 'content' => 'lama', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
        ChatMessage::create(['user_id' => $u->id, 'role' => 'user', 'content' => 'lama 2', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Baru'])->assertOk();
    }

    public function test_groq_gagal_tidak_memakan_kuota(): void
    {
        Http::swap(new Factory); // buang fake bawaan setUp, karena fake pertama yang cocok yang dipakai
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'down'], 500)]);
        $u = User::factory()->create();

        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Halo'])
            ->assertStatus(503)->assertJson(['remaining' => 2]);
        $this->assertSame(0, ChatMessage::where('user_id', $u->id)->count());
    }

    public function test_tanpa_kunci_groq_asisten_nonaktif(): void
    {
        config(['services.groq.key' => null]);
        $this->actingAs(User::factory()->create())->postJson(route('account.chat.send'), ['message' => 'Halo'])->assertStatus(503);
    }

    public function test_pesan_kosong_atau_terlalu_panjang_ditolak(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('account.chat.send'), ['message' => ''])->assertStatus(422);
        $this->postJson(route('account.chat.send'), ['message' => str_repeat('a', 501)])->assertStatus(422);
    }

    public function test_riwayat_tiap_pengguna_terpisah_dan_bisa_dihapus(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        ChatMessage::create(['user_id' => $b->id, 'role' => 'user', 'content' => 'rahasia milik B']);

        $this->actingAs($a)->postJson(route('account.chat.send'), ['message' => 'pesan A'])->assertOk();
        $this->get(route('account.chat'))->assertSee('pesan A')->assertDontSee('rahasia milik B');

        $this->delete(route('account.chat.clear'))->assertRedirect(route('account.chat'));
        $this->assertSame(0, ChatMessage::where('user_id', $a->id)->count());
        $this->assertSame(1, ChatMessage::where('user_id', $b->id)->count());
    }

    public function test_hapus_riwayat_tidak_mengembalikan_kuota(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->postJson(route('account.chat.send'), ['message' => 'Satu'])->assertOk();
        $this->postJson(route('account.chat.send'), ['message' => 'Dua'])->assertOk();

        $this->delete(route('account.chat.clear'))->assertRedirect(route('account.chat'));
        $this->assertSame(0, ChatMessage::where('user_id', $u->id)->count());

        $this->postJson(route('account.chat.send'), ['message' => 'Tiga'])->assertStatus(429)->assertJson(['remaining' => 0]);
    }

    public function test_pemakaian_kemarin_tidak_dihitung_hari_ini(): void
    {
        $u = User::factory()->create();
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->subDay()->toDateString(), 'count' => 2]);

        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Baru'])->assertOk()->assertJson(['remaining' => 1]);
    }

    private function pesan(User $u, string $role, string $isi, $waktu): ChatMessage
    {
        return ChatMessage::create(['user_id' => $u->id, 'role' => $role, 'content' => $isi, 'created_at' => $waktu, 'updated_at' => $waktu]);
    }

    public function test_halaman_chat_hanya_menampilkan_percakapan_hari_ini(): void
    {
        $u = User::factory()->create();
        $this->pesan($u, 'user', 'pertanyaan kemarin', now()->subDay());
        $this->pesan($u, 'user', 'pertanyaan hari ini', now());

        $this->actingAs($u)->get(route('account.chat'))
            ->assertSee('pertanyaan hari ini')->assertDontSee('pertanyaan kemarin');
    }

    public function test_konteks_ai_tidak_memuat_percakapan_hari_lain(): void
    {
        $u = User::factory()->create();
        $this->pesan($u, 'user', 'rahasia kemarin', now()->subDay());

        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Halo'])->assertOk();

        Http::assertSent(fn ($req) => ! str_contains(json_encode($req->data()), 'rahasia kemarin'));
    }

    public function test_arsip_menampilkan_hari_lalu_dengan_jumlah_pertanyaan(): void
    {
        $u = User::factory()->create();
        $kemarin = now()->subDay();
        $this->pesan($u, 'user', 'cara belajar TWK', $kemarin);
        $this->pesan($u, 'assistant', 'jawaban TWK', $kemarin);
        $this->pesan($u, 'user', 'pertanyaan hari ini', now());

        $this->actingAs($u)->get(route('account.chat.days'))
            ->assertOk()->assertSee('1 pertanyaan')->assertSee('cara belajar TWK')->assertDontSee('pertanyaan hari ini');

        $this->get(route('account.chat.day', $kemarin->toDateString()))
            ->assertOk()->assertSee('cara belajar TWK')->assertSee('jawaban TWK');
    }

    public function test_arsip_tiap_pengguna_terpisah(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $kemarin = now()->subDay();
        $this->pesan($b, 'user', 'rahasia milik B', $kemarin);

        $this->actingAs($a)->get(route('account.chat.days'))->assertDontSee('rahasia milik B');
        $this->get(route('account.chat.day', $kemarin->toDateString()))->assertNotFound();
        $this->delete(route('account.chat.day.clear', $kemarin->toDateString()));
        $this->assertSame(1, ChatMessage::where('user_id', $b->id)->count());
    }

    public function test_tanggal_hari_ini_atau_tidak_valid_ditolak(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('account.chat.day', now()->toDateString()))->assertNotFound();
        $this->get('/dashboard/asisten/riwayat/2026-02-31')->assertNotFound();
    }

    public function test_hapus_satu_hari_tidak_menyentuh_hari_lain_dan_kuota(): void
    {
        $u = User::factory()->create();
        $kemarin = now()->subDay();
        $this->pesan($u, 'user', 'kemarin', $kemarin);
        $this->pesan($u, 'user', 'dua hari lalu', now()->subDays(2));
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->toDateString(), 'count' => 1]);

        $this->actingAs($u)->delete(route('account.chat.day.clear', $kemarin->toDateString()))
            ->assertRedirect(route('account.chat.days'));

        $this->assertSame(['dua hari lalu'], ChatMessage::where('user_id', $u->id)->pluck('content')->all());
        $this->assertSame(1, ChatUsage::where('user_id', $u->id)->value('count'));
    }

    public function test_hapus_riwayat_di_halaman_chat_hanya_hari_ini(): void
    {
        $u = User::factory()->create();
        $this->pesan($u, 'user', 'kemarin', now()->subDay());
        $this->pesan($u, 'user', 'hari ini', now());

        $this->actingAs($u)->delete(route('account.chat.clear'))->assertRedirect(route('account.chat'));

        $this->assertSame(['kemarin'], ChatMessage::where('user_id', $u->id)->pluck('content')->all());
    }
}
