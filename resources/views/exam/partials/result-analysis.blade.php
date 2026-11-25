{{-- Analisis lanjutan di tab Detail (ujian penuh). --}}
@php
    $segs = $insights['segments'];
    $sn = fn ($x) => preg_match('/\(([^)]+)\)/', $x, $m) ? $m[1] : $x;
    $recMax = max(1, (int) collect($segs)->max('recoverable'));
    $recTotal = (int) collect($segs)->sum('recoverable');
    $allowed = $st['allowed_seconds'];
    $times = collect($segs)->filter(fn ($s) => $s['avg_seconds'] !== null);
    $tMax = max(1, (int) ceil(max($allowed ? $allowed * 1.4 : 0, $times->max('avg_seconds') ?? 0)));
    $slow = $times->filter(fn ($s) => $allowed && $s['avg_seconds'] > $allowed);
    $hasPeers = ! in_array(null, array_column($segs, 'peer_pct'), true) && $segs !== [];
    $below = collect($segs)->filter(fn ($s) => $s['threshold'] !== null && $s['gap'] < 0);
@endphp

<div class="ra">
    <section class="ra-card">
        <h3 class="rs-h">Poin yang masih bisa diambil</h3>
        <div class="ra-time">
            @foreach ($segs as $s)
                <div>
                    <div class="ra-time-top"><b>{{ $sn($s['name']) }}</b><span>+{{ $s['recoverable'] }} poin &middot; {{ $s['lost'] }} soal</span></div>
                    <div class="ra-bar" role="img" aria-label="{{ $sn($s['name']) }} masih bisa menambah {{ $s['recoverable'] }} poin"><i style="width: {{ $s['recoverable'] / $recMax * 100 }}%; background: var(--acc)"></i></div>
                </div>
            @endforeach
        </div>
        <p class="ra-why">Kalau semua soal salah dan kosong bisa kamu benarkan, skormu bertambah sekitar <b>{{ $recTotal }} poin</b>. Mulai dari batang paling panjang karena itu bagian dengan peluang terbesar.</p>
    </section>

    <section class="ra-card">
        <h3 class="rs-h">Skor vs ambang{{ $hasPeers ? ' dan peserta' : '' }}</h3>
        <div class="ra-time">
            @foreach ($segs as $s)
                @php $thr = $s['threshold'] ?? $overallMin; @endphp
                <div>
                    <div class="ra-time-top"><b>{{ $sn($s['name']) }}</b><span>{{ $s['pct'] }}%@if ($thr !== null) &middot; ambang {{ rtrim(rtrim(number_format($thr, 1), '0'), '.') }}%@endif @if ($s['peer_pct'] !== null) &middot; peserta {{ $s['peer_pct'] }}%@endif</span></div>
                    <div @class(['ra-bar', 'is-slow' => $thr !== null && $s['pct'] < $thr]) role="img" aria-label="{{ $sn($s['name']) }} {{ $s['pct'] }} persen">
                        <i style="width: {{ min(100, $s['pct']) }}%"></i>
                        @if ($thr !== null)<u style="left: {{ min(100, $thr) }}%"></u>@endif
                        @if ($s['peer_pct'] !== null)<s style="position:absolute;top:-.2rem;bottom:-.2rem;width:3px;border-radius:2px;background:#b7791f;left: {{ min(100, $s['peer_pct']) }}%"></s>@endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="ra-why">
            Garis hitam adalah ambang lulus{{ $hasPeers ? ', garis kuning rata-rata peserta' : '' }}.
            @if ($below->isNotEmpty()) Batang merah berarti masih di bawah ambang: <b>{{ $below->map(fn ($s) => $sn($s['name']))->implode(', ') }}</b>.
            @else Semua batang sudah melewati garis ambang. @endif
        </p>
    </section>

    @if ($times->isNotEmpty())
        <section class="ra-card is-wide">
            <h3 class="rs-h">Waktu per soal tiap bagian</h3>
            <div class="ra-time">
                @foreach ($times as $s)
                    @php $isSlow = $allowed && $s['avg_seconds'] > $allowed; @endphp
                    <div>
                        <div class="ra-time-top"><b>{{ $sn($s['name']) }}</b><span>{{ $fmtSec($s['avg_seconds']) }}@if ($allowed) &middot; jatah {{ $fmtSec($allowed) }}@endif</span></div>
                        <div @class(['ra-bar', 'is-slow' => $isSlow]) role="img" aria-label="{{ $sn($s['name']) }} rata-rata {{ $s['avg_seconds'] }} detik per soal"><i style="width: {{ min(100, $s['avg_seconds'] / $tMax * 100) }}%"></i>@if ($allowed)<u style="left: {{ min(100, $allowed / $tMax * 100) }}%"></u>@endif</div>
                    </div>
                @endforeach
            </div>
            <p class="ra-why">
                @if ($slow->isNotEmpty()) Waktumu melewati jatah di <b>{{ $slow->map(fn ($s) => $sn($s['name']))->implode(', ') }}</b>. Garis hitam adalah jatah rata-rata per soal; latih bagian itu dengan timer supaya tidak menghabiskan waktu bagian lain.
                @else Tempo pengerjaanmu masih dalam jatah per soal. Garis hitam adalah batas jatah rata-rata. @endif
            </p>
        </section>
    @endif
</div>

