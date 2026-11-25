<?php

namespace Database\Seeders;

use App\Models\ExamSegment;
use App\Models\ExamType;
use App\Models\Question;
use App\Services\Exam\QuestionImporter;
use Illuminate\Database\Seeder;

/** Memuat jenis ujian dan bank soal awal (hasil porting questions.js lama). */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/soal-awal.json'), true);

        foreach ($data['types'] as $i => $t) {
            $type = ExamType::firstOrCreate(['code' => $t['code']], [
                'name' => $t['name'],
                'description' => $t['description'],
                'duration_seconds' => $t['duration_seconds'],
                'trial_duration_seconds' => $t['trial_duration_seconds'],
                'passing_rules' => $t['passing_rules'],
                'is_active' => true,
                'position' => $i + 1,
            ]);

            foreach ($t['segments'] as $j => $s) {
                ExamSegment::firstOrCreate(['exam_type_id' => $type->id, 'code' => $s['code']], [
                    'name' => $s['name'],
                    'description' => $s['description'],
                    'question_count_trial' => $s['question_count_trial'],
                    'question_count_full' => \App\Services\Exam\FullExamPlan::split(100, count($t['segments']))[$j],
                    'position' => $j + 1,
                ]);
            }
        }

        if (Question::count() === 0) {
            $result = app(QuestionImporter::class)->import($data['questions']);
            $this->command?->info("Bank soal awal dimuat: {$result['created']} soal.");
        }
    }
}
