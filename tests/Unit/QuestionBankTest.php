<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Menjaga bank soal tetap valid: kunci ada di pilihan, tanpa opsi kembar, tanpa soal ganda. */
class QuestionBankTest extends TestCase
{
    private function load(string $file): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 2).'/database/seeders/data/'.$file), true);
    }

    public function test_soal_seleksi_valid(): void
    {
        $seen = [];
        foreach ($this->load('soal-awal.json')['questions'] as $i => $q) {
            $options = array_map(fn ($o) => mb_strtolower(trim(preg_replace('/^[A-E]\.\s*/u', '', $o))), $q['opts']);
            $letters = array_map(fn ($o) => $o[0], $q['opts']);

            $this->assertContains($q['ans'], $letters, "Soal #{$i}: kunci tidak ada di pilihan.");
            $this->assertCount(count($options), array_unique($options), "Soal #{$i}: ada pilihan kembar.");
            $this->assertNotSame('', trim($q['exp']), "Soal #{$i}: pembahasan kosong.");

            $key = $q['examType'].'/'.$q['segmentId'].'/'.mb_strtolower($q['q']);
            $this->assertArrayNotHasKey($key, $seen, "Soal #{$i}: teks soal ganda.");
            $seen[$key] = true;
        }
    }

    public function test_soal_karier_valid(): void
    {
        foreach ($this->load('soal-karier.json') as $slug => $tiers) {
            foreach ($tiers as $tier => $rows) {
                foreach ($rows as $i => $q) {
                    $options = array_map(fn ($o) => mb_strtolower(trim($o)), $q['o']);
                    $this->assertContains($q['a'], ['A', 'B', 'C', 'D'], "{$slug}/{$tier}/{$i}: kunci tidak valid.");
                    $this->assertLessThan(count($options), ord($q['a']) - 65, "{$slug}/{$tier}/{$i}: kunci di luar pilihan.");
                    $this->assertCount(count($options), array_unique($options), "{$slug}/{$tier}/{$i}: ada pilihan kembar.");
                }
            }
        }
    }

    public function test_ambang_cpns_sesuai_nilai_resmi(): void
    {
        $cpns = collect($this->load('soal-awal.json')['types'])->firstWhere('code', 'cpns');

        // Ambang resmi 65/80/166 dari skor maksimum 150/175/225, tidak boleh dibulatkan ke atas.
        foreach (['twk' => 65 / 150, 'tiu' => 80 / 175, 'tkp' => 166 / 225] as $seg => $ratio) {
            $this->assertLessThanOrEqual($ratio * 100, $cpns['passing_rules']['segments'][$seg]);
        }
    }
}
