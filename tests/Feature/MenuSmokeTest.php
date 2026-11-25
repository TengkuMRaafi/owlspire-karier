<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\User;
use Database\Seeders\CareerSeeder;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Sekali jalan, kelihatan menu mana yang rusak. Daftar menu dibaca dari config/panel.php, jadi menu baru
 * otomatis ikut dites. Semua kegagalan dikumpulkan dan dilaporkan sekaligus (bukan berhenti di yang pertama).
 */
class MenuSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ExamSeeder::class, CareerSeeder::class]);
    }

    private function items(string $area): array
    {
        return collect(config("panel.{$area}.groups"))
            ->flatMap(fn ($g) => collect($g['items'])->map(fn ($i) => $i + ['group' => $g['label']]))
            ->all();
    }

    /** Buka tiap URL; kembalikan daftar yang statusnya tidak sesuai ($ok dipanggil dengan status). */
    private function probe(?User $user, array $targets, callable $ok): array
    {
        $bad = [];
        foreach ($targets as $name => $url) {
            $res = ($user ? $this->actingAs($user) : $this)->get($url);
            if (! $ok($res->getStatusCode(), $name)) {
                $bad[] = "{$name} ({$url}) -> {$res->getStatusCode()}";
            }
        }

        return $bad;
    }

    private function assertNoneBroken(array $bad, string $who): void
    {
        $this->assertSame([], $bad, "Menu bermasalah untuk {$who}:\n - ".implode("\n - ", $bad));
    }

    private function adminTargets(): array
    {
        $t = [];
        foreach ($this->items('admin') as $i) {
            $t["[{$i['group']}] {$i['label']}"] = route($i['route']);
        }

        return $t;
    }

    public function test_halaman_publik_dan_navbar_terbuka_untuk_tamu(): void
    {
        $paths = ['/', '/arah-karier', '/belajar', '/lowongan', '/simulasi', '/simulasi/peringkat', '/harga', '/info-seleksi', '/masuk', '/daftar'];
        $bad = $this->probe(null, array_combine($paths, $paths), fn ($s) => $s === 200);

        $this->assertNoneBroken($bad, 'tamu (halaman publik)');
    }

    public function test_menu_yang_butuh_login_mengalihkan_tamu_ke_halaman_masuk(): void
    {
        $bad = [];
        foreach (['/cv', '/dashboard', '/admin'] as $path) {
            $res = $this->get($path);
            if ($res->getStatusCode() !== 302 || ! str_ends_with((string) $res->headers->get('Location'), '/masuk')) {
                $bad[] = "{$path} -> {$res->getStatusCode()} ".$res->headers->get('Location');
            }
        }

        $this->assertNoneBroken($bad, 'tamu (halaman yang dikunci login)');
    }

    public function test_admin_bisa_membuka_semua_menu_sidebar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertNoneBroken($this->probe($admin, $this->adminTargets(), fn ($s) => $s === 200), 'admin (sidebar)');
    }

    public function test_admin_bisa_membuka_semua_halaman_get_tanpa_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $targets = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (str_starts_with($name, 'admin.') && in_array('GET', $route->methods(), true) && ! str_contains($route->uri(), '{')) {
                $targets[$name] = '/'.ltrim($route->uri(), '/');
            }
        }
        $this->assertNotEmpty($targets);

        $this->assertNoneBroken($this->probe($admin, $targets, fn ($s) => $s < 400), 'admin (semua halaman GET, termasuk tambah/impor)');
    }

    public function test_staf_hanya_bisa_membuka_menu_sesuai_area_dan_sidebarnya_cocok(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $bad = [];
        foreach ($this->items('admin') as $i) {
            $allowed = empty($i['area']) || $staff->canAccess($i['area']);
            $url = route($i['route']);
            $status = $this->actingAs($staff)->get($url)->getStatusCode();
            if ($allowed && $status !== 200) {
                $bad[] = "{$i['label']} ({$url}) harusnya 200, dapat {$status}";
            }
            if (! $allowed && $status !== 403) {
                $bad[] = "{$i['label']} ({$url}) harusnya 403, dapat {$status}";
            }
            $sees = str_contains($this->actingAs($staff)->get('/admin')->getContent(), 'href="'.$url.'"');
            if ($allowed !== $sees) {
                $bad[] = "{$i['label']}: sidebar ".($sees ? 'menampilkan' : 'menyembunyikan').' padahal akses '.($allowed ? 'boleh' : 'dilarang');
            }
        }

        $this->assertNoneBroken($bad, 'staf');
    }

    public function test_pengguna_gratis_bisa_membuka_semua_menu_akun_tanpa_error(): void
    {
        $member = User::factory()->create();
        $targets = [];
        foreach ($this->items('account') as $i) {
            $targets["[{$i['group']}] {$i['label']}"] = route($i['route']);
        }

        // Menu Premium boleh mengalihkan atau menampilkan halaman terkunci; yang tidak boleh: 4xx/5xx.
        $this->assertNoneBroken($this->probe($member, $targets, fn ($s) => $s < 400), 'pengguna Gratis (menu akun)');
    }

    public function test_pengguna_premium_bisa_membuka_semua_menu_akun(): void
    {
        $premium = User::factory()->create();
        Entitlement::create(['user_id' => $premium->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);
        $targets = [];
        foreach ($this->items('account') as $i) {
            $targets["[{$i['group']}] {$i['label']}"] = route($i['route']);
        }

        $this->assertNoneBroken($this->probe($premium, $targets, fn ($s) => $s === 200), 'pengguna Premium (menu akun)');
    }

    public function test_sidebar_akun_menampilkan_semua_menu_untuk_pengguna(): void
    {
        $member = User::factory()->create();
        $html = $this->actingAs($member)->get('/dashboard')->assertOk()->getContent();

        $missing = [];
        foreach ($this->items('account') as $i) {
            if (! str_contains($html, route($i['route']))) {
                $missing[] = "{$i['label']} (".route($i['route']).')';
            }
        }

        $this->assertNoneBroken($missing, 'pengguna (sidebar akun tidak menampilkan)');
    }
}
