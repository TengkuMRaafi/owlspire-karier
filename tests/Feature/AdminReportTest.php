<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_menu_laporan_ada_dan_impor_soal_tidak_lagi_di_sidebar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.reports.index'), $html);
        $this->assertStringNotContainsString('href="'.route('admin.questions.import').'"', $html);
        $this->assertStringNotContainsString('Tambah soal', $html);
        $this->assertStringContainsString('ui-linkbtn', $html);

        // Impor tetap dibuka dari tombol di halaman Bank soal.
        $this->actingAs($admin)->get(route('admin.questions.index'))->assertOk()->assertSee(route('admin.questions.import'), false);
    }

    public function test_laporan_admin_memuat_pesanan_dan_bisa_diunduh(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.reports.index', ['periode' => '7']))->assertOk()
            ->assertSee('Ujian seleksi')->assertSee('Karier')->assertSee('Pesanan');
        $res = $this->actingAs($admin)->get(route('admin.reports.download'))->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $res->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $res->headers->get('content-disposition'));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($res->baseResponse->getFile()->getPathname()));
        $book = $zip->getFromName('xl/workbook.xml');
        foreach (['Ringkasan', 'Ujian Seleksi', 'Karier', 'Pesanan'] as $name) {
            $this->assertStringContainsString($name, $book);
        }
        $this->assertStringContainsString('LAPORAN OWLSPIRE', $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
    }

    public function test_staf_melihat_laporan_tanpa_uang_dan_member_ditolak(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('admin.reports.index'))->assertOk()->assertDontSee('Pendapatan')->assertDontSee('>Pesanan<', false);

        $zip = new \ZipArchive();
        $res = $this->actingAs($staff)->get(route('admin.reports.download'))->assertOk();
        $zip->open($res->baseResponse->getFile()->getPathname());
        $this->assertStringNotContainsString('Pesanan', $zip->getFromName('xl/workbook.xml'));
        $zip->close();

        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_dashboard_membatasi_tiga_data_terbaru(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 5) as $i) {
            Order::create(['user_id' => $admin->id, 'invoice_no' => 'INV-T'.$i, 'item_type' => 'plan', 'amount' => 1000, 'status' => 'paid', 'paid_at' => now()]);
        }
        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        preg_match_all('/INV-T\d/', $html, $m);
        $this->assertCount(3, array_unique($m[0]));
    }
}
