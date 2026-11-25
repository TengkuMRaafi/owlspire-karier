<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** A basic test example. */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_halaman_tidak_ditemukan_memakai_tampilan_khusus(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Tersesat')
            ->assertSee('Kembali ke beranda');

        $this->getJson('/halaman-yang-tidak-ada')->assertNotFound()->assertJsonMissing(['Tersesat']);
    }
}
