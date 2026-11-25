<?php

/**
 * Papan Top 5 karier di landing: pengumpulan permintaan pasar tiap minggu.
 *
 * Tiap karier punya kata kunci judul lowongan (kunci = slug karier). Satu lowongan dihitung ke SATU karier saja,
 * yaitu yang kata kuncinya paling panjang cocok dengan judul. Karier baru tanpa baris di sini memakai namanya sendiri.
 */
return [
    // Zona waktu jadwal. Minggu 00:00 di sini = Minggu 00:00 WIB.
    'timezone' => env('TRENDING_TIMEZONE', 'Asia/Jakarta'),

    // Pasar yang dicari di sumber yang mendukung lokasi (LinkedIn).
    'location' => env('TRENDING_LOCATION', 'Indonesia'),

    // Berapa kata kunci per karier yang dikirim ke tiap sumber berbasis pencarian (hemat kuota).
    'queries_per_career' => ['linkedin' => 2, 'jobicy' => 2],

    // Jeda antar permintaan ke satu sumber (milidetik), supaya sopan ke API.
    'pause_ms' => 300,

    'keywords' => [
        'ai-engineer' => ['ai engineer', 'machine learning engineer', 'ml engineer', 'llm engineer', 'artificial intelligence'],
        'soc-analyst' => ['soc analyst', 'security analyst', 'cyber security analyst', 'cybersecurity analyst', 'analis keamanan siber'],
        'ehs-specialist' => ['ehs specialist', 'ehs manager', 'hse officer', 'hse specialist', 'safety officer', 'spesialis k3', 'ahli k3', 'health and safety'],
        'procurement' => ['procurement', 'purchasing', 'sourcing specialist'],
        'industrial-engineer' => ['industrial engineer', 'process engineer', 'manufacturing engineer'],
        'learning-specialist' => ['learning and development', 'learning & development', 'l&d', 'instructional designer', 'training specialist', 'corporate trainer'],
        'logistik' => ['logistics specialist', 'logistics coordinator', 'import specialist', 'customs', 'supply chain coordinator', 'logistik'],
        'partnership' => ['business development', 'partnership manager', 'partnerships', 'partner manager'],
        'data-analyst' => ['data analyst', 'business intelligence', 'bi analyst', 'analis data'],
        'software-developer' => ['software developer', 'software engineer', 'web developer', 'full stack', 'fullstack', 'frontend developer', 'backend developer', 'laravel', 'react developer'],
        'ui-ux' => ['ui/ux', 'ux designer', 'ui designer', 'product designer', 'ux researcher'],
        'digital-marketing' => ['digital marketing', 'performance marketing', 'seo specialist', 'social media manager', 'content marketing', 'growth marketing'],
        'akuntansi' => ['accountant', 'accounting', 'tax specialist', 'tax accountant', 'akuntan', 'perpajakan'],
        'hr-talent' => ['talent acquisition', 'recruiter', 'hr specialist', 'human resources', 'hr generalist', 'people operations'],
        'customer-service' => ['customer service', 'customer support', 'customer success', 'customer experience', 'support specialist'],
    ],
];
