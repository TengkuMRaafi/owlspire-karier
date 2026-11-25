<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_bisa_dibuka(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Masuk');
    }

    public function test_login_berhasil_mengarah_ke_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/masuk', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal_kalau_sandi_salah(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->from('/masuk')
            ->post('/masuk', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/keluar')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
