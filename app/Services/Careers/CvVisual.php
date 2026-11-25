<?php

namespace App\Services\Careers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Tampilan asli CV (gambar tiap halaman PDF dan posisi barisnya) untuk pratinjau bertanda panah.
 * Isinya data pribadi, jadi semuanya disimpan terenkripsi di disk privat (bukan di public/), satu folder per pengguna,
 * dan ikut terhapus bersama data CV.
 */
class CvVisual
{
    private const MAX_DATA_URL = 1800000;

    private function disk()
    {
        return Storage::disk('local');
    }

    private function dir(int $userId): string
    {
        return 'cv-visual/'.$userId;
    }

    /**
     * Ubah data URL JPEG kiriman browser menjadi isi berkas, dan pastikan memang gambar JPEG yang wajar.
     *
     * @param  mixed  $urls
     * @return array<int,array{bin:string,w:int,h:int}>|null
     */
    public function decode(mixed $urls): ?array
    {
        if (! is_array($urls) || $urls === []) {
            return null;
        }

        $out = [];
        foreach (array_slice(array_values($urls), 0, CvLayout::MAX_PAGES) as $u) {
            if (! is_string($u) || strlen($u) > self::MAX_DATA_URL || ! preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $u, $m)) {
                return null;
            }
            $bin = base64_decode($m[1], true);
            $info = $bin === false ? false : @getimagesizefromstring($bin);
            if (! $info || ($info[2] ?? 0) !== IMAGETYPE_JPEG || $info[0] < 200 || $info[1] < 200 || $info[0] > 2400 || $info[1] > 4000) {
                return null;
            }
            $out[] = ['bin' => $bin, 'w' => (int) $info[0], 'h' => (int) $info[1]];
        }

        return $out;
    }

    /**
     * @param  array{pages:array}  $layout  hasil CvLayout::sanitize
     * @param  array<int,array{bin:string,w:int,h:int}>  $images  hasil decode()
     */
    public function save(int $userId, array $layout, array $images): void
    {
        $this->forget($userId);
        $dir = $this->dir($userId);

        foreach ($images as $i => $img) {
            $this->disk()->put($dir.'/page-'.($i + 1).'.enc', Crypt::encryptString(base64_encode($img['bin'])));
        }
        $meta = ['v' => time(), 'pages' => $layout['pages'], 'sizes' => array_map(fn ($i) => [$i['w'], $i['h']], $images)];
        $this->disk()->put($dir.'/layout.enc', Crypt::encryptString(json_encode($meta, JSON_UNESCAPED_UNICODE)));
    }

    /** @return array{v:int,pages:array,sizes:array}|null */
    public function load(int $userId): ?array
    {
        $path = $this->dir($userId).'/layout.enc';
        if (! $this->disk()->exists($path)) {
            return null;
        }

        try {
            $meta = json_decode(Crypt::decryptString((string) $this->disk()->get($path)), true);
        } catch (DecryptException) {
            return null;
        }

        return is_array($meta) && is_array($meta['pages'] ?? null) && is_array($meta['sizes'] ?? null) && count($meta['pages']) === count($meta['sizes']) ? $meta : null;
    }

    public function image(int $userId, int $n): ?string
    {
        $path = $this->dir($userId).'/page-'.$n.'.enc';
        if ($n < 1 || $n > CvLayout::MAX_PAGES || ! $this->disk()->exists($path)) {
            return null;
        }

        try {
            $bin = base64_decode(Crypt::decryptString((string) $this->disk()->get($path)), true);
        } catch (DecryptException) {
            return null;
        }

        return $bin === false ? null : $bin;
    }

    public function forget(int $userId): void
    {
        $this->disk()->deleteDirectory($this->dir($userId));
    }
}
