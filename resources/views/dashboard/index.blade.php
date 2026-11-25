@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('heading', 'Halo, '.\Illuminate\Support\Str::of($user->name)->before(' ').'.')
@section('subheading', $internal ? 'Akses penuh. Semua fitur terbuka.' : ($premium ? 'Premium aktif. Semua fitur terbuka.' : 'Paket Gratis. Lihat apa yang terbuka dan yang menunggu Premium.'))
@section('actions')
    <a href="{{ route('account.exams') }}" class="btn-primary"><x-icon name="play" class="size-4" /> Mulai simulasi</a>
@endsection

@section('content')
    @php
        $showReady = $user->pref('dashboard.show_readiness');
        $showHistory = $user->pref('dashboard.show_history');
        $showCv = $user->pref('dashboard.show_cv');
        $showAccess = $user->pref('dashboard.show_access');

        // Bagian kesiapan yang paling rendah = fokus berikutnya.
        $weakKey = $readiness ? collect($readiness['parts'])->sortBy('value')->keys()->first() : null;
        $weak = $weakKey ? $readiness['parts'][$weakKey] : null;
        $weakUrl = match ($weakKey) {
            'cv' => $premium ? route('cv.index') : route('account.subscription'),
            'learn' => route('account.learning'),
            default => route('account.exams'),
        };
        $weakVerb = match ($weakKey) {
            'cv' => $premium ? 'Unggah CV' : 'Buka analisis CV',
            'learn' => 'Lanjut belajar',
            default => 'Latihan simulasi',
        };

        // Pintasan: yang terbuka dulu, tanpa tujuan ganda, maksimal 8.
        $shortcuts = collect($features['free'])->concat($features['premium'])
            ->sortBy('locked')->unique('url')->take(8);
    @endphp

    <div class="pn-stack db">
        @if ($stats['berjalan'])
            <x-alert type="warning" title="Ujian yang belum selesai">
                <p>{{ $stats['berjalan']->type->name }} &middot; sisa sekitar {{ (int) ceil($stats['berjalan']->remainingSeconds() / 60) }} menit. <a href="{{ route('exam.room', $stats['berjalan']) }}">Lanjutkan</a></p>
            </x-alert>
        @endif

        {{-- Hero: kesiapan karier target --}}
        @if ($showReady)
            @if ($readiness)
                <section class="db-hero" aria-label="Skor kesiapan karier">
                    <div class="db-ring" style="--p: {{ $readiness['total'] }}" role="img" aria-label="Skor kesiapan {{ $readiness['total'] }} dari 100">
                        <b>{{ $readiness['total'] }}</b><small>dari 100</small>
                    </div>
                    <div class="db-hero-main">
                        <p class="db-hero-k">Target kariermu</p>
                        <h2><a href="{{ route('account.career') }}">{{ $target->name }}</a></h2>
                        @if ($weak && $readiness['total'] < 100)
                            <p class="db-hero-t">Bagian terendah: <b>{{ $weak['label'] }}</b> ({{ $weak['value'] }}%). Mulai dari sini untuk menaikkan skor paling cepat.</p>
                            <a href="{{ $weakUrl }}" class="db-hero-cta">{{ $weakVerb }} <x-icon name="arrow-right" class="size-4" /></a>
                        @else
                            <p class="db-hero-t">Semua bagian sudah penuh. Pertahankan dengan simulasi rutin.</p>
                        @endif
                    </div>
                    <div class="db-parts">
                        @foreach ($readiness['parts'] as $key => $part)
                            <div @class(['db-part', 'is-weak' => $key === $weakKey && $readiness['total'] < 100])>
                                <div class="db-part-h"><span>{{ $part['label'] }}</span><b>{{ $part['value'] }}%</b></div>
                                <div class="db-meter" role="img" aria-label="{{ $part['label'] }} {{ $part['value'] }} persen"><i style="width: {{ $part['value'] }}%"></i></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="db-hero is-empty" aria-label="Skor kesiapan karier">
                    <div class="db-ring" style="--p: 0" aria-hidden="true"><b>&ndash;</b><small>dari 100</small></div>
                    <div class="db-hero-main">
                        <h2>Pilih karier target</h2>
                        <p class="db-hero-t">Dengan satu karier target, skor kesiapanmu dihitung dari simulasi, kecocokan CV, dan kemajuan belajar.</p>
                        <a href="{{ route('account.career') }}" class="db-hero-cta">Pilih karier <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                </section>
            @endif
        @endif

        <style>
            @media (max-width: 639px) {
                /* Mobile: ikon di atas, lalu label, angka, dan keterangan selebar penuh sel (tidak terpotong "..."), tautan jadi tombol kecil di bawah */
                body .db-stat { flex-direction: column; align-items: flex-start; gap: .55rem; padding: .9rem .95rem; }
                body .db-stat > div { width: 100%; }
                body .db-stat small { overflow: visible; text-overflow: clip; white-space: normal; margin-top: .1rem; line-height: 1.3; }
                /* Tombol "Ulangi sekarang": sebaris dengan angka, di kanan; kalau tidak muat otomatis turun ke baris berikutnya */
                body .db-stat > div:has(> .ui-link) { display: flex; flex-wrap: wrap; align-items: center; column-gap: .5rem; }
                body .db-stat > div:has(> .ui-link) > dt { order: 0; flex: 0 0 100%; }
                body .db-stat > div:has(> .ui-link) > dd { order: 1; }
                body .db-stat > div:has(> .ui-link) > small { order: 3; flex: 0 0 100%; }
                body .db-stat .ui-link { order: 2; margin-left: auto; margin-top: 0; padding: .28rem .6rem; border-radius: .5rem; background: var(--color-accent-600, #3b6cd4); color: #fff; font-size: .72rem; font-weight: 700; line-height: 1.2; white-space: nowrap; text-decoration: none; }
                body .db-stat .ui-link:hover, body .db-stat .ui-link:active { background: var(--color-accent-700, #2f58b3); color: #fff; text-decoration: none; }
            }
        </style>
        {{-- Angka penting: satu baris --}}
        @php
            $last = collect($stats['terakhir'])->first();
            $lastMax = $last ? array_sum(array_column($last->score ?? [], 'maks')) : 0;
            $lastPct = $last && $lastMax > 0 ? min(100, (int) round($last->total_score / $lastMax * 100)) : null;
        @endphp
        <dl class="db-strip">
            @foreach ([
                ['Ujian selesai', $stats['selesai'], 'sejak bergabung', 'bars'],
                ['Skor terbaik', $stats['terbaik'] ?? '-', 'dari ujian penuh', 'trophy'],
                ['Tiket simulasi', $premium ? '∞' : $tickets, $internal ? 'akses penuh' : ($premium ? 'tanpa batas' : 'simulasi masih terbuka'), 'ticket'],
                $last
                    ? ['Simulasi terakhir', $lastPct !== null ? $lastPct.'%' : $last->total_score, \Illuminate\Support\Str::limit($last->type->name, 28), 'clock']
                    : ['Simulasi terakhir', '-', 'belum ada simulasi', 'clock'],
            ] as [$label, $value, $hint, $icon])
                <div class="db-stat">
                    <span class="db-stat-ic"><x-icon :name="$icon" /></span>
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                        <small>{{ $hint }}</small>
                    </div>
                </div>
            @endforeach
        </dl>


        @if ($showHistory || $showAccess || $showCv)
            <div @class(['db-cols', 'is-one' => ! $showHistory || ! ($showAccess || $showCv)])>
                {{-- Riwayat terakhir --}}
                @if ($showHistory)
                    <section class="db-panel" aria-label="Riwayat terakhir">
                        <header><h2>Riwayat terakhir</h2><a href="{{ route('account.history') }}" class="ui-link">Lihat semua</a></header>
                        @forelse (collect($stats['terakhir'])->take(4) as $a)
                            @php
                                $max = array_sum(array_column($a->score ?? [], 'maks'));
                                $pct = $max > 0 ? min(100, (int) round($a->total_score / $max * 100)) : null;
                            @endphp
                            <a href="{{ route('exam.result', $a) }}" class="db-row">
                                <span class="db-row-main">
                                    <b>{{ $a->type->name }}</b>
                                    <small>{{ $a->mode === 'full' ? 'Ujian penuh' : 'Mode coba' }} &middot; {{ $a->finished_at->translatedFormat('d M, H:i') }}</small>
                                </span>
                                <span class="db-row-score">
                                    <b>{{ $a->total_score }}</b>
                                    @if ($pct !== null)<span class="db-meter is-sm" aria-hidden="true"><i style="width: {{ $pct }}%"></i></span>@endif
                                </span>
                                <x-icon name="chevron-right" class="db-row-go" />
                            </a>
                        @empty
                            <div class="db-empty">
                                <p>Belum ada ujian. Mulai dengan mode coba gratis.</p>
                                <a href="{{ route('exam.trial') }}" class="btn-outline btn-sm">Coba simulasi</a>
                            </div>
                        @endforelse
                    </section>
                @endif

                {{-- Pintasan --}}
                @if ($showAccess || $showCv)
                    <section class="db-panel" aria-label="Pintasan">
                        <header>
                            <h2>Pintasan</h2>
                            @unless ($internal)<a href="{{ route('account.subscription') }}" class="ui-link">{{ $premium ? 'Kelola langganan' : 'Lihat paket' }}</a>@endunless
                        </header>
                        @if ($showCv)
                            <div class="db-cv">
                                <x-icon name="file" />
                                @if ($premium && $cvTop && $cvCareer)
                                    <p>CV paling cocok: <b>{{ $cvCareer->name }}</b> ({{ $cvTop['score'] }}%)</p><a href="{{ route('cv.index') }}" class="ui-link">Lihat</a>
                                @elseif ($premium)
                                    <p>Unggah CV untuk dicocokkan ke karier.</p><a href="{{ route('cv.index') }}" class="ui-link">Unggah</a>
                                @else
                                    <p>Analisis CV khusus Premium.</p><a href="{{ route('account.subscription') }}" class="ui-link">Buka</a>
                                @endif
                            </div>
                        @endif
                        @if ($showAccess)
                            <ul class="db-links">
                                @foreach ($shortcuts as $f)
                                    <li>
                                        <a href="{{ $f['url'] }}" @class(['is-locked' => $f['locked']]) @if ($f['locked']) title="Khusus Premium" @endif>
                                            <x-icon :name="$f['icon']" /><span>{{ $f['label'] }}</span>
                                            @if ($f['locked'])<x-icon name="lock" class="db-links-lock" />@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection
