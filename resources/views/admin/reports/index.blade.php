@extends('layouts.admin')

@section('title', 'Laporan')
@section('heading', 'Laporan')
@section('subheading', 'Rekap Owlspire · '.$label)
@section('actions')
    <x-export-button :href="route('admin.reports.download', ['periode' => $period])" />
@endsection

@section('content')
    @php
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $careerTop = array_slice($careers, 0, 3);
    @endphp

    <style>
        .rp4-bar { display: flex; align-items: center; gap: .9rem; padding: .85rem 1.1rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: 1.1rem; background: var(--pn-card, #fff); }
        .rp4-ic { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .75rem; background: var(--color-accent-100, #dce8fb); color: var(--color-accent-700, #2f58b3); }
        .rp4-ic svg { width: 1.15rem; height: 1.15rem; }
        .rp4-tx { flex: 1; display: grid; gap: .1rem; min-width: 0; }
        .rp4-tx b { font-size: .95rem; font-weight: 800; color: var(--color-graphite-900, #1b2638); }
        .rp4-tx small { font-size: .78rem; line-height: 1.35; color: var(--color-graphite-500, #667794); }
        .rp4-bar .ui-dd { flex: 0 0 15rem; }
        @media (max-width: 640px) {
            .rp4-bar { flex-wrap: wrap; gap: .65rem .75rem; padding: .85rem .9rem; }
            .rp4-bar .ui-dd { flex: 1 1 100%; }
        }
        .rp3-split { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; align-items: stretch; }
        @media (min-width: 900px) { .rp3-split { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; } }
        .rp3-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: 1.2rem; background: var(--pn-card, #fff); }
        .rp3-h { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; padding: .8rem 1.1rem; border-bottom: 1px solid var(--color-graphite-100, #edf0f5); }
        .rp3-h b { font-size: .95rem; font-weight: 800; color: var(--color-graphite-900, #1b2638); }
        .rp3-h span { font-size: .8rem; color: var(--color-graphite-500, #667794); }
        .rp3-list { flex: 1; display: flex; flex-direction: column; margin: 0; padding: .1rem 1.1rem; list-style: none; }
        .rp3-list > li { flex: 1; display: flex; align-items: center; gap: .7rem; padding: .55rem 0; }
        .rp3-list > li + li { border-top: 1px dashed var(--color-graphite-200, #e2e6ec); }
        .rp3-rank { flex: none; display: grid; place-items: center; width: 1.5rem; height: 1.5rem; border-radius: .5rem; background: var(--color-graphite-100, #edf0f5); font-size: .75rem; font-weight: 800; color: var(--color-graphite-600, #4a5a73); font-variant-numeric: tabular-nums; }
        .rp3-rank.is-first { background: var(--color-accent-600, #3b6cd4); color: #fff; }
        .rp3-main { flex: 1; display: grid; gap: .3rem; min-width: 0; }
        .rp3-line { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .rp3-line > b { min-width: 0; font-size: .88rem; font-weight: 700; line-height: 1.3; color: var(--color-graphite-800, #1f2a3d); }
        .rp3-val { flex: none; font-size: .9rem; font-weight: 800; white-space: nowrap; font-variant-numeric: tabular-nums; color: var(--color-graphite-900, #1b2638); }
        .rp3-val small { font-size: .72rem; font-weight: 600; color: var(--color-graphite-500, #667794); }
        .rp3-mrow { display: flex; align-items: center; gap: .6rem; }
        .rp3-mrow .rp3-meter { flex: 1; }
        .rp3-meter { height: .35rem; overflow: hidden; border-radius: 999px; background: var(--color-graphite-100, #edf0f5); }
        .rp3-meter i { display: block; height: 100%; min-width: 2px; border-radius: inherit; background: linear-gradient(90deg, var(--color-accent-500, #5b86e0), var(--color-accent-600, #3b6cd4)); }
        .rp3-meter.is-success i { background: #2bb673; }
        .rp3-meter.is-warning i { background: #e8a92c; }
        .rp3-meter.is-danger i { background: #e05555; }
        .rp3-sub { flex: none; font-size: .72rem; color: var(--color-graphite-500, #667794); }
        .rp3-empty { justify-content: center; padding: 1.5rem 0 !important; color: var(--color-graphite-500, #667794); }
        .rp3-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .65rem 1.1rem; border-top: 1px solid var(--color-graphite-100, #edf0f5); background: var(--color-graphite-50, #f6f8fb); font-size: .78rem; color: var(--color-graphite-500, #667794); }
        .rp3-foot b { font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; color: var(--color-graphite-800, #1f2a3d); }
    </style>

    <div class="pn-stack">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="rp4-bar">
            <span class="rp4-ic" aria-hidden="true"><x-icon name="calendar" /></span>
            <div class="rp4-tx"><b>Periode laporan</b><small>Mengatur semua tabel di bawah dan isi file Excel.</small></div>
            <x-dropdown name="periode" :options="$periods" :value="$period" aria="Periode laporan" submit />
        </form>

        <div class="ui-table-wrap">
            <div class="ui-table-bar"><b class="text-graphite-800">Ujian seleksi</b></div>
            <div class="ui-table-scroll">
                <table class="ui-table is-compact">
                    <thead><tr><th>Ujian</th><th class="is-num">Selesai</th><th class="is-num">Rata-rata skor</th><th class="is-num">Tingkat lulus</th></tr></thead>
                    <tbody>
                        @forelse ($exams as $r)
                            <tr>
                                <td><b>{{ $r['name'] }}</b></td>
                                <td class="is-num">{{ number_format($r['total']) }}</td>
                                <td class="is-num">{{ $r['avg'] }}</td>
                                <td class="is-num">{{ $r['full'] > 0 ? $r['pass_rate'].'%' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="ui-table-empty">Belum ada ujian seleksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $maxSims = max(1, (int) collect($careerTop)->max('sims'));
            $allSims = (int) array_sum(array_column($careers, 'sims'));
            $allTargets = (int) array_sum(array_column($careers, 'targets'));
            $ordTotal = (int) collect($orders)->sum('count');
            $ordMoney = (int) collect($orders)->sum('amount');
        @endphp
        <div class="rp3-split">
            <div class="rp3-card">
                <div class="rp3-h"><b>Karier teratas</b><span>3 dari {{ count($careers) }}</span></div>
                <ol class="rp3-list">
                    @forelse ($careerTop as $i => $r)
                        <li>
                            <span @class(['rp3-rank', 'is-first' => $i === 0])>{{ $i + 1 }}</span>
                            <div class="rp3-main">
                                <div class="rp3-line"><b>{{ $r['name'] }}</b><span class="rp3-val">{{ number_format($r['sims']) }}<small> simulasi</small></span></div>
                                <div class="rp3-mrow">
                                    <div class="rp3-meter"><i style="width: {{ round($r['sims'] / $maxSims * 100) }}%"></i></div>
                                    <small class="rp3-sub">{{ number_format($r['targets']) }} peminat</small>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="rp3-empty">Belum ada karier.</li>
                    @endforelse
                </ol>
                <div class="rp3-foot"><span>Semua karier</span><b>{{ number_format($allSims) }} simulasi &middot; {{ number_format($allTargets) }} peminat</b></div>
            </div>

            @if ($business)
                <div class="rp3-card">
                    <div class="rp3-h"><b>Pesanan</b><span>{{ $label }}</span></div>
                    <ul class="rp3-list">
                        @foreach ($orders as $r)
                            @php
                                $tone = $r['status'] === 'paid' ? 'is-success' : ($r['status'] === 'pending' ? 'is-warning' : 'is-danger');
                                $share = $ordMoney > 0 ? round($r['amount'] / $ordMoney * 100) : 0;
                            @endphp
                            <li>
                                <div class="rp3-main">
                                    <div class="rp3-line"><span class="ui-badge has-dot {{ $tone }}">{{ $r['label'] }}</span><span class="rp3-val">{{ $rp($r['amount']) }}</span></div>
                                    <div class="rp3-mrow">
                                        <div @class(['rp3-meter', $tone])><i style="width: {{ $share }}%"></i></div>
                                        <small class="rp3-sub">{{ number_format($r['count']) }} pesanan</small>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="rp3-foot"><span>Total</span><b>{{ number_format($ordTotal) }} pesanan &middot; {{ $rp($ordMoney) }}</b></div>
                </div>
            @endif
        </div>

        <p class="rp-foot">Rincian lengkap (semua karier, ujian penuh, jumlah lulus, total) ada di file Excel.</p>
    </div>
@endsection
