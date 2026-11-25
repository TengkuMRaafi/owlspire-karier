<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Skill;
use App\Models\ExamSegment;
use App\Models\ExamType;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Memuat 15 karier, skill, simulasi (jenis ujian + segmen), dan bank soal karier. */
class CareerSeeder extends Seeder
{
    /** Alias supaya CV berbahasa Indonesia/Inggris sama-sama terbaca. */
    private const ALIASES = [
        'Accounting' => ['akuntansi', 'akuntan'], 'Akuntansi' => ['accounting'], 'Pajak' => ['perpajakan', 'taxation', 'tax'],
        'Laporan Keuangan' => ['financial report', 'financial statement'], 'K3' => ['keselamatan kerja', 'keselamatan dan kesehatan kerja'],
        'HSE' => ['ehs', 'hse officer'], 'Hukum Ketenagakerjaan' => ['labor law', 'labour law', 'uu ketenagakerjaan'],
        'Machine Learning' => ['ml'], 'Deep Learning' => ['neural network'], 'NLP' => ['natural language processing'],
        'LLM' => ['large language model'], 'Data Analysis' => ['analisis data', 'data analytics'], 'Data Visualization' => ['visualisasi data'],
        'Customer Service' => ['layanan pelanggan'], 'Communication' => ['komunikasi'], 'Public Speaking' => ['presentasi publik'],
        'Recruitment' => ['rekrutmen', 'recruiting'], 'Procurement' => ['pengadaan', 'purchasing'], 'Negotiation' => ['negosiasi'],
        'Supply Chain' => ['rantai pasok'], 'Inventory' => ['persediaan'], 'Logistics' => ['logistik'], 'Customs' => ['kepabeanan'],
        'Digital Marketing' => ['pemasaran digital'], 'Content Writing' => ['penulisan konten'], 'Social Media' => ['media sosial'],
        'UI/UX' => ['ui ux', 'ux design', 'ui design'], 'Project Management' => ['manajemen proyek'], 'Sales' => ['penjualan'],
        'Quality Control' => ['qc', 'pengendalian kualitas'], 'Time Study' => ['studi waktu'], 'Training' => ['pelatihan'],
        'Risk Assessment' => ['penilaian risiko', 'hiradc'], 'Cybersecurity' => ['keamanan siber'], 'SIEM' => ['splunk', 'qradar'],
    ];

    public function run(): void
    {
        $dir = __DIR__.'/data/';
        $catalog = json_decode(file_get_contents($dir.'karier.json'), true);
        $bank = json_decode(file_get_contents($dir.'soal-karier.json'), true);
        $cfg = config('platform.career_exam');

        foreach ($catalog['careers'] as $i => $c) {
            $type = ExamType::firstOrCreate(['code' => 'karier-'.$c['slug']], [
                'name' => 'Simulasi '.$c['name'],
                'description' => $c['summary'],
                'kind' => 'career',
                'duration_seconds' => $cfg['duration_seconds'],
                'trial_duration_seconds' => $cfg['trial_duration_seconds'],
                'passing_rules' => ['min_percent' => $cfg['min_percent']],
                'is_active' => true,
                'position' => 100 + $i,
            ]);

            $segments = [];
            foreach ([['konsep', 'Konsep Dasar', 'Istilah dan prinsip inti bidang ini.'], ['kasus', 'Studi Kasus', 'Menerapkan konsep pada situasi kerja nyata.'], ['praktik', 'Alat & Praktik', 'Alat, teknik, dan praktik kerja sehari-hari.']] as $j => [$code, $name, $desc]) {
                $segments[$j] = ExamSegment::firstOrCreate(['exam_type_id' => $type->id, 'code' => $code], [
                    'name' => $name, 'description' => $desc,
                    'question_count_trial' => $cfg['trial_per_segment'], 'question_count_full' => \App\Services\Exam\FullExamPlan::split((int) $cfg['full_total'], 3)[$j], 'position' => $j + 1,
                ]);
            }

            foreach (['free', 'pro'] as $tier) {
                foreach ($bank[$c['slug']][$tier] ?? [] as $n => $row) {
                    $h = md5($c['slug'].'|'.$tier.'|'.$n);
                    $uid = sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20, 12));
                    Question::firstOrCreate(['uid' => $uid], [
                        'segment_id' => $segments[$row['s']]->id,
                        'body' => $row['q'],
                        'options' => array_combine(['A', 'B', 'C', 'D'], $row['o']),
                        'answer' => $row['a'],
                        'explanation' => $row['e'],
                        'is_trial' => $tier === 'free',
                        'difficulty' => $tier === 'free' ? 2 : 4,
                    ]);
                }
            }

            $career = Career::firstOrCreate(['slug' => $c['slug']], [
                'name' => $c['name'], 'division' => $c['division'], 'summary' => $c['summary'],
                'details' => ['does' => $c['does'], 'tags' => $c['tags'], 'path' => $c['path'], 'tren' => $c['tren']],
                'rank' => $i + 1, 'is_active' => true, 'exam_type_id' => $type->id,
            ]);

            $ids = [];
            foreach ($c['skills'] as $name) {
                $skill = Skill::firstOrCreate(['name' => $name], ['slug' => Str::slug($name) ?: Str::slug($name.'-x'), 'category' => $c['division']]);
                if ($skill->aliases === null && isset(self::ALIASES[$name])) {
                    $skill->update(['aliases' => self::ALIASES[$name]]);
                }
                $ids[] = $skill->id;
            }
            $career->skills()->syncWithoutDetaching($ids);
        }
    }
}
