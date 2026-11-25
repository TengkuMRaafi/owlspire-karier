<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien LLM berantai. Nama kelas tetap GroqClient supaya pemakainya tidak perlu diubah.
 *
 * Semua penyedia memakai format OpenAI (/chat/completions). Urutan default: Groq, Gemini, Cerebras, OpenRouter (ubah lewat LLM_ORDER).
 * Penyedia tanpa kunci dilewati. Satu penyedia boleh punya beberapa kunci (dipisah koma, mis. GROQ_API_KEY=kunci1,kunci2,kunci3).
 * Tiap kunci diperlakukan sendiri: yang kena limit atau gagal "istirahat" sebentar, panggilan pindah ke kunci lain, baru ke penyedia berikutnya.
 * Mengembalikan null bila tidak ada penyedia aktif atau semuanya gagal.
 */
class GroqClient
{
    private ?string $used = null;

    /**
     * @param  string|null  $orderOverride  urutan penyedia khusus (mis. "gemini,groq"); null = pakai LLM_ORDER
     * @return array<int,array{name:string,id:string,key:string,model:string,base_url:string,timeout:int}> satu entri per kunci yang terisi, berurutan
     */
    public function providers(?string $orderOverride = null): array
    {
        $all = [
            'groq' => [
                'key' => config('services.groq.key'),
                'model' => config('services.groq.model'),
                'base_url' => config('services.groq.base_url'),
                'timeout' => config('services.groq.timeout'),
            ],
        ];
        foreach (['gemini', 'cerebras', 'openrouter'] as $name) {
            $all[$name] = (array) config("services.llm.$name", []);
        }

        $order = array_filter(array_map('trim', explode(',', $orderOverride ?: (string) config('services.llm.order', 'groq,gemini,cerebras,openrouter'))));
        $out = [];
        foreach ($order as $name) {
            $p = $all[$name] ?? null;
            if (! $p || ! filled($p['model'] ?? null) || ! filled($p['base_url'] ?? null)) {
                continue;
            }
            $keys = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($p['key'] ?? ''))))));
            foreach ($keys as $i => $key) {
                $out[] = [
                    'name' => $name,
                    'id' => count($keys) > 1 ? $name.'#'.($i + 1) : $name,
                    'key' => $key,
                    'model' => (string) $p['model'],
                    'base_url' => (string) $p['base_url'],
                    'timeout' => (int) ($p['timeout'] ?? 20),
                ];
            }
        }

        return $out;
    }

    public function enabled(): bool
    {
        return $this->providers() !== [];
    }

    /** Nama penyedia yang menjawab panggilan terakhir (untuk debug). */
    public function lastProvider(): ?string
    {
        return $this->used;
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     * @param  float  $temperature  0.2-0.4 untuk keluaran terstruktur (JSON); 0.7-0.9 untuk percakapan yang terasa hidup
     * @param  string|null  $order  urutan penyedia khusus untuk panggilan ini; null = LLM_ORDER
     */
    public function chat(array $messages, int $maxTokens = 1200, float $temperature = 0.4, ?string $order = null): ?string
    {
        $providers = $this->providers($order) ?: $this->providers(); // urutan khusus tanpa kunci aktif: pakai urutan biasa
        if ($providers === []) {
            return null;
        }

        // Lewati penyedia yang sedang istirahat; kalau semuanya istirahat, coba semuanya lagi.
        $ready = array_values(array_filter($providers, fn ($p) => ! Cache::has('llm:cool:'.$p['id'])));
        $candidates = $this->spread($ready !== [] ? $ready : $providers);

        $multi = count($candidates) > 1;
        $start = microtime(true);
        $budget = (float) config('services.llm.budget', 24); // total detik; dijaga di bawah batas waktu PHP

        foreach ($candidates as $p) {
            $left = $budget - (microtime(true) - $start);
            if ($left < 3) {
                break;
            }
            $timeout = (int) max(3, min($p['timeout'], $multi ? 12 : $p['timeout'], $left));
            $text = $this->call($p, $messages, $maxTokens, $timeout, $multi ? 0 : 1, $temperature);
            if ($text !== null) {
                $this->used = $p['name'];

                return $text;
            }
        }

        $this->used = null;

        return null;
    }

    /** Uji satu penyedia saja (dipakai php artisan llm:test). Mengembalikan teks, atau null bila gagal. */
    public function ping(array $provider, string $prompt): ?string
    {
        return $this->call($provider, [['role' => 'user', 'content' => $prompt]], 600, max(5, $provider['timeout']), 0);
    }

    /** Kunci dari penyedia yang sama diacak urutannya supaya beban terbagi rata; urutan antarpenyedia tetap. */
    private function spread(array $list): array
    {
        $groups = [];
        foreach ($list as $p) {
            $groups[$p['name']][] = $p;
        }
        $out = [];
        foreach ($groups as $keys) {
            shuffle($keys);
            array_push($out, ...$keys);
        }

        return $out;
    }

    private function call(array $p, array $messages, int $maxTokens, int $timeout, int $retries, float $temperature = 0.4): ?string
    {
        $name = $p['name'];
        $payload = ['model' => $p['model'], 'messages' => $messages, 'temperature' => $temperature];
        // Groq memakai max_completion_tokens; penyedia lain memakai max_tokens.
        $payload[$name === 'groq' ? 'max_completion_tokens' : 'max_tokens'] = $maxTokens;
        // Model gpt-oss "berpikir" dulu; level rendah menghemat token dan waktu.
        if (in_array($name, ['groq', 'cerebras'], true) && str_contains($p['model'], 'gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            $req = Http::withToken($p['key'])->timeout($timeout);
            if ($retries > 0) {
                $req = $req->retry($retries, 400, throw: false);
            }
            $res = $req->post(rtrim($p['base_url'], '/').'/chat/completions', $payload);

            if (! $res->successful()) {
                Log::warning('LLM gagal', ['provider' => $p['id'], 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 300)]);
                // 429 = kuota habis, 4xx lain = kunci/model salah (jangan dicoba terus), 5xx = gangguan sesaat.
                $this->rest($p['id'], $res->status() === 429 ? 120 : ($res->status() >= 500 ? 30 : 300));

                return null;
            }

            $text = trim((string) data_get($res->json(), 'choices.0.message.content', ''));
            if ($text === '') {
                // Biasanya batas token habis dipakai untuk "berpikir" sebelum jawaban sempat ditulis.
                Log::warning('LLM mengembalikan jawaban kosong', ['provider' => $p['id'], 'finish' => data_get($res->json(), 'choices.0.finish_reason'), 'usage' => data_get($res->json(), 'usage')]);

                return null;
            }

            return $text;
        } catch (\Throwable $e) {
            Log::warning('LLM error: '.$p['id'].': '.$e->getMessage());
            $this->rest($p['id'], 30);

            return null;
        }
    }

    private function rest(string $name, int $seconds): void
    {
        try {
            Cache::put('llm:cool:'.$name, 1, $seconds);
        } catch (\Throwable) {
            // cache bermasalah tidak boleh menggagalkan panggilan
        }
    }
}
