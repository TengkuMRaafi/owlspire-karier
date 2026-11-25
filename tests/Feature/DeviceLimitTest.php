<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\NewDeviceLogin;
use App\Services\Account\DeviceGuard;
use App\Services\Account\SessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Batas perangkat bersamaan dan pemberitahuan login dari perangkat baru. */
class DeviceLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $id, int $userId, int $minutesAgo): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $userId, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 Chrome/120',
            'payload' => '', 'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
        ]);
    }

    private function fakeRequest(): Request
    {
        return Request::create('/masuk', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0 Safari/537.36',
            'REMOTE_ADDR' => '10.0.0.9',
        ]);
    }

    public function test_login_baru_mengeluarkan_sesi_paling_lama_bila_batas_tercapai(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create();
        $this->makeSession('tua', $u->id, 30);
        $this->makeSession('baru', $u->id, 5);

        $kicked = app(SessionManager::class)->enforceLimit($u, 2);

        $this->assertSame(1, $kicked);
        $this->assertDatabaseMissing('sessions', ['id' => 'tua']);
        $this->assertDatabaseHas('sessions', ['id' => 'baru']);
    }

    public function test_tidak_ada_yang_dikeluarkan_selama_di_bawah_batas(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create();
        $this->makeSession('satu', $u->id, 5);

        $this->assertSame(0, app(SessionManager::class)->enforceLimit($u, 2));
        $this->assertDatabaseHas('sessions', ['id' => 'satu']);
    }

    public function test_sesi_kedaluwarsa_tidak_dihitung(): void
    {
        config(['session.driver' => 'database']);
        $u = User::factory()->create();
        $this->makeSession('basi', $u->id, 600);
        $this->makeSession('aktif', $u->id, 5);

        $this->assertSame(0, app(SessionManager::class)->enforceLimit($u, 2));
    }

    public function test_perangkat_pertama_tidak_memicu_email_perangkat_kedua_memicu(): void
    {
        Notification::fake();
        $u = User::factory()->create();
        $guard = app(DeviceGuard::class);

        $guard->beforeLogin($this->fakeRequest(), $u);
        $this->app->terminate();
        Notification::assertNothingSent();
        $this->assertSame(1, UserDevice::where('user_id', $u->id)->count());

        $guard->beforeLogin($this->fakeRequest(), $u); // tanpa cookie perangkat: dianggap perangkat baru
        $this->app->terminate();
        Notification::assertSentTo($u, NewDeviceLogin::class);
        $this->assertSame(2, UserDevice::where('user_id', $u->id)->count());
    }

    public function test_admin_tidak_dibatasi_dan_tidak_dicatat(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        app(DeviceGuard::class)->beforeLogin($this->fakeRequest(), $admin);

        $this->assertSame(0, UserDevice::where('user_id', $admin->id)->count());
    }
}
