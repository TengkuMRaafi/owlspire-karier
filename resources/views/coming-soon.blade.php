@extends('layouts.app')

@section('title', $name.': Segera Hadir')
@section('description', 'Menu '.$name.' Owlspire segera hadir. Sementara itu, kenali arah kariermu atau coba simulasi 5 menit.')

@section('content')
    <section class="cs on-dark">
        {{-- Latar: kisi, cahaya redup, dan gerimis tipis (hiasan, suasana murung) --}}
        <div class="cs-bg" aria-hidden="true">
            <span class="cs-glow cs-glow-a"></span><span class="cs-glow cs-glow-b"></span>
            @foreach (range(0, 13) as $i)
                <i class="cs-rain" style="--x: {{ ($i * 37 + 9) % 100 }}%; --d: -{{ ($i * 7 % 23) / 10 }}s; --t: {{ 3.2 + ($i % 5) * .5 }}s; --h: {{ 1.2 + ($i % 3) * .5 }}rem"></i>
            @endforeach
        </div>

        <div class="cs-inner mx-auto max-w-3xl px-4">
            <div class="cs-stage">
                {{-- Papan "Segera Hadir" yang menggantung dan berayun --}}
                <div class="cs-sign">
                    <svg class="cs-ropes" viewBox="0 0 240 70" aria-hidden="true" focusable="false">
                        <path d="M34 70 120 9 206 70" fill="none" stroke="#c5cfde" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" opacity=".75"/>
                        <circle cx="120" cy="7" r="5.5" fill="none" stroke="#f0b429" stroke-width="2.6"/>
                    </svg>
                    <div class="cs-board">
                        <i class="cs-nail cs-nail-l" aria-hidden="true"></i><i class="cs-nail cs-nail-r" aria-hidden="true"></i>
                        <p class="cs-menu">Menu {{ $name }}</p>
                        <h1 class="font-display cs-title">Segera Hadir<span class="cs-bang">!!</span></h1>
                    </div>
                </div>

                {{-- Logo Owlspire dengan wajah murung --}}
                <div class="cs-owl-wrap">
                    <span class="cs-halo" aria-hidden="true"></span>
                    <svg class="cs-owl" viewBox="0 0 120 140" role="img" aria-label="Logo Owlspire dengan wajah murung">
                        <defs>
                            <clipPath id="cs-eye-l"><circle cx="36" cy="76" r="17"/></clipPath>
                            <clipPath id="cs-eye-r"><circle cx="84" cy="76" r="17"/></clipPath>
                        </defs>
                        <g class="cs-owl-body">
                            <path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136 14 114Q8 108 8 100V72Q8 60 18 54L4 22 36 32Z" fill="#4670bd"/>
                            <path d="M60 4 84 32 60 52 36 32Z" fill="#3a63b8"/>
                            <path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136Z" fill="#a9c4f2" opacity=".4"/>
                            <path class="cs-tuft cs-tuft-l" d="M4 22 36 32 22 52Q14 54 14 60Z" fill="#2f58b3"/>
                            <path class="cs-tuft cs-tuft-r" d="M116 22 84 32 98 52Q106 54 106 60Z" fill="#2f58b3"/>
                            <path d="M60 46V16M50 26 60 14 70 26" fill="none" stroke="#2f58b3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 56Q36 52 54 64V70Q34 62 14 62Z" fill="#2f58b3"/>
                            <path d="M106 56Q84 52 66 64V70Q86 62 106 62Z" fill="#2f58b3"/>
                            <circle cx="36" cy="76" r="17" fill="#fff"/><circle cx="84" cy="76" r="17" fill="#fff"/>
                            {{-- Iris dan pupil menunduk --}}
                            <g class="cs-gaze">
                                <circle cx="36" cy="81" r="10.5" fill="#d9a21f"/><circle cx="84" cy="81" r="10.5" fill="#d9a21f"/>
                                <circle cx="36" cy="84" r="5.8" fill="#1f2a3d"/><circle cx="84" cy="84" r="5.8" fill="#1f2a3d"/>
                                <circle cx="38.2" cy="81.6" r="2" fill="#fff"/><circle cx="86.2" cy="81.6" r="2" fill="#fff"/>
                            </g>
                            {{-- Kelopak turun miring (sudut luar lebih rendah) = wajah sedih --}}
                            <g clip-path="url(#cs-eye-l)"><path class="cs-lid" d="M14 50H58V60L14 74Z" fill="#2f58b3"/></g>
                            <g clip-path="url(#cs-eye-r)"><path class="cs-lid" d="M106 50H62V60L106 74Z" fill="#2f58b3"/></g>
                            <path d="M54 90H66L60 101Z" fill="#e0a93a"/>
                            <path d="M48 114 60 104 72 114M48 122 60 112 72 122" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        <path class="cs-tear cs-tear-l" d="M26 91C26 91 21 98 21 102a5 5 0 0 0 10 0C31 98 26 91 26 91Z" fill="#8fb8ff"/>
                        <path class="cs-tear cs-tear-r" d="M94 91C94 91 89 98 89 102a5 5 0 0 0 10 0C99 98 94 91 94 91Z" fill="#8fb8ff"/>
                    </svg>
                </div>
                <p class="cs-word" aria-hidden="true"><span>Owl</span><b>spire</b></p>
            </div>

            <p class="cs-lead">{{ $lead }}</p>

            <div class="cs-progress" role="img" aria-label="Sedang disiapkan">
                <span>Sedang disiapkan</span><div class="cs-bar"><i></i></div>
            </div>

            <div class="cs-actions">
                <a href="{{ route('careers.quiz') }}" class="btn-primary px-7 py-3.5 text-base">Cari arah kariermu</a>
                <a href="{{ route('exam.trial') }}" class="btn-outline-light px-7 py-3.5 text-base">Coba simulasi 5 menit</a>
            </div>
        </div>
    </section>
@endsection
