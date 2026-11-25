<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Laporan Analisis CV - {{ $user->name }}</title>
    <style>
        *{box-sizing:border-box}body{font:14px/1.55 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111827;margin:0;padding:2rem;background:#fff}
        .wrap{max-width:780px;margin:0 auto}h1{font-size:1.5rem;margin:0}h2{font-size:1.05rem;margin:1.6rem 0 .6rem;border-bottom:1px solid #e5e7eb;padding-bottom:.3rem}
        .muted{color:#6b7280;font-size:.85rem}.top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}
        .score{font-size:2.6rem;font-weight:800;line-height:1}.bar{height:8px;border-radius:99px;background:#e5e7eb;overflow:hidden}.bar i{display:block;height:100%}
        .row{display:grid;grid-template-columns:1fr 60px;gap:.4rem 1rem;align-items:center;margin:.35rem 0}
        .issue{padding:.55rem .75rem;border:1px solid #e5e7eb;border-radius:.6rem;margin:.4rem 0;break-inside:avoid}.issue b{display:block}
        .tags{display:flex;flex-wrap:wrap;gap:.35rem;padding:0;list-style:none;margin:.3rem 0}.tags li{border:1px solid #d1d5db;border-radius:99px;padding:.1rem .6rem;font-size:.82rem}
        table{width:100%;border-collapse:collapse}td,th{padding:.35rem .4rem;border-bottom:1px solid #e5e7eb;text-align:left;font-size:.85rem}
        .btn{display:inline-block;padding:.5rem 1rem;border:1px solid #111827;border-radius:.5rem;background:#111827;color:#fff;cursor:pointer;font:inherit}
        @media print{.noprint{display:none}body{padding:0}}
    </style>
</head>
<body>
<div class="wrap">
    <p class="noprint"><button class="btn" onclick="window.print()">Cetak / simpan sebagai PDF</button></p>
    @php $in = $insights; $sc = (int) $in['score']; @endphp
    <div class="top">
        <div><h1>Laporan Analisis CV</h1><p class="muted">{{ $user->name }} &middot; dibuat {{ now()->translatedFormat('d F Y') }} &middot; {{ config('platform.brand.name') }}</p></div>
        <div style="text-align:right"><div class="score">{{ $sc }}<span style="font-size:1rem;font-weight:500">/100</span></div><div class="muted">{{ $in['grade'] }}</div></div>
    </div>

    @if (! empty($ats))
        <h2>Kesiapan ATS untuk {{ $ats['role'] }}: {{ (int) $ats['score'] }}/100 ({{ $ats['grade'] }})</h2>
        <p class="muted">{{ $ats['coverage'] }}% kata kunci jabatan tampil di CV (rumpun {{ $ats['family_label'] }}).</p>
        <p><b>Sudah tampil:</b></p>
        <ul class="tags">@forelse ($ats['found'] as $k)<li>{{ $k['label'] }}</li>@empty<li>Belum ada</li>@endforelse</ul>
        <p><b>Belum tampil:</b></p>
        <ul class="tags">@forelse ($ats['missing'] as $k)<li>{{ $k['label'] }}</li>@empty<li>Semua kata kunci utama tampil</li>@endforelse</ul>
        @foreach (collect($ats['checks'])->where('ok', false) as $c)
            <div class="issue"><b>{{ $c['label'] }}</b><span class="muted">{{ $c['tip'] }}</span></div>
        @endforeach
        <p class="muted">Kata kunci disusun dari pola umum lowongan jabatan ini: panduan, bukan jaminan lolos.</p>
    @endif

    <h2>Rincian skor</h2>
    @foreach ($in['parts'] as $p)
        @php $pct = $p['max'] ? round($p['score'] / $p['max'] * 100) : 0; @endphp
        <div class="row"><div>{{ $p['label'] }}<div class="bar"><i style="width:{{ $pct }}%;background:{{ $pct >= 75 ? '#16a34a' : ($pct >= 45 ? '#d97706' : '#dc2626') }}"></i></div></div><b>{{ (int) $p['score'] }}/{{ $p['max'] }}</b></div>
    @endforeach
    <p class="muted">{{ $in['metrics']['words'] }} kata &middot; pengalaman {{ $in['experience']['years'] !== null ? rtrim(rtrim(number_format($in['experience']['years'], 1, ',', ''), '0'), ',').' tahun' : 'tidak terbaca' }} &middot; pendidikan {{ $in['education']['level'] ?? '-' }} &middot; {{ $in['metrics']['numbers'] }} pencapaian ber-angka</p>

    @if ($in['issues'])
        <h2>Yang perlu diperbaiki</h2>
        @foreach ($in['issues'] as $i)
            <div class="issue"><b>{{ $i['title'] }}</b><span class="muted">{{ $i['tip'] }}</span></div>
        @endforeach
    @endif

    @if ($in['strengths'])
        <h2>Sudah bagus</h2>
        <ul>@foreach ($in['strengths'] as $g)<li>{{ $g }}</li>@endforeach</ul>
    @endif

    <h2>Skill terbaca ({{ $skills->count() }})</h2>
    <ul class="tags">@forelse ($skills as $s)<li>{{ $s['name'] }}</li>@empty<li>Belum ada</li>@endforelse</ul>

    @if ($top->isNotEmpty())
        <h2>Karier yang paling cocok</h2>
        <table>
            <thead><tr><th>Karier</th><th>Kecocokan</th><th>Celah skill</th></tr></thead>
            <tbody>
                @foreach ($top as $m)
                    <tr><td>{{ $m['career']->name }}</td><td><b>{{ $m['score'] }}%</b></td><td>{{ collect($m['missing'])->take(5)->implode(', ') ?: '-' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($history->count() > 1)
        <h2>Riwayat skor</h2>
        <table>
            <thead><tr><th>Tanggal</th><th>Versi</th><th>Skor</th></tr></thead>
            <tbody>@foreach ($history->reverse()->take(8) as $h)<tr><td>{{ $h->created_at->translatedFormat('d M Y H:i') }}</td><td>{{ $h->label ?? '-' }}</td><td><b>{{ $h->score }}</b></td></tr>@endforeach</tbody>
        </table>
    @endif

    <p class="muted" style="margin-top:2rem">Penilaian otomatis berbasis aturan sebagai panduan awal. Sesuaikan CV dengan lowongan yang kamu tuju.</p>
</div>
</body>
</html>
