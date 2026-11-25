<?php

namespace App\Console\Commands;

use App\Services\Ai\GroqClient;
use Illuminate\Console\Command;

class GroqTestCommand extends Command
{
    protected $signature = 'groq:test';

    protected $aliases = ['llm:test'];

    protected $description = 'Cek tiap penyedia LLM (Groq, Gemini, Cerebras, OpenRouter) yang kuncinya sudah diisi';

    public function handle(GroqClient $llm): int
    {
        $providers = $llm->providers();
        if ($providers === []) {
            $this->error('Belum ada kunci yang diisi. Isi GROQ_API_KEY (atau GEMINI_API_KEY, CEREBRAS_API_KEY, OPENROUTER_API_KEY) di .env lalu jalankan php artisan config:clear.');

            return self::FAILURE;
        }

        $ok = 0;
        foreach ($providers as $p) {
            $t = microtime(true);
            $out = $llm->ping($p, 'Balas satu kalimat pendek: Owlspire siap membantu.');
            $ms = (int) round((microtime(true) - $t) * 1000);
            if ($out === null) {
                $this->error(sprintf('[GAGAL] %s (%s), %d ms. Cek key, nama model, dan storage/logs/laravel.log.', $p['id'], $p['model'], $ms));
            } else {
                $ok++;
                $this->info(sprintf('[OK]    %s (%s), %d ms: %s', $p['id'], $p['model'], $ms, $out));
            }
        }

        $this->line('Urutan dipakai: '.implode(' > ', array_column($providers, 'id')).'. Aktif: '.$ok.' dari '.count($providers).'.');

        return $ok > 0 ? self::SUCCESS : self::FAILURE;
    }
}
