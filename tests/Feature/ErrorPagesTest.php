<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Rute uji: memicu abort dengan kode yang diminta.
        Route::get('/uji-error/{code}', fn (int $code) => abort($code, '', $code === 429 ? ['Retry-After' => 25] : []));
    }

    public static function halaman(): array
    {
        return [
            '403' => [403, 'Gerbang ini terkunci'],
            '404' => [404, 'Tersesat'],
            '405 (4xx umum)' => [405, 'Cara aksesnya keliru'],
            '408 (4xx umum)' => [408, 'Koneksi kelamaan menunggu'],
            '413' => [413, 'Berkasnya kebesaran'],
            '419' => [419, 'Sesimu sudah habis'],
            '429' => [429, 'Pelan-pelan dulu ya'],
            '500' => [500, 'Server lagi kami perbaiki'],
            '502 (5xx umum)' => [502, 'Server belum menjawab dengan benar'],
            '503' => [503, 'Sedang dirapikan'],
        ];
    }

    #[DataProvider('halaman')]
    public function test_setiap_kode_memakai_halaman_bermaskot(int $kode, string $teks): void
    {
        $this->get('/uji-error/'.$kode)
            ->assertStatus($kode)
            ->assertSee($teks)
            ->assertSee('Owlspire')
            ->assertSee('er-svg', false);
    }

    public function test_permintaan_json_tetap_json(): void
    {
        $this->getJson('/uji-error/403')->assertForbidden()->assertJsonMissing(['Gerbang ini terkunci']);
        $this->getJson('/uji-error/500')->assertStatus(500);
    }

    public function test_429_membawa_lama_tunggu_dari_retry_after(): void
    {
        $this->get('/uji-error/429')->assertStatus(429)->assertSee('data-er-wait="25"', false);
    }

    public function test_halaman_statis_untuk_nginx_tersedia(): void
    {
        foreach (['502', '503', '504'] as $kode) {
            $this->assertFileExists(public_path("errors/{$kode}.html"));
        }
        $this->assertFileExists(public_path('errors/terlalu-besar.html'));
    }

    public function test_setiap_kode_5xx_punya_adegan_sendiri(): void
    {
        $this->get('/uji-error/502')->assertSee('e502-cable', false)->assertDontSee('e500-arm', false);
        $this->get('/uji-error/504')->assertSee('e504-hg', false)->assertDontSee('e500-arm', false);
        $this->get('/uji-error/500')->assertSee('e500-arm', false);
    }

    public function test_halaman_statis_punya_adegan_sendiri(): void
    {
        foreach (['502' => 'e502-cable', '503' => 'e503b-pk', '504' => 'e504-hg'] as $kode => $kelas) {
            $html = file_get_contents(public_path("errors/{$kode}.html"));
            $this->assertStringContainsString($kelas, $html);
            $this->assertStringNotContainsString('class="e500-arm"', $html);
        }
    }
}
