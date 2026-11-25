@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Ringkasan sistem Owlspire hari ini.')

@section('content')
    {{-- Tampilan HP: kartu angka rapi (tanpa kartu yatim), grafik 7 hari; inline supaya langsung jalan tanpa build ulang --}}
    <style>
        /* Grafik aktivitas: sumbu Y + garis bantu, angka di atas batang, hari ini disorot, tooltip saat disentuh/diarahkan */
        /* Kartu uang: latar biru bergradasi supaya jadi sorotan utama */
        body .ad-dash .ui-stat.is-hero { border-color: transparent; background: linear-gradient(135deg, var(--color-accent-600, #3b6cd4), var(--color-accent-800, #254690)); box-shadow: 0 16px 30px -20px rgb(37 70 144 / .8); }
        body .ad-dash .ui-stat.is-hero .ui-stat-l, body .ad-dash .ui-stat.is-hero .ui-stat-h { color: rgb(255 255 255 / .78); }
        body .ad-dash .ui-stat.is-hero .ui-stat-ic { background: rgb(255 255 255 / .18); color: #fff; }
        body .ad-dash .ui-stat.is-hero .ui-stat-v { color: #fff; }
        body .ad-dash .ad-legend b { margin-left: .1rem; font-variant-numeric: tabular-nums; color: var(--color-ink, #1b2638); }
        body .ad-dash .ad-c { display: grid; grid-template-columns: 1.7rem minmax(0, 1fr); grid-template-rows: 13rem auto; column-gap: .6rem; margin-top: 1.3rem; }
        body .ad-dash .ad-c-y { position: relative; }
        body .ad-dash .ad-c-y span { position: absolute; right: 0; transform: translateY(50%); font-size: .68rem; line-height: 1; font-variant-numeric: tabular-nums; color: var(--color-graphite-400, #94a3bb); }
        body .ad-dash .ad-c-area { position: relative; display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); gap: .25rem; }
        body .ad-dash .ad-c-grid { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none; }
        body .ad-dash .ad-c-grid i { display: block; height: 0; border-top: 1px dashed var(--color-graphite-200, #e2e6ec); }
        body .ad-dash .ad-c-grid i:last-child { border-top: 1px solid var(--color-graphite-300, #c5cfde); }
        body .ad-dash .ad-c-col { position: relative; z-index: 1; display: flex; align-items: flex-end; justify-content: center; gap: .2rem; padding: 0 .15rem; border-radius: .7rem; outline: none; transition: background .2s; }
        body .ad-dash .ad-c-col:hover, body .ad-dash .ad-c-col:focus-visible { background: var(--color-graphite-100, #edf0f5); }
        body .ad-dash .ad-c-col.is-today { background: rgb(59 108 212 / .07); }
        body .ad-dash .ad-c-bar { position: relative; width: 100%; max-width: 1.7rem; min-height: 3px; border-radius: .4rem .4rem .15rem .15rem; background: linear-gradient(180deg, var(--color-accent-500, #5b86e0), var(--color-accent-600, #3b6cd4)); transform-origin: bottom; animation: ad-grow .7s cubic-bezier(.2, .8, .2, 1) both; animation-delay: calc(var(--i) * 45ms); }
        body .ad-dash .ad-c-bar.is-b { background: linear-gradient(180deg, var(--color-graphite-600, #4a5a73), var(--color-graphite-800, #1f2a3d)); }
        body .ad-dash .ad-c-bar.is-zero { opacity: .28; }
        body .ad-dash .ad-c-bar em { position: absolute; left: 50%; bottom: calc(100% + .2rem); transform: translateX(-50%); font-style: normal; font-size: .68rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; color: var(--color-graphite-700, #34435c); }
        body .ad-dash .ad-c-col::after { content: attr(data-tip); position: absolute; z-index: 5; bottom: calc(100% + .35rem); left: 50%; transform: translate(-50%, 4px); width: max-content; max-width: 12rem; padding: .35rem .6rem; border-radius: .5rem; background: var(--color-ink, #1b2638); color: #fff; font-size: .72rem; font-weight: 600; line-height: 1.3; opacity: 0; pointer-events: none; transition: opacity .15s, transform .15s; }
        body .ad-dash .ad-c-col:hover::after, body .ad-dash .ad-c-col:focus-visible::after { opacity: 1; transform: translate(-50%, 0); }
        body .ad-dash .ad-c-col:nth-child(-n+3 of .ad-c-col)::after { left: 0; transform: translate(0, 4px); }
        body .ad-dash .ad-c-col:nth-child(-n+3 of .ad-c-col):hover::after, body .ad-dash .ad-c-col:nth-child(-n+3 of .ad-c-col):focus-visible::after { transform: translate(0, 0); }
        body .ad-dash .ad-c-col:last-child::after { left: auto; right: 0; transform: translate(0, 4px); }
        body .ad-dash .ad-c-col:last-child:hover::after, body .ad-dash .ad-c-col:last-child:focus-visible::after { transform: translate(0, 0); }
        body .ad-dash .ad-c-x { grid-column: 2; display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); gap: .25rem; margin-top: .45rem; }
        body .ad-dash .ad-c-x small { display: grid; justify-items: center; gap: .1rem; padding: .3rem 0; border-radius: .6rem; line-height: 1.1; font-variant-numeric: tabular-nums; color: var(--color-graphite-500, #667794); }
        body .ad-dash .ad-c-x small b { font-size: .7rem; font-weight: 700; }
        body .ad-dash .ad-c-x small span { font-size: .95rem; font-weight: 800; color: var(--color-graphite-800, #1f2a3d); }
        body .ad-dash .ad-c-x small.is-today { background: var(--color-accent-600, #3b6cd4); color: rgb(255 255 255 / .85); }
        body .ad-dash .ad-c-x small.is-today span { color: #fff; }
        /* Tabel jadi kartu di HP: nama satu baris (dipotong …), nomor/keterangan di bawahnya, jadi tinggi tiap kartu seragam */
        @media (max-width: 640px) {
            body .ad-dash .ui-table.is-compact .ui-cell { display: grid; gap: .1rem; width: 100%; }
            body .ad-dash .ui-table:not(.no-stack) .ui-cell b,
            body .ad-dash .ui-table:not(.no-stack) .ui-cell small { display: block; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        }
        @media (max-width: 639px) {
            body .ad-dash .ui-stats, body .ad-dash .ui-stats.is-compact { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
            body .ad-dash .ui-stats > :last-child:nth-child(odd) { grid-column: auto; }
            body .ad-dash .ui-stats > .is-wide { grid-column: 1 / -1; }
            body .ad-dash .ui-stat, body .ad-dash .ui-stats.is-compact .ui-stat { padding: .85rem .95rem; border-radius: 1rem; }
            body .ad-dash .ui-stat-l { font-size: .64rem; letter-spacing: .06em; }
            body .ad-dash .ui-stat-v, body .ad-dash .ui-stats.is-compact .ui-stat-v { margin-top: .4rem; font-size: 1.6rem; white-space: nowrap; }
            body .ad-dash .ui-stat-h { font-size: .72rem; line-height: 1.35; }
            body .ad-dash .ad-c { grid-template-rows: 12.5rem auto; grid-template-columns: 1.2rem minmax(0, 1fr); column-gap: .4rem; }
            body .ad-dash .ad-c-area, body .ad-dash .ad-c-x { gap: .2rem; }
            body .ad-dash .ad-c-col { gap: .12rem; padding: 0 .1rem; }
            body .ad-dash .ad-c-bar { max-width: .95rem; }
            body .ad-dash .ad-c-x small b { font-size: .64rem; }
            body .ad-dash .ad-c-x small span { font-size: .88rem; }
            body .ad-dash .ad-legend { gap: .4rem .9rem; }
        }
    </style>

    @php
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $badge = ['paid' => ['success', 'Lunas'], 'pending' => ['warning', 'Menunggu'], 'failed' => ['danger', 'Ditolak'], 'expired' => ['danger', 'Kedaluwarsa']];
        $premiumPct = $kpi['users'] > 0 ? round($kpi['premium_users'] / $kpi['users'] * 100) : 0;
    @endphp

    <div class="pn-stack ad-dash">
        @if ($waiting > 0)
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="ad-queue">
                <b>{{ $waiting }} pembayaran menunggu verifikasi kamu</b>
                <span>Periksa sekarang <x-icon name="arrow-right" /></span>
            </a>
        @endif

        @php
            $cards = [
                ['Pengguna', number_format($kpi['users']), '+'.$kpi['users_week'].' minggu ini', 'users', false],
                ...($business ? [
                    ['Premium aktif', number_format($kpi['premium_users']), $premiumPct.'% dari pengguna', 'crown', true],
                    ['Pendapatan', $rp($kpi['revenue']), $rp($kpi['revenue_month']).' bulan ini', 'card', false],
                ] : []),
                ['Ujian selesai', number_format($kpi['attempts']), $kpi['full_attempts'].' ujian penuh', 'bars', false],
                ['Bank soal', number_format($kpi['questions']), 'soal tersedia', 'list', false],
            ];
            // HP (2 kolom): Pendapatan selebar baris; tanpa Pendapatan, kartu terakhir yang ganjil dilebarkan
            $wideAt = $business ? 2 : (count($cards) % 2 === 1 ? count($cards) - 1 : -1);
        @endphp
        <div class="ui-stats is-compact">
            @foreach ($cards as $i => [$label, $value, $hint, $icon, $accent])
                <div @class(['ui-stat', 'is-accent' => $accent, 'is-wide' => $i === $wideAt, 'is-hero' => $label === 'Pendapatan'])>
                    <div class="ui-stat-top"><span class="ui-stat-l">{{ $label }}</span><span class="ui-stat-ic"><x-icon :name="$icon" /></span></div>
                    <p class="ui-stat-v">{{ $value }}</p>
                    <p class="ui-stat-h">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        <div class="pn-split">
            <div class="pn-card">
                @php
                    $cMax = max(4, (int) ceil($seriesMax / 4) * 4);
                    $cCount = $series->count();
                    $sumA = $series->sum('attempts');
                    $sumU = $series->sum('users');
                @endphp
                <div class="pn-card-h">
                    <h2>Aktivitas 7 hari terakhir</h2>
                    <div class="ad-legend"><span><i></i>Ujian dimulai <b>{{ $sumA }}</b></span><span><i class="is-b"></i>Pengguna baru <b>{{ $sumU }}</b></span></div>
                </div>
                <div class="ad-c" style="--n: {{ $cCount }}" role="img" aria-label="Grafik batang ujian dimulai dan pengguna baru 7 hari terakhir">
                    <div class="ad-c-y" aria-hidden="true">
                        @foreach ([2, 1, 0] as $t)
                            <span style="bottom: {{ $t * 50 }}%">{{ (int) ($cMax * $t / 2) }}</span>
                        @endforeach
                    </div>
                    <div class="ad-c-area">
                        <div class="ad-c-grid" aria-hidden="true"><i></i><i></i><i></i></div>
                        @foreach ($series as $i => $d)
                            <div @class(['ad-c-col', 'is-today' => $loop->last]) style="--i: {{ $i }}" tabindex="0" data-tip="{{ $d['label'] }} &middot; {{ $d['attempts'] }} ujian, {{ $d['users'] }} pengguna baru">
                                <span @class(['ad-c-bar', 'is-zero' => $d['attempts'] === 0]) style="height: {{ $d['attempts'] / $cMax * 100 }}%">@if ($d['attempts'] > 0)<em>{{ $d['attempts'] }}</em>@endif</span>
                                <span @class(['ad-c-bar', 'is-b', 'is-zero' => $d['users'] === 0]) style="height: {{ $d['users'] / $cMax * 100 }}%">@if ($d['users'] > 0)<em>{{ $d['users'] }}</em>@endif</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="ad-c-x" aria-hidden="true">
                        @foreach ($series as $d)
                            <small @class(['is-today' => $loop->last])><b>{{ $d['dow'] }}</b><span>{{ $d['day'] }}</span></small>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pn-card">
                @if ($business)
                    <div class="pn-card-h"><h2>Komposisi paket</h2><a href="{{ route('admin.users.index') }}" class="ui-linkbtn">Lihat</a></div>
                    <div class="ui-meter" role="img" aria-label="{{ $premiumPct }} persen pengguna Premium"><i style="width: {{ $premiumPct }}%"></i></div>
                    <ul class="ad-list" style="margin-top:1rem">
                        <li><span class="ui-badge is-premium">Premium</span><b class="tabular-nums">{{ number_format($kpi['premium_users']) }}</b></li>
                        <li><span class="ui-badge">Gratis</span><b class="tabular-nums">{{ number_format($kpi['free_users']) }}</b></li>
                    </ul>
                @else
                    <div class="pn-card-h"><h2>Percobaan per ujian</h2><a href="{{ route('admin.attempts.index') }}" class="ui-linkbtn">Lihat</a></div>
                    <ul class="ad-list">
                        @foreach ($types->take(5) as $t)
                            <li><span>{{ $t->name }}</span><b class="tabular-nums">{{ $t->attempts_count }}</b></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div @class(['pn-split', 'is-even'])>
            @if ($business)
                <div class="ui-table-wrap">
                    <div class="ui-table-bar"><b class="text-graphite-800">Pesanan terbaru</b><span class="ui-table-bar-end"><a href="{{ route('admin.orders.index') }}" class="ui-linkbtn">Semua <x-icon name="arrow-right" class="size-3.5" /></a></span></div>
                    <div class="ui-table-scroll">
                        <table class="ui-table is-compact">
                            <thead><tr><th>Pembeli</th><th class="is-num">Jumlah</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentOrders as $o)
                                    <tr>
                                        <td><span class="ui-cell"><b>{{ $o->user?->name ?? 'Tamu' }}</b><small>{{ $o->invoice_no }}</small></span></td>
                                        <td class="is-num"><b>{{ $rp($o->amount) }}</b></td>
                                        <td><span class="ui-badge has-dot is-{{ $badge[$o->status][0] ?? '' }}">{{ $badge[$o->status][1] ?? $o->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="ui-table-empty">Belum ada pesanan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <div class="ui-table-wrap">
                <div class="ui-table-bar"><b class="text-graphite-800">Hasil ujian terbaru</b><span class="ui-table-bar-end"><a href="{{ route('admin.attempts.index') }}" class="ui-linkbtn">Semua <x-icon name="arrow-right" class="size-3.5" /></a></span></div>
                <div class="ui-table-scroll">
                    <table class="ui-table is-compact">
                        <thead><tr><th>Peserta</th><th>Ujian</th><th class="is-num">Skor</th></tr></thead>
                        <tbody>
                            @forelse ($recentAttempts as $a)
                                <tr>
                                    <td><span class="ui-cell"><b>{{ $a->user?->name ?? 'Tamu' }}</b><small>{{ $a->mode === 'full' ? 'penuh' : 'coba' }}</small></span></td>
                                    <td>{{ $a->type->name }}</td>
                                    <td class="is-num"><b>{{ $a->total_score }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="ui-table-empty">Belum ada ujian selesai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
