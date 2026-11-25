@extends('layouts.app')

@section('title', $course->career?->name ?? $course->title)

@section('content')
    <x-page-header eyebrow="Jalur belajar" :title="$course->title" :desc="$course->summary" />

    <section class="mx-auto max-w-4xl px-4 py-12">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            @auth
                <p class="text-sm font-semibold text-graphite-600">Progres kamu: {{ $percent }}%</p>
            @else
                <p class="text-sm text-graphite-600"><a href="{{ route('login') }}" class="font-bold text-accent-700 underline">Masuk</a> untuk menyimpan progres.</p>
            @endauth
            @if ($course->career)
                <a href="{{ route('careers.show', $course->career) }}" class="btn-outline btn-sm">Lihat karier &amp; simulasi</a>
            @elseif (in_array($course->slug, ['jalur-cpns', 'jalur-bumn', 'jalur-tni-polri']))
                <a href="{{ route('exam.index') }}" class="btn-outline btn-sm">Coba simulasi {{ $course->category }}</a>
            @endif
        </div>

        @if (filled($course->description))
            <div class="card lp-about is-clamp" data-lp-about>
                <p class="eyebrow">Tentang jalur ini</p>
                <div class="lp-body">
                    @foreach (preg_split('/\n{2,}/', trim($course->description)) as $para)
                        <p>{{ $para }}</p>
                    @endforeach
                </div>
                <button type="button" class="lp-more" data-lp-more aria-expanded="false">Baca selengkapnya</button>
                @if ($course->skills->isNotEmpty())
                    <p class="lp-about-k">Skill yang dilatih</p>
                    <ul class="lp-skills">
                        @foreach ($course->skills->take(10) as $sk)<li>{{ $sk->name }}</li>@endforeach
                    </ul>
                @endif
            </div>
        @endif

        @foreach ($sections as $section)
            @continue($section->lessons->isEmpty())
            <h2 class="font-display text-2xl font-semibold text-ink">{{ $section->title }}</h2>
            <ol class="mt-4 space-y-3">
                @foreach ($section->lessons as $l)
                    @php $isLocked = ! $l->is_free_preview && ! $premium; @endphp
                    <li>
                        <a href="{{ route('learning.lesson', [$course, $l->id]) }}" class="card flex items-center gap-4 p-4 transition hover:border-accent-600">
                            @if ($l->thumbnailUrl())<span class="lp-mini"><img src="{{ $l->thumbnailUrl() }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><i aria-hidden="true"></i></span>@endif
                            <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-extrabold {{ in_array($l->id, $done) ? 'bg-emerald-600 text-white' : 'bg-graphite-100 text-graphite-700' }}">{{ in_array($l->id, $done) ? '✓' : $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-bold text-graphite-800">{{ $l->title }}</span>
                                <span class="block text-xs text-graphite-500">{{ $l->isVideo() ? 'Video'.($l->duration_minutes ? ' · '.$l->duration_minutes.' mnt' : '') : 'Topik' }}</span>
                                @if ($l->body)<span class="lp-li-sum">{{ $l->body }}</span>@endif
                            </span>
                            @if ($isLocked)<span class="badge bg-accent-100 text-accent-800">Premium</span>@endif
                        </a>
                    </li>
                @endforeach
            </ol>
        @endforeach
    </section>
<script>
    document.querySelectorAll('[data-lp-about]').forEach(function (box) {
        var btn = box.querySelector('[data-lp-more]');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var open = box.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.textContent = open ? 'Tutup' : 'Baca selengkapnya';
        });
    });
</script>
@endsection
