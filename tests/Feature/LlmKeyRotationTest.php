<?php

namespace Tests\Feature;

use App\Services\Ai\GroqClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmKeyRotationTest extends TestCase
{
    private function setKeys(string $keys): void
    {
        config(['services.groq.key' => $keys, 'services.groq.model' => 'm', 'services.groq.base_url' => 'https://llm.test/v1', 'services.llm.order' => 'groq']);
        Cache::flush();
    }

    public function test_beberapa_kunci_dipisah_koma_menjadi_entri_sendiri(): void
    {
        $this->setKeys('k1, k2,k3 ,k4,k1');

        $p = app(GroqClient::class)->providers();

        $this->assertSame(['k1', 'k2', 'k3', 'k4'], array_column($p, 'key'));
        $this->assertSame(['groq#1', 'groq#2', 'groq#3', 'groq#4'], array_column($p, 'id'));
    }

    public function test_satu_kunci_tetap_bernama_biasa(): void
    {
        $this->setKeys('k1');

        $this->assertSame(['groq'], array_column(app(GroqClient::class)->providers(), 'id'));
    }

    public function test_kunci_kena_limit_diistirahatkan_dan_diganti_kunci_lain(): void
    {
        $this->setKeys('bad,good');
        Http::fake(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer good')
                ? Http::response(['choices' => [['message' => ['content' => 'halo']]]])
                : Http::response(['error' => 'limit'], 429);
        });

        $llm = app(GroqClient::class);
        $this->assertSame('halo', $llm->chat([['role' => 'user', 'content' => 'hai']]));

        // Kunci yang kena 429 tidak dicoba lagi sampai masa istirahatnya habis, jadi panggilan berikutnya langsung memakai kunci yang sehat.
        $this->assertSame('halo', $llm->chat([['role' => 'user', 'content' => 'hai lagi']]));
        $bad = collect(Http::recorded())->filter(fn ($pair) => $pair[0]->hasHeader('Authorization', 'Bearer bad'))->count();
        $this->assertLessThanOrEqual(1, $bad);
    }
}
