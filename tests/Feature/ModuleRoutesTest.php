<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_tiap_modul_bisa_dibuka(): void
    {
        foreach (['/', '/belajar', '/simulasi', '/simulasi/peringkat', '/lowongan', '/harga', '/masuk', '/daftar'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_dashboard_dan_admin_butuh_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/masuk');
        $this->get('/admin')->assertRedirect('/masuk');
    }
}
