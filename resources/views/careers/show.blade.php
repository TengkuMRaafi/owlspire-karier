@extends('layouts.app')

@section('title', $career->name)
@section('description', $career->summary)

@section('content')
    <style>
        .kd-empty > div { flex: 1 1 14rem; min-width: 0; }
        .kd-empty b { display: block; line-height: 1.35; }
        .kd-empty p { line-height: 1.5; }
        @media (max-width: 720px) {
            .kd-empty { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: .8rem 1rem; padding: 1rem; }
            .kd-empty > div { grid-column: 2; flex: none; }
            .kd-empty > .btn-outline { grid-column: 1 / -1; width: 100%; min-height: 2.8rem; justify-content: center; text-align: center; }
        }
    </style>
    @php
        $t = $d['theme'];
        $st = $d['stats'];
        $trend = $d['trend'];
        $skills = $d['skills'];
        $steps = $d['steps'];
        $jobs = $d['jobs'];
        $maxSeg = max(1, collect($d['segments'])->max('count') ?? 1);
        $kind = ['video' => 'Video', 'artikel' => 'Artikel', 'kuis' => 'Kuis'];
        $share = urlencode($career->name.' - '.url()->current());
    @endphp

    @include('careers.partials.kd-icons')

    {{-- Hero --}}
    <section class="kd-hero on-dark">
        <div class="kd-hero-bg" aria-hidden="true"><img src="{{ $t['image'] }}" alt="" decoding="async"></div>
        <div class="kd-wrap kd-hero-in">
            <div class="kd-hero-main">
                <nav class="kd-crumb" aria-label="Jejak halaman">
                    <a href="{{ url('/') }}">Beranda</a><span aria-hidden="true">/</span>
                    <a href="{{ route('exam.index') }}#karier">Karier</a><span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $career->name }}</span>
                </nav>
                <div class="kd-pills">
                    <span class="kd-pill kd-pill-solid">{{ $career->division }}</span>
                </div>
                <h1 class="font-display kd-title">{{ $career->name }}</h1>
                <p class="kd-lead">{{ $career->summary }}</p>
                <div class="kd-cta">
                    @if ($type)<a href="#simulasi" class="btn-primary">Coba simulasi<svg class="kd-ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg></a>@endif
                </div>
            </div>

            <aside class="kd-hero-card" aria-label="Posisi tren karier">
                <div class="kd-poster"><img src="{{ $t['image'] }}" alt="" loading="lazy" decoding="async"><span class="kd-poster-tag">{{ $t['label'] }}</span></div>
                @if ($trend)
                    <div class="kd-rank">
                        <span class="kd-rank-no"><small>Peringkat</small>#{{ $trend['position'] }}</span>
                        <span class="kd-rank-txt">
                            <b>Papan karier</b>
                            @if ($trend['delta'] > 0)<span class="kd-up">Naik {{ $trend['delta'] }} peringkat</span>
                            @elseif ($trend['delta'] < 0)<span class="kd-down">Turun {{ abs($trend['delta']) }} peringkat</span>
                            @else<span>Posisi stabil</span>@endif
                        </span>
                    </div>
                    <div class="kd-heat" role="img" aria-label="Tingkat peminat {{ $trend['heat'] }} dari 100"><i style="--w: {{ $trend['heat'] }}%"></i></div>
                    <ul class="kd-rank-meta">
                        <li><b>{{ $trend['jobs'] }}</b> lowongan</li>
                        <li><b>{{ $trend['sims'] }}</b> simulasi</li>
                        <li><b>{{ $trend['targets'] }}</b> target</li>
                    </ul>
                @endif
            </aside>
        </div>
    </section>

    <div class="kd-wrap kd-grid">
        <div class="kd-main">
            {{-- Ringkasan --}}
            @if ($career->detail('tren'))
            <section id="ringkasan" class="kd-sec" data-reveal>
                    <div class="kd-trend">
                        <span class="kd-trend-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-trend"/></svg></span>
                        <div>
                            <p class="kd-k">Tren karier</p>
                            <p class="kd-trend-t">{{ $career->detail('tren') }}</p>
                        </div>
                    </div>
            </section>
            @endif

            {{-- Sehari-hari --}}
            <section id="harian" class="kd-sec" data-reveal>
                <header class="kd-sh"><p class="kd-k">Pekerjaan nyata</p><h2 class="font-display">Yang dikerjakan sehari-hari</h2></header>
                <ul class="kd-does">
                    @foreach ($career->detail('does', []) as $i => $x)
                        <li><span class="kd-does-no">{{ sprintf('%02d', $i + 1) }}</span><p>{{ $x }}</p><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg></li>
                    @endforeach
                </ul>
            </section>

            {{-- Skill --}}
            <section id="skill" class="kd-sec" data-reveal>
                <header class="kd-sh"><p class="kd-k">Kebutuhan kompetensi</p><h2 class="font-display">Skill yang dicari</h2></header>
                @auth
                    <div class="kd-have">
                        <div><b>{{ $skills['have'] }}</b> dari {{ $skills['total'] }} skill sudah ada di profilmu</div>
                        <div class="kd-bar" role="img" aria-label="{{ $skills['have'] }} dari {{ $skills['total'] }} skill"><i style="--w: {{ $skills['total'] ? round($skills['have'] / $skills['total'] * 100) : 0 }}%"></i></div>
                    </div>
                @endauth
                @foreach ($skills['groups'] as $g)
                    <div class="kd-group">
                        @if ($skills['multi'])<p class="kd-group-t">{{ $g['label'] }}</p>@endif
                        <ul class="kd-chips">
                            @foreach ($g['items'] as $s)
                                <li @class(['is-have' => $s['have']])>@if ($s['have'])<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg>@endif{{ $s['name'] }}@if ($s['have'])<span class="sr-only"> (sudah kamu miliki)</span>@endif</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <aside class="kd-cv" aria-labelledby="kd-cv-t">
                    <span class="kd-cv-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-cv"/></svg></span>
                    <div class="kd-cv-body">
                        <h3 id="kd-cv-t" class="font-display">Punya CV? Cek kecocokannya</h3>
                        <p>Unggah CV-mu, lalu lihat skill mana yang sudah cocok untuk {{ $career->name }} dan mana yang masih perlu dilatih.</p>
                        <ul class="kd-cv-pts">
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg>Skill terbaca otomatis</li>
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg>Skor kecocokan</li>
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg>Celah skill</li>
                        </ul>
                    </div>
                    <a href="{{ route('cv.index') }}" class="btn-primary kd-cv-go">Analisis CV<svg class="kd-ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg></a>
                </aside>
            </section>

            {{-- Jalur belajar --}}
            @if ($career->course && $steps)
                <section id="jalur" class="kd-sec" data-reveal>
                    <header class="kd-sh"><p class="kd-k">Dari nol sampai siap</p><h2 class="font-display">Jalur belajar</h2></header>
                    <ol class="kd-steps">
                        @foreach ($steps as $i => $s)
                            <li>
                                <span class="kd-step-no">{{ $i + 1 }}</span>
                                <div class="kd-step-body">
                                    <b>{{ $s['title'] }}</b>
                                    @if ($s['desc'])<p>{{ $s['desc'] }}</p>@endif
                                    @if ($s['type'] || $s['minutes'])
                                        <span class="kd-step-meta">
                                            @if ($s['type'])<span><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-{{ $s['type'] === 'video' ? 'play' : ($s['type'] === 'kuis' ? 'quiz' : 'book') }}"/></svg>{{ $kind[$s['type']] ?? ucfirst($s['type']) }}</span>@endif
                                            @if ($s['minutes'])<span><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-clock"/></svg>{{ $s['minutes'] }} menit</span>@endif
                                        </span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <a href="{{ route('learning.show', $career->course) }}" class="btn-outline kd-go">Buka jalur belajar<svg class="kd-ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg></a>
                </section>
            @endif

            {{-- Lowongan --}}
            <section id="lowongan" class="kd-sec" data-reveal>
                <header class="kd-sh kd-sh-row">
                    <div><p class="kd-k">Peluang terbuka</p><h2 class="font-display">Lowongan terkait</h2></div>
                    @if ($jobs['total'] > 0)<a href="{{ route('jobs.index') }}" class="kd-more">{{ $jobs['total'] }} lowongan<svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg></a>@endif
                </header>
                @if ($jobs['items']->isNotEmpty())
                    <ul class="kd-jobs">
                        @foreach ($jobs['items'] as $j)
                            <li>
                                <a href="{{ $j->url }}" target="_blank" rel="noopener nofollow">
                                    <span class="kd-job-logo">@if ($j->logo_url)<img src="{{ $j->logo_url }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer">@else{{ strtoupper(mb_substr($j->company, 0, 1)) }}@endif</span>
                                    <span class="kd-job-body">
                                        <b>{{ $j->title }}</b>
                                        <small>{{ $j->company }}@if ($j->location) &middot; {{ $j->location }}@endif</small>
                                        <span class="kd-job-tags">
                                            <span>{{ $j->kind === 'magang' ? 'Magang' : 'Kerja' }}</span>
                                            @if ($j->remote)<span>Remote</span>@endif
                                            @if ($j->match_count > 0)<span class="is-match">{{ $j->match_count }} skill cocok</span>@endif
                                        </span>
                                    </span>
                                    <span class="kd-job-when">{{ ($j->posted_at ?? $j->created_at)?->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="kd-empty">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-job"/></svg>
                        <div><b>Belum ada lowongan yang cocok saat ini</b><p>Data lowongan diperbarui berkala. Cari manual di halaman Lowongan.</p></div>
                        <a href="{{ route('jobs.index') }}" class="btn-outline btn-sm">Buka Lowongan</a>
                    </div>
                @endif
            </section>

            {{-- Karier lain: dicocokkan dengan skill pengguna bila ada --}}
            @if ($d['related']['items'])
                <section id="sebidang" class="kd-sec" data-reveal>
                    <header class="kd-sh">
                        <p class="kd-k">Jelajahi lebih luas</p>
                        <h2 class="font-display">{{ $d['related']['personal'] ? 'Karier yang cocok dengan skillmu' : 'Karier sebidang' }}</h2>
                    </header>
                    <ul class="kd-rel">
                        @foreach ($d['related']['items'] as $r)
                            <li><a href="{{ route('careers.show', $r['career']) }}">
                                <span class="kd-rel-img"><img src="{{ $r['theme']['image'] }}" alt="" loading="lazy" decoding="async">@if ($r['match'])<span class="kd-rel-pct">{{ $r['match']['pct'] }}% cocok</span>@endif</span>
                                <b>{{ $r['career']->name }}</b><small>{{ $r['career']->division }}</small>
                                @if ($r['match'])
                                    <span class="kd-rel-match">
                                        <span class="kd-bar" role="img" aria-label="{{ $r['match']['have'] }} dari {{ $r['match']['total'] }} skill cocok"><i style="--w: {{ $r['match']['pct'] }}%"></i></span>
                                        <small>{{ $r['match']['have'] }} dari {{ $r['match']['total'] }} skill sudah kamu miliki</small>
                                    </span>
                                @endif
                                <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-arrow"/></svg>
                            </a></li>
                        @endforeach
                    </ul>
                    @if (count($d['related']['items']) > 3)
                        <div class="sx-all kd-rel-more">
                            <button type="button" class="sx-all-btn" data-rel-toggle aria-expanded="false">
                                <span data-rel-label>Tampilkan semua {{ count($d['related']['items']) }} karier</span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </div>
                        <script>
                        (function () {
                            var b = document.currentScript.parentNode.querySelector('[data-rel-toggle]'), ul = document.currentScript.parentNode.querySelector('.kd-rel');
                            if (!b || !ul) return;
                            var n = {{ count($d['related']['items']) }};
                            b.addEventListener('click', function () {
                                var open = ul.classList.toggle('is-open');
                                b.setAttribute('aria-expanded', open ? 'true' : 'false');
                                b.querySelector('[data-rel-label]').textContent = open ? 'Tampilkan lebih sedikit' : 'Tampilkan semua ' + n + ' karier';
                                b.querySelector('svg').style.transform = open ? 'rotate(180deg)' : '';
                            });
                        })();
                        </script>
                    @endif
                    @unless ($d['related']['personal'])
                        <p class="kd-rel-hint">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-skill"/></svg>
                            <span>
                                @auth Tambahkan skill lewat <a href="{{ route('cv.index') }}">Analisis CV</a> supaya rekomendasi ini dicocokkan dengan keahlianmu.
                                @else <a href="{{ route('register') }}">Daftar</a> dan isi skillmu supaya rekomendasi ini dicocokkan dengan keahlianmu.
                                @endauth
                            </span>
                        </p>
                    @endunless
                </section>
            @endif
        </div>

        <aside class="kd-side">
            @if ($type)
                <div id="simulasi" class="kd-card kd-sim">
                    <p class="eyebrow">Simulasi</p>
                    <h2 class="font-display">{{ $type->name }}</h2>
                    <p class="kd-sim-meta"><span>{{ $st['questions'] }} soal</span><span>{{ $st['minutes'] }} menit</span><span>{{ count($d['segments']) }} bagian</span></p>
                    <ul class="kd-seg">
                        @foreach ($d['segments'] as $seg)
                            <li><span>{{ $seg['name'] }}</span><span class="kd-seg-n">{{ $seg['count'] }} soal</span><i style="--w: {{ round($seg['count'] / $maxSeg * 100) }}%"></i></li>
                        @endforeach
                    </ul>
                    <div class="kd-sim-go">@include('exam.partials.start', ['type' => $type, 'tickets' => $tickets, 'tone' => 'light'])</div>
                    <p class="kd-fine">Coba gratis dulu. Skor dan pembahasan lengkap ada di simulasi penuh.</p>
                </div>
            @endif

            <div class="kd-card kd-target">
                @auth
                    @if ($isTarget)
                        <p class="kd-is-target"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-check"/></svg>Ini karier targetmu</p>
                        @if ($d['readiness'])
                            <div class="kd-ready">
                                <span class="kd-ring" style="--p: {{ $d['readiness']['total'] }}"><b>{{ $d['readiness']['total'] }}</b><small>/100</small></span>
                                <ul>
                                    @foreach ($d['readiness']['parts'] as $p)
                                        <li><span>{{ $p['label'] }}<em>{{ $p['weight'] }}%</em></span><div class="kd-bar"><i style="--w: {{ $p['value'] }}%"></i></div></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <a href="{{ route('dashboard.index') }}" class="btn-outline w-full">Lihat skor kesiapan</a>
                    @else
                        @if (auth()->user()->isAdmin())
                            <p class="kd-fine">Akun admin tidak memasang target karier. Kelola data karier lewat panel admin.</p>
                            <a href="{{ route('admin.careers.index') }}" class="btn-dark w-full">Kelola karier</a>
                        @else
                        <p class="kd-target-t"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-target"/></svg>Jadikan target</p>
                        <form method="POST" action="{{ route('careers.target', $career) }}">@csrf<button class="btn-dark w-full">Jadikan karier target</button></form>
                        <p class="kd-fine">Dashboard akan menampilkan skor kesiapanmu untuk karier ini.</p>
                        @endif
                    @endif
                @else
                    <p class="kd-target-t"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-target"/></svg>Pantau kesiapanmu</p>
                    <ul class="kd-weights">
                        <li><b>50%</b> hasil simulasi</li><li><b>25%</b> kecocokan CV</li><li><b>25%</b> progres belajar</li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn-dark w-full">Daftar untuk memasang target</a>
                @endauth
            </div>

            <div class="kd-card kd-share">
                <p class="kd-k">Bagikan karier ini</p>
                <div class="kd-share-row">
                    <button type="button" class="btn-outline btn-sm" data-kd-copy="{{ url()->current() }}"><svg class="kd-ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#kd-i-link"/></svg><span data-kd-copy-label>Salin tautan</span></button>
                    <a href="https://wa.me/?text={{ $share }}" target="_blank" rel="noopener" class="btn-outline btn-sm">WhatsApp</a>
                </div>
            </div>
        </aside>
    </div>
@endsection
