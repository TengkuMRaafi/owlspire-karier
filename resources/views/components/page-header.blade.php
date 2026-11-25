@props(['title', 'desc' => null, 'eyebrow' => null])

<section class="on-dark bg-graphite-800 text-white">
    <div class="mx-auto max-w-6xl px-4 py-14">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="font-display mt-2 max-w-3xl text-4xl font-semibold tracking-tight md:text-5xl">{{ $title }}</h1>
        @if ($desc)
            <p class="mt-4 max-w-2xl text-lg text-graphite-200">{{ $desc }}</p>
        @endif
    </div>
</section>
