<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CvVisual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pratinjau CV asli: gambar halaman PDF di kiri, catatan bertanda panah di kanan. */
class CvDocPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABQODxIPDRQSEBIXFRQYHjIhHhwcHj0sLiQySUBMS0dARkVQWnNiUFVtVkVGZIhlbXd7gYKBTmCNl4x9lnN+gXz/2wBDARUXFx4aHjshITt8U0ZTfHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHz/wAARCAFUAPADASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDs6KKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=';

    private const TEXT_CV = "PENDIDIKAN\nS1 Informatika 2020\nKEAHLIAN\nSQL, Excel, Python, Git\nPENGALAMAN\nMenganalisis data penjualan";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-DOC-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    private function ln(string $t, float $x, float $y, float $w = 0.3, float $h = 0.013): array
    {
        return ['t' => $t, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
    }

    private function layoutJson(): string
    {
        return json_encode(['total' => 1, 'pages' => [['lines' => [
            $this->ln('TAKEHIRO KANEGI', 0.50, 0.11, 0.35, 0.05), $this->ln('AUTHOR / PENULIS', 0.53, 0.25, 0.22, 0.017),
            $this->ln('+123-456-7890', 0.18, 0.36, 0.15, 0.015), $this->ln('hello@reallygreatsite.com', 0.18, 0.39, 0.25, 0.015),
            $this->ln('PROFIL', 0.56, 0.344, 0.09, 0.018),
            $this->ln('Memiliki bekal pendidikan di Borcelle', 0.51, 0.388, 0.31), $this->ln('University, jurusan Sastra Indonesia', 0.51, 0.41, 0.31),
            $this->ln('PENDIDIKAN', 0.20, 0.581, 0.165, 0.018), $this->ln('PENGALAMAN KERJA', 0.555, 0.583, 0.28, 0.018),
            $this->ln('(2018-2022)', 0.15, 0.625, 0.12), $this->ln('Universitas Borcelle', 0.15, 0.644, 0.19), $this->ln('Sastra Indonesia', 0.15, 0.669, 0.15),
            $this->ln('Penulis Freelancer (2020 - Sekarang)', 0.51, 0.626, 0.36), $this->ln('Menulis novel fiksi untuk dewasa dan', 0.53, 0.653, 0.31),
            $this->ln('KEMAMPUAN', 0.20, 0.821, 0.176, 0.018), $this->ln('Menulis kreatif.', 0.16, 0.864, 0.13), $this->ln('Editing.', 0.16, 0.881, 0.06),
        ]]]]);
    }

    private function upload(User $user)
    {
        return $this->actingAs($user)->post(route('cv.store'), [
            'cv_file' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'cv_layout' => $this->layoutJson(),
            'cv_pages' => ['data:image/jpeg;base64,'.self::JPEG],
        ]);
    }

    public function test_pdf_dibaca_browser_menampilkan_gambar_cv_dan_catatan_bernomor(): void
    {
        $user = $this->premiumUser();
        $this->upload($user)->assertRedirect(route('cv.index'));

        $html = $this->actingAs($user)->get(route('cv.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-cvd', $html);
        $this->assertStringContainsString('cvd-page', $html);
        $this->assertStringContainsString('/cv/halaman/1', $html);
        $this->assertStringContainsString('cvd-box', $html);
        $this->assertStringContainsString('Kontak lengkap', $html);
    }

    public function test_gambar_halaman_hanya_untuk_pemiliknya_dan_tersimpan_terenkripsi(): void
    {
        $user = $this->premiumUser();
        $this->upload($user);

        $this->assertStringNotContainsString(substr(self::JPEG, 0, 40), Storage::disk('local')->get('cv-visual/'.$user->id.'/page-1.enc'));
        $this->assertStringNotContainsString('hello@reallygreatsite.com', Storage::disk('local')->get('cv-visual/'.$user->id.'/layout.enc'));

        $res = $this->actingAs($user)->get(route('cv.page', ['n' => 1]))->assertOk();
        $this->assertSame('image/jpeg', $res->headers->get('Content-Type'));
        $this->actingAs($user)->get(route('cv.page', ['n' => 2]))->assertNotFound();

        // Pengguna lain tidak punya gambar sendiri: milik orang lain tidak bisa dilihat.
        $this->actingAs($this->premiumUser())->get(route('cv.page', ['n' => 1]))->assertNotFound();
    }

    public function test_teks_dua_kolom_diurutkan_sehingga_ringkasan_terbaca(): void
    {
        $user = $this->premiumUser();
        $this->upload($user);

        $lines = explode("\n", (string) CvProfile::where('user_id', $user->id)->value('raw_text'));
        $at = array_search('PROFIL', $lines);
        $this->assertLessThan($at, array_search('hello@reallygreatsite.com', $lines));
        $this->assertSame('Memiliki bekal pendidikan di Borcelle', $lines[$at + 1]);
    }

    public function test_analisis_baru_tanpa_gambar_menghapus_gambar_lama_dan_hapus_data_membersihkan(): void
    {
        $user = $this->premiumUser();
        $this->upload($user);
        $this->assertTrue(Storage::disk('local')->exists('cv-visual/'.$user->id.'/layout.enc'));

        $this->actingAs($user)->post(route('cv.store'), ['cv_text' => self::TEXT_CV]);
        $this->assertFalse(Storage::disk('local')->exists('cv-visual/'.$user->id.'/layout.enc'));
        $html = $this->actingAs($user)->get(route('cv.index'))->getContent();
        $this->assertStringNotContainsString('cvd-page', $html);
        $this->assertStringContainsString('cvp-cell', $html); // kembali ke pratinjau teks

        $this->upload($user);
        $this->actingAs($user)->delete(route('cv.destroy'))->assertRedirect();
        $this->assertFalse(Storage::disk('local')->exists('cv-visual/'.$user->id.'/page-1.enc'));
    }

    public function test_kiriman_tidak_wajar_diabaikan_tanpa_menggagalkan_analisis(): void
    {
        $user = $this->premiumUser();
        $this->actingAs($user)->post(route('cv.store'), [
            'cv_file' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n"),
            'cv_layout' => $this->layoutJson(),
            'cv_pages' => ['data:text/html;base64,'.base64_encode('<script>alert(1)</script>')],
        ])->assertRedirect();

        $this->assertNull(app(CvVisual::class)->load($user->id));
        $this->assertFalse(Storage::disk('local')->exists('cv-visual/'.$user->id.'/layout.enc'));
    }

    public function test_berkas_selain_pdf_tidak_menyimpan_gambar(): void
    {
        $user = $this->premiumUser();
        $this->actingAs($user)->post(route('cv.store'), [
            'cv_file' => UploadedFile::fake()->createWithContent('cv.txt', self::TEXT_CV),
            'cv_layout' => $this->layoutJson(),
            'cv_pages' => ['data:image/jpeg;base64,'.self::JPEG],
        ])->assertRedirect(route('cv.index'));

        $this->assertFalse(Storage::disk('local')->exists('cv-visual/'.$user->id.'/layout.enc'));
    }
}
