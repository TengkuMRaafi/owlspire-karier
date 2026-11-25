{{-- "Cari arah kariermu": dua sisi layar penuh (Karier | Simulasi), tanpa kartu.
     Awalnya hanya judul dan visual. Kursor ke satu sisi: sisi itu menggeser keluar dan menutupi sisi lain (tanpa zoom),
     lalu memunculkan penjelasan serta ajakan mulai; sisi yang tertutup menyisakan label tepi. Layar sentuh atau sempit: semua isi langsung tampil, bertumpuk.
     Radar dan kartu soal hanya hiasan contoh, bukan data pengguna. --}}
@php
    $drDims = collect(config('arah-karier.dimensions'))->map(fn ($m) => $m[0])->values();
    $drSample = [.92, .78, .45, .6, .35, .7, .55]; // contoh bentuk profil, bukan data pengguna
    $drPt = fn (float $r, int $k) => [round(100 + $r * cos(-M_PI / 2 + $k * 2 * M_PI / 7), 1), round(100 + $r * sin(-M_PI / 2 + $k * 2 * M_PI / 7), 1)];
    $drTrialN = count(config('arah-karier.trial'));
    $drMin = (int) config('arah-karier.trial_minutes', 5);
@endphp
<section class="dr on-dark" id="arah-karier" data-guide-end aria-label="Cari arah kariermu atau coba simulasi">
    {{-- ============ Sisi kiri: karier ============ --}}
    <article class="dr-pane dr-career" aria-labelledby="dr-career-h">
        <span class="dr-glow" aria-hidden="true"></span>
        <span class="dr-edge" aria-hidden="true">Cari arah kariermu</span>
        <div class="dr-inner">
            <p class="dr-eyebrow"><span class="dr-dot" aria-hidden="true"></span>Cari arah kariermu</p>
            <h2 class="font-display dr-title" id="dr-career-h">Bingung mau ke mana? Biarkan <em>situasi nyata</em> yang menjawab.</h2>

            <div class="dr-viz" aria-hidden="true">
                <svg viewBox="0 0 200 200">
                    @foreach ([.33, .66, 1] as $m)
                        <polygon class="dr-grid" points="{{ collect(range(0, 6))->map(fn ($k) => implode(',', $drPt(66 * $m, $k)))->implode(' ') }}"/>
                    @endforeach
                    @foreach (range(0, 6) as $k)
                        <line class="dr-axis" x1="100" y1="100" x2="{{ $drPt(66, $k)[0] }}" y2="{{ $drPt(66, $k)[1] }}"/>
                    @endforeach
                    <polygon class="dr-shape" points="{{ collect($drSample)->map(fn ($v, $k) => implode(',', $drPt(66 * $v, $k)))->implode(' ') }}"/>
                    @foreach ($drSample as $k => $v)
                        <circle class="dr-node" style="--k: {{ $k }}" cx="{{ $drPt(66 * $v, $k)[0] }}" cy="{{ $drPt(66 * $v, $k)[1] }}" r="3.2"/>
                    @endforeach
                    @foreach ($drDims as $k => $label)
                        <text class="dr-lbl" x="{{ $drPt(88, $k)[0] }}" y="{{ $drPt(88, $k)[1] }}" text-anchor="middle" dominant-baseline="central">{{ $label }}</text>
                    @endforeach
                </svg>
                <span class="dr-chip dr-chip-1">Contoh hasil<b>Data Analyst</b></span>
                <span class="dr-chip dr-chip-2">Alasan<b>dari jawabanmu</b></span>
            </div>

            <div class="dr-detail">
                <div>
                    <p class="dr-lead">Dua puluh pertanyaan keahlian yang spesifik, bukan kuis iseng. Jawabanmu dipetakan ke tujuh dimensi kerja lalu dicocokkan dengan profil tiap karier.</p>
                    <ul class="dr-points">
                        <li><b>20</b><span>situasi kerja spesifik, tanpa perlu akun</span></li>
                        <li><b>7</b><span>dimensi kerja dibaca dari jawabanmu</span></li>
                    </ul>
                    <div class="dr-go">
                        <p class="dr-ask">Mulai coba karier?</p>
                        <a href="{{ route('careers.quiz') }}" class="btn-primary px-8 py-3.5 text-base">Cari arah kariermu</a>
                    </div>
                </div>
            </div>
        </div>
        <p class="dr-peek" aria-hidden="true"><span></span>Arahkan kursor untuk melihat detail</p>
    </article>

    {{-- ============ Sisi kanan: simulasi ============ --}}
    <article class="dr-pane dr-sim" aria-labelledby="dr-sim-h">
        <span class="dr-glow" aria-hidden="true"></span>
        <span class="dr-edge" aria-hidden="true">Coba simulasi</span>
        <div class="dr-inner">
            <p class="dr-eyebrow"><span class="dr-dot" aria-hidden="true"></span>Coba simulasi</p>
            <h2 class="font-display dr-title" id="dr-sim-h">Siap diuji? <em>{{ $drTrialN }} soal</em>, {{ $drMin }} menit saja.</h2>

            <div class="dr-viz dr-viz-sim" aria-hidden="true">
                <div class="dr-exam">
                    <div class="dr-exam-top">
                        <span class="dr-clock"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9.5 2.5h5"/></svg>{{ sprintf('%02d:32', max(0, $drMin - 1)) }}</span>
                        <span class="dr-exam-n">Soal 3 dari {{ $drTrialN }}</span>
                    </div>
                    <div class="dr-exam-bar"><i></i></div>
                    <p class="dr-exam-q">Ibu kota Provinsi Riau adalah...</p>
                    <ul class="dr-exam-opts">
                        <li style="--k: 0"><b>A</b>Dumai</li>
                        <li style="--k: 1" class="is-on"><b>B</b>Pekanbaru</li>
                        <li style="--k: 2"><b>C</b>Siak Sri Indrapura</li>
                        <li style="--k: 3"><b>D</b>Bengkalis</li>
                    </ul>
                </div>
                <span class="dr-chip dr-chip-1">Timer<b>{{ $drMin }} menit</b></span>
            </div>

            <div class="dr-detail">
                <div>
                    <p class="dr-lead">Soal kasus kerja nyata yang detail untuk merasakan alur simulasi yang sebenarnya, lengkap dengan timer. Langsung kerjakan tanpa akun.</p>
                    <ul class="dr-points">
                        <li><b>{{ $drTrialN }}</b><span>soal pilihan ganda, kasus kerja nyata</span></li>
                        <li><b>{{ $drMin }}:00</b><span>menit, timer berjalan setelah kamu mulai</span></li>
                    </ul>
                    <div class="dr-go">
                        <p class="dr-ask">Mulai coba simulasi?</p>
                        <a href="{{ route('exam.trial') }}" class="btn-primary px-8 py-3.5 text-base">Coba simulasi {{ $drMin }} menit</a>
                    </div>
                </div>
            </div>
        </div>
        <p class="dr-peek" aria-hidden="true"><span></span>Arahkan kursor untuk melihat detail</p>
    </article>
</section>
