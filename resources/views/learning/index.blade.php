@extends('layouts.app')

@section('title', 'Belajar')
@section('description', 'Jalur belajar per karier dengan video YouTube pilihan, lengkap dengan penjelasan tiap topik.')

@section('content')
    @php $fallbackTheme = ['key' => 'umum', 'label' => 'Karier', 'image' => asset('images/karier/umum.svg')]; @endphp

    {{-- ============ Hero: jalur belajar ============ --}}
    <section class="bl-hero">
        <div class="bl-hero-grid mx-auto max-w-6xl px-4">
            <div class="bl-hero-copy">
                <p class="bl-eyebrow">Belajar</p>
                <h1 class="font-display bl-title">Satu jalur belajar <br>untuk <em>tiap karier.</em></h1>
                <p class="bl-lead">Empat topik berurutan per karier. Tiap topik punya penjelasan singkat dan video YouTube yang diputar langsung di Owlspire.</p>
                <a href="#jalur" class="btn-primary bl-cta">Pilih jalurmu</a>
            </div>

            {{-- Tangga belajar: empat anak tangga naik, maskot melompat satu per satu sampai puncak. Hiasan saja. --}}
            <div class="bl-trail" aria-hidden="true">
                <svg viewBox="0 0 480 300" role="presentation">
                    <ellipse class="bl-halo" cx="240" cy="165" rx="215" ry="115"/>
                    <path class="bl-ground" d="M10 268H470"/>
                    <g class="bl-stp bl-p1">
                        <rect class="bl-bar" x="28" y="212" width="96" height="56" rx="14"/>
                        <rect class="bl-cap" x="38" y="220" width="76" height="6" rx="3"/>
                        <rect class="bl-pill" x="30" y="230" width="92" height="28" rx="14"/><text class="bl-num" x="76" y="244.5">Dasar</text>
                    </g>
                    <g class="bl-stp bl-p2">
                        <rect class="bl-bar" x="132" y="168" width="96" height="100" rx="14"/>
                        <rect class="bl-cap" x="142" y="176" width="76" height="6" rx="3"/>
                        <rect class="bl-pill" x="134" y="230" width="92" height="28" rx="14"/><text class="bl-num" x="180" y="244.5">Praktik</text>
                    </g>
                    <g class="bl-stp bl-p3">
                        <rect class="bl-bar" x="236" y="124" width="96" height="144" rx="14"/>
                        <rect class="bl-cap" x="246" y="132" width="76" height="6" rx="3"/>
                        <rect class="bl-pill" x="238" y="230" width="92" height="28" rx="14"/><text class="bl-num" x="284" y="244.5">Kasus nyata</text>
                    </g>
                    <g class="bl-stp bl-p4">
                        <rect class="bl-bar" x="340" y="80" width="96" height="188" rx="14"/>
                        <rect class="bl-cap" x="350" y="88" width="76" height="6" rx="3"/>
                        <rect class="bl-pill" x="342" y="230" width="92" height="28" rx="14"/><text class="bl-num" x="388" y="244.5">Siap kerja</text>
                    </g>
                    <g transform="translate(70 56)"><path class="bl-twk" d="M0-9 2.4-2.4 9 0 2.4 2.4 0 9-2.4 2.4-9 0-2.4-2.4Z"/></g>
                    <g transform="translate(436 150)"><path class="bl-twk bl-twk2" d="M0-7 1.9-1.9 7 0 1.9 1.9 0 7-1.9 1.9-7 0-1.9-1.9Z"/></g>
                    <g transform="translate(250 40)"><path class="bl-twk bl-twk3" d="M0-6 1.6-1.6 6 0 1.6 1.6 0 6-1.6 1.6-6 0-1.6-1.6Z"/></g>
                    <g class="bl-flagg"><path d="M428 82V34" class="bl-pole"/><path class="bl-cloth" d="M428 34 460 45 428 56Z"/></g>
                    <circle class="bl-cf" cx="388" cy="64" r="3" style="--dx:-70px;--dy:-60px;fill:#f0b429"/><circle class="bl-cf" cx="388" cy="64" r="4" style="--dx:-40px;--dy:-85px;fill:#7fa8e8"/><circle class="bl-cf" cx="388" cy="64" r="3" style="--dx:-8px;--dy:-95px;fill:#fff"/><circle class="bl-cf" cx="388" cy="64" r="4" style="--dx:28px;--dy:-88px;fill:#f0b429"/><circle class="bl-cf" cx="388" cy="64" r="3" style="--dx:62px;--dy:-62px;fill:#3b6cd4"/><circle class="bl-cf" cx="388" cy="64" r="4" style="--dx:80px;--dy:-30px;fill:#7fa8e8"/><circle class="bl-cf" cx="388" cy="64" r="3" style="--dx:-86px;--dy:-28px;fill:#fff"/><circle class="bl-cf" cx="388" cy="64" r="4" style="--dx:48px;--dy:-100px;fill:#f0b429"/>
                    <rect class="bl-trk" x="28" y="284" width="408" height="6" rx="3"/>
                    <rect class="bl-fill" x="28" y="284" width="408" height="6" rx="3"/>
                    <g class="bl-hop"><g class="bl-hop-in">
                        <ellipse class="bl-shadow" cx="0" cy="1" rx="19" ry="4"/>
                        <x-owl :animated="true" x="-23" y="-54" width="46" height="54" />
                    </g></g>
                </svg>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">
        @if ($continue)
            @php $cTheme = $continue->career ? $themes->for($continue->career) : $fallbackTheme; @endphp
            <a href="{{ route('learning.show', $continue) }}" class="bl-continue">
                <span class="bl-continue-ring" style="--p: {{ $progress[$continue->id] }}">
                    <img src="{{ $cTheme['image'] }}" alt="" decoding="async">
                </span>
                <span class="bl-continue-t">
                    <small>Lanjutkan belajar</small>
                    <strong>{{ $continue->career?->name ?? $continue->title }}</strong>
                    <span>{{ $progress[$continue->id] }}% selesai</span>
                </span>
                <span class="bl-continue-go" aria-hidden="true">&rarr;</span>
            </a>
        @endif

        <div id="jalur" class="bl-head">
            <div>
                <p class="eyebrow">Jalur belajar</p>
                <h2 class="font-display">Mulai dari bidang yang kamu tuju.</h2>
            </div>
        </div>

        @if ($total > 0)
            <form method="GET" action="{{ route('learning.index') }}#jalur" role="search" class="ui-bar" data-live-form>
                <div class="ui-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <label for="bl-q" class="sr-only">Cari jalur belajar</label>
                    <input id="bl-q" type="search" name="q" value="{{ $q }}" placeholder="Cari jalur (mis. data, desain, hukum)" autocomplete="off" enterkeyhint="search">
                </div>
                @if ($categories->count() > 1)
                    <x-dropdown name="kategori" id="bl-kat" aria="Kategori" all="Semua kategori" :options="$categories->mapWithKeys(fn ($c) => [$c => $c])->all()" :value="$kategori" />
                @endif
                <button type="submit" class="ui-go">Cari</button>
            </form>
        @endif

        <div class="ui-results" data-live-results>
            @include('learning.partials.results')
        </div>
    </section>

    @include('partials.live-script')
@endsection
