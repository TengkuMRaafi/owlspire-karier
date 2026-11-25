@props(['light' => false, 'tagline' => false, 'size' => 'md'])

@php
    // 'nav': navbar, lebih ringkas di HP lalu normal mulai md
    $markSize = match ($size) { 'lg' => 'h-12 w-10', 'nav' => 'h-8 w-[1.7rem] md:h-10 md:w-[2.15rem]', default => 'h-10 w-[2.15rem]' };
    $textSize = match ($size) { 'lg' => 'text-3xl', 'nav' => 'text-xl md:text-2xl', default => 'text-2xl' };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2 md:gap-2.5']) }}>
    {{-- Logo di navbar dan footer diam (tanpa kedip/gerak): lebih ringan dan tidak mengganggu. --}}
    <x-owl class="{{ $markSize }} shrink-0" :animated="false" />
    <span class="leading-none">
        <span class="font-display {{ $textSize }} font-bold tracking-tight"><span class="{{ $light ? 'text-white' : 'text-ink' }}">Owl</span><span class="{{ $light ? 'text-accent-400' : 'text-accent-600' }}">spire</span></span>
        @if ($tagline)
            <span class="logo-tag mt-1 block text-[10px] font-bold uppercase tracking-[0.18em] {{ $light ? 'text-graphite-300' : 'text-graphite-500' }}">{{ config('platform.brand.tagline') }}</span>
        @endif
    </span>
</span>
