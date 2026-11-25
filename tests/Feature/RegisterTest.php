<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_membuat_member_belum_masuk_dan_kirim_link_verifikasi(): void
    {
        Notification::fake();

        $this->post('/daftar', [
            'name' => 'Rina Sari',
            'email' => 'rina@example.com',
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ])->assertRedirect(route('verification.sent'));

        $user = User::where('email', 'rina@example.com')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmailId::class);
    }

    public function test_role_tidak_bisa_dipaksa_lewat_form(): void
    {
        $this->post('/daftar', [
            'name' => 'Nakal', 'email' => 'nakal@example.com', 'role' => 'admin',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ]);

        $this->assertSame('member', User::where('email', 'nakal@example.com')->value('role'));
    }

    public function test_email_ganda_ditolak(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/daftar', [
            'name' => 'X', 'email' => 'ada@example.com',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_masuk_langsung_ke_panel_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'rahasia-123']);

        $this->post('/masuk', ['email' => $admin->email, 'password' => 'rahasia-123'])->assertRedirect('/admin');
    }
}
