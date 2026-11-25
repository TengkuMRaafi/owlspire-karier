<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Banner promo melengkung di bawah navbar: angka dan teks bisa diatur admin. */
class PromoBannerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'learning_open' => '1', 'registration_open' => '1',
            'chatbot_free_daily' => 3, 'chatbot_premium_daily' => 99, 'interview_free_daily' => 2, 'interview_premium_daily' => 6,
            'cv_rewrite_free_daily' => 1, 'cv_rewrite_premium_daily' => 4, 'cover_letter_free_daily' => 1, 'cover_letter_premium_daily' => 4,
            'exam_pass_price' => 15000, 'exam_pass_valid_days' => 30, 'exam_trial_questions' => 7,
        ], $extra);
    }

    public function test_tanpa_diskon_dan_tanpa_pengaturan_manual_banner_tidak_tampil(): void
    {
        $this->get('/')->assertOk()->assertDontSee('pb-bar', false);
    }

    public function test_admin_mengisi_angka_dan_teks_banner_lalu_tampil_di_situs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.features'), $this->payload([
            'promo_enabled' => '1', 'promo_prefix' => 'Hingga', 'promo_number' => '75%', 'promo_suffix' => 'OFF',
            'promo_headline' => 'Diskon Kilat', 'promo_eyebrow' => 'Penawaran terbaik', 'promo_cta' => 'Beli sekarang', 'promo_tone' => 'violet',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('75%', app(SiteSettings::class)->promoBanner()['number']);

        auth()->logout();
        $this->get('/')->assertOk()
            ->assertSee('pb-bar', false)->assertSee('data-tone="violet"', false)
            ->assertSee('Diskon Kilat')->assertSee('75%')->assertSee('Beli sekarang');
    }

    public function test_banner_manual_dimatikan_kembali_ke_otomatis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.settings.features'), $this->payload(['promo_number' => '75%']))->assertSessionHasNoErrors();

        // Kolom terisi tetapi kotak centang mati: tidak ada diskon paket, jadi tidak ada banner.
        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('pb-bar', false);
    }

    public function test_banner_manual_yang_sudah_lewat_waktunya_tidak_tampil_dan_tanpa_tombol_tutup(): void
    {
        $site = app(SiteSettings::class);
        $site->put('promo_banner', ['enabled' => true, 'tone' => 'sunset', 'headline' => 'Diskon Kilat', 'number' => '75%', 'ends_at' => now()->addDay()->toIso8601String()]);
        $this->get('/')->assertOk()->assertSee('pb-bar', false)->assertSee('data-promo-countdown', false)->assertDontSee('data-promo-close', false);

        $site->put('promo_banner', ['enabled' => true, 'tone' => 'sunset', 'headline' => 'Diskon Kilat', 'number' => '75%', 'ends_at' => now()->subHour()->toIso8601String()]);
        $this->get('/')->assertOk()->assertDontSee('pb-bar', false);
    }
}
