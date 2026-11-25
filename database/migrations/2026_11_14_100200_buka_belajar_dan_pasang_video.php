<?php

use App\Models\Lesson;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Membuka menu Belajar, menambah kolom judul video, dan memasang video YouTube pilihan ke topik jalur belajar. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lessons', 'video_title')) {
            Schema::table('lessons', fn (Blueprint $t) => $t->string('video_title')->nullable());
        }

        // Pengaturan lama dari panel admin (Belajar "Segera Hadir") ditimpa: menu dibuka.
        Setting::updateOrCreate(['key' => 'learning_open'], ['value' => true]);
        Cache::forget('site_settings.v1');

        // Topik tanpa video: tombol cari memakai pencarian YouTube biasa, tidak lagi dibatasi lisensi CC.
        Lesson::where('provider', 'link')->get()->each(function (Lesson $l) {
            $q = parse_url((string) $l->content_url, PHP_URL_QUERY);
            parse_str((string) $q, $params);
            if (! empty($params['search_query'])) {
                $url = Lesson::searchUrl($params['search_query']);
                $l->update(['content_url' => $url, 'source_url' => $url, 'license' => 'Pencarian video di YouTube']);
            }
        });

        \App\Services\Learning\VideoSeeder::apply();
    }

    public function down(): void
    {
    }
};
