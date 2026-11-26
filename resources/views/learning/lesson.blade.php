@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
    @php
        $total = $total ?? 1;
        $pos = $pos ?? 1;
        $thumb = $lesson->thumbnailUrl();
        $topicQuery = $lesson->content_url;
        // Topik jalur resmi (CPNS/BUMN/TNI-POLRI) tanpa video tersemat: tautannya ke situs institusi, bukan pencarian YouTube.
        $isOfficial = ! $lesson->isVideo() && filled($lesson->credit_name) && filled($lesson->content_url) && ! str_contains((string) $lesson->content_url, 'youtube.com');
    @endphp
    <section class="mx-auto max-w-4xl px-4 py-10">
        <a href="{{ route('learning.show', $course) }}" class="lp-btn lp-btn-back"><span aria-hidden="true">&larr;</span> {{ $course->title }}</a>
        <p class="lp-step">Topik {{ $pos }} dari {{ $total }}</p>
        <h1 class="font-display mt-1 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">{{ $lesson->title }}</h1>

        @if ($locked)
            <div class="mt-8 rounded-2xl border border-accent-300 bg-accent-50 p-6">
                <p class="font-extrabold text-graphite-800">Topik ini bagian dari jalur lengkap Premium.</p>
                <p class="mt-1 text-sm text-graphite-600">Dua topik pertama tiap jalur gratis. Premium membuka seluruh topik, ujian level lanjut, dan analisis CV.</p>
                <a href="{{ route('billing.index') }}" class="btn-primary mt-4">Lihat paket Premium</a>
            </div>
        @else
            {{-- Pemutar Owlspire: thumbnail + tombol play; video YouTube baru dimuat saat ditekan. --}}
            <div class="lp-frame">
                <div class="lp-bar">
                    <span class="lp-brand"><x-icon name="book" /> Owlspire Belajar</span>
                    <span class="lp-bar-t">{{ $lesson->isVideo() ? ($lesson->video_title ?: $lesson->title) : ($isOfficial ? 'Sumber resmi topik ini' : 'Video topik ini') }}</span>
                </div>

                @if ($lesson->isVideo())
                    <div class="lp-stage" data-lp data-id="{{ $lesson->video_id }}" data-title="{{ $lesson->video_title ?: $lesson->title }}">
                        <img class="lp-poster" src="{{ $thumb }}" alt="" width="480" height="360" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                        <span class="lp-shade" aria-hidden="true"></span>
                        <button type="button" class="lp-play" aria-label="Putar video: {{ $lesson->video_title ?: $lesson->title }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg>
                        </button>
                        <span class="lp-chip">Diputar dari YouTube</span>
                    </div>
                @else
                    <div class="lp-stage is-empty">
                        <span class="lp-shade" aria-hidden="true"></span>
                        <div class="lp-empty">
                            <x-icon name="play" />
                            @if ($isOfficial)
                                <p>Video resmi untuk topik ini belum disematkan. Pelajari penjelasan lengkap di bawah, lalu cek informasi terbaru langsung dari {{ $lesson->credit_name }}.</p>
                                <a href="{{ $lesson->content_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary">Buka sumber resmi &#8599;</a>
                            @else
                                <p>Video untuk topik ini belum dipasang.</p>
                                <a href="{{ $topicQuery }}" target="_blank" rel="noopener" class="btn-primary">Cari videonya di YouTube &#8599;</a>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="lp-meta">
                    <div>
                        <b>{{ $lesson->isVideo() ? ($lesson->video_title ?: $lesson->title) : $lesson->title }}</b>
                        @if ($lesson->isVideo())<small>{{ $lesson->credit_name ?: 'YouTube' }} &middot; {{ $lesson->license }}</small>@endif
                    </div>
                    @if ($lesson->isVideo())
                        <a href="{{ $lesson->source_url }}" target="_blank" rel="noopener" class="lp-yt">Buka di YouTube &#8599;</a>
                    @endif
                </div>
            </div>

            @php $det = $lesson->details ?? []; @endphp
            <div class="card mt-6">
                <p class="eyebrow">Tentang topik ini</p>
                <p class="mt-2 text-graphite-800">{{ $lesson->body ?: 'Tonton videonya, lalu catat poin pentingnya dan tandai selesai.' }}</p>

                @if (! empty($det['points']))
                    <h2 class="lp-h">Yang kamu pelajari</h2>
                    <ul class="lp-tips">
                        @foreach ($det['points'] as $point)<li>{{ $point }}</li>@endforeach
                    </ul>
                @endif

                @if (! empty($det['terms']))
                    <h2 class="lp-h">Istilah penting</h2>
                    <dl class="lp-terms">
                        @foreach ($det['terms'] as $term)
                            <div><dt>{{ $term['t'] ?? '' }}</dt><dd>{{ $term['d'] ?? '' }}</dd></div>
                        @endforeach
                    </dl>
                @endif

                @if (! empty($det['practice']))
                    <div class="lp-practice">
                        <b>Latihan mandiri</b>
                        <p>{{ $det['practice'] }}</p>
                    </div>
                @endif

                <h2 class="lp-h">Cara belajar</h2>
                <ul class="lp-tips">
                    @if ($lesson->isVideo())
                        <li>Tonton sampai selesai, lalu catat 3 hal yang baru kamu pahami.</li>
                    @else
                        <li>Baca penjelasan lengkap di bawah, lalu catat 3 hal yang baru kamu pahami.</li>
                    @endif
                    <li>{{ $course->career_id ? 'Setelah topik ini, coba simulasi karier untuk mengukur pemahamanmu.' : 'Setelah topik ini, coba Simulasi '.$course->category.' untuk mengukur pemahamanmu.' }}</li>
                </ul>
            </div>

            @php $deep = $det['deep'] ?? null; @endphp
            @if ($deep)
                <article class="card lp-deep mt-6" id="penjelasan">
                    <header class="lp-deep-head">
                        <h2 class="font-display">Penjelasan lengkap</h2>
                        @if (! empty($deep['minutes']))<span class="lp-deep-min">&plusmn; {{ $deep['minutes'] }} menit baca</span>@endif
                    </header>
                    @if (! empty($deep['prereq']))<p class="lp-deep-pre"><b>Sebelum mulai.</b> {{ $deep['prereq'] }}</p>@endif

                    @foreach ($deep['sections'] ?? [] as $sec)
                        <section class="lp-sec">
                            <h3>{{ $sec['heading'] ?? '' }}</h3>
                            @foreach ($sec['paragraphs'] ?? [] as $para)<p>{{ $para }}</p>@endforeach
                            @if (! empty($sec['table']['rows']))
                                <div class="lp-tbl" tabindex="0">
                                    <table>
                                        <thead><tr>@foreach ($sec['table']['head'] ?? [] as $th)<th scope="col">{{ $th }}</th>@endforeach</tr></thead>
                                        <tbody>@foreach ($sec['table']['rows'] as $row)<tr>@foreach ($row as $td)<td>{{ $td }}</td>@endforeach</tr>@endforeach</tbody>
                                    </table>
                                </div>
                            @endif
                            @if (! empty($sec['code']))<pre class="lp-code" tabindex="0"><code>{{ $sec['code'] }}</code></pre>@endif
                            @if (! empty($sec['steps']))<ol class="lp-steps">@foreach ($sec['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>@endif
                            @foreach ($sec['paragraphs_after'] ?? [] as $para)<p>{{ $para }}</p>@endforeach
                            @if (! empty($sec['note']['text']))
                                <aside class="lp-note"><b>{{ $sec['note']['label'] ?? 'Catatan' }}</b><p>{{ $sec['note']['text'] }}</p></aside>
                            @endif
                        </section>
                    @endforeach

                    @if (! empty($deep['mistakes']))
                        <section class="lp-sec">
                            <h3>Kesalahan yang sering terjadi</h3>
                            <ul class="lp-mist">
                                @foreach ($deep['mistakes'] as $m)
                                    <li><b>{{ $m['wrong'] ?? '' }}</b><span>{{ $m['why'] ?? '' }}</span><em>{{ $m['fix'] ?? '' }}</em></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($deep['checks']))
                        <section class="lp-sec">
                            <h3>Uji pemahamanmu</h3>
                            <p>Jawab dulu dalam hati, baru buka jawabannya.</p>
                            <div class="lp-checks">
                                @foreach ($deep['checks'] as $c)
                                    <details><summary>{{ $c['q'] ?? '' }}</summary><p>{{ $c['a'] ?? '' }}</p></details>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if (! empty($deep['summary']))
                        <section class="lp-sec lp-sum">
                            <h3>Ringkasan</h3>
                            <ul>@foreach ($deep['summary'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                            @if (! empty($deep['next']))<p class="lp-next">{{ $deep['next'] }}</p>@endif
                        </section>
                    @endif
                </article>
            @endif

            @if (($questionCount ?? 0) > 0 && $course->career)
                <a href="{{ route('careers.show', $course->career) }}#simulasi" class="lp-quiz">
                    <span class="lp-quiz-t">
                        <b>{{ $questionCount }} soal dari topik ini ada di simulasi</b>
                        <small>Simulasi {{ $course->career->name }} memuat soal yang disusun dari materi topik &ldquo;{{ $lesson->title }}&rdquo;.</small>
                    </span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            @endif

            <div class="mt-6">
                @auth
                    @if ($completed)
                        <p class="font-bold text-emerald-700">&#10003; Sudah selesai</p>
                    @else
                        <form method="POST" action="{{ route('learning.complete', [$course, $lesson->id]) }}">@csrf<button class="btn-dark">Tandai selesai</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn-outline">Masuk untuk menandai selesai</a>
                @endauth
            </div>
        @endif

        <nav class="lp-nav" aria-label="Topik sebelum dan sesudahnya">
            @if ($prev)
                <a href="{{ route('learning.lesson', [$course, $prev->id]) }}" class="lp-btn lp-btn-prev"><span aria-hidden="true">&larr;</span><span class="lp-btn-t"><small>Sebelumnya</small>{{ $prev->title }}</span></a>
            @else<span></span>@endif
            @if ($next)
                <a href="{{ route('learning.lesson', [$course, $next->id]) }}" class="lp-btn lp-btn-next"><span class="lp-btn-t"><small>Selanjutnya</small>{{ $next->title }}</span><span aria-hidden="true">&rarr;</span></a>
            @endif
        </nav>
    </section>

    <script>
        document.querySelectorAll('[data-lp]').forEach(function (el) {
            var btn = el.querySelector('.lp-play');
            btn.addEventListener('click', function () {
                var f = document.createElement('iframe');
                f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(el.dataset.id) + '?autoplay=1&rel=0&modestbranding=1';
                f.title = el.dataset.title;
                f.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';
                f.referrerPolicy = 'strict-origin-when-cross-origin';
                f.allowFullscreen = true;
                el.classList.add('is-playing');
                el.appendChild(f);
            });
        });
    </script>
@endsection
