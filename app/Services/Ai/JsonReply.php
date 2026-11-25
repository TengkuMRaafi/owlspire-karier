<?php

namespace App\Services\Ai;

/** Mengambil JSON dari jawaban LLM yang kadang dibungkus pagar kode atau kalimat pengantar. */
class JsonReply
{
    public static function parse(?string $text): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $text = preg_replace('/^```(?:json)?\s*|\s*```$/mi', '', trim($text));
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Ambil blok {...} atau [...] terluar.
        foreach ([['{', '}'], ['[', ']']] as [$open, $close]) {
            $a = strpos($text, $open);
            $b = strrpos($text, $close);
            if ($a !== false && $b !== false && $b > $a) {
                $decoded = json_decode(substr($text, $a, $b - $a + 1), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }
}
