{{-- Templat email broadcast. Tata letak tabel + gaya inline supaya aman di Gmail/Outlook; @media untuk layar HP. --}}
@php
    $brand = config('platform.brand.name');
    $tagline = mb_strtoupper(config('platform.brand.tagline'));
    $font = "Arial,Helvetica,sans-serif";
    $ink = '#1f2a3d'; $muted = '#5f6b7a'; $blue = '#2f58b3'; $rule = '#d8dee8';
    $paragraphs = $broadcast->paragraphs();
    $preheader = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', (string) $broadcast->body), 110, '');
    $hasCta = filled($broadcast->cta_url) && filled($broadcast->cta_label);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>{{ $broadcast->subject }}</title>
<style>
    body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table { border-collapse: collapse; }
    img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
    @media only screen and (max-width: 480px) {
        .outer { padding: 0 !important; }
        .card { border-radius: 0 !important; }
        .hd { padding: 18px 20px !important; }
        .logo-img { width: 42px !important; height: 49px !important; }
        .wm { font-size: 29px !important; line-height: 29px !important; }
        .tg { font-size: 8px !important; line-height: 11px !important; letter-spacing: 1.2px !important; white-space: normal !important; }
        .px { padding-left: 20px !important; padding-right: 20px !important; }
        .ttl { font-size: 21px !important; }
        .btn { width: 100% !important; }
        .btn a { display: block !important; text-align: center !important; }
    }
</style>
</head>
<body style="margin:0;padding:0;background:#eef2f8;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">{{ $preheader }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f8;">
<tr><td class="outer" align="center" style="padding:24px 12px;">
<table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">

    @if ($isTest)
    <tr><td align="center" style="background:#fff4d6;padding:8px 16px;font-family:{{ $font }};font-size:12px;color:#7a5600;">Ini email tes. Tidak dikirim ke pengguna.</td></tr>
    @endif

    {{-- Header: logo di kiri, nama + tagline di kanan, sejajar tengah. --}}
    <tr><td class="hd" style="background:{{ $blue }};padding:24px 32px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td valign="middle" style="padding:0 14px 0 0;">
                <img class="logo-img" src="{{ asset('images/email/owl-mark.png') }}" width="52" height="61" alt="" style="display:block;width:52px;height:61px;">
            </td>
            <td valign="middle" style="padding:0;">
                <div class="wm" style="font-family:{{ $font }};font-size:34px;line-height:34px;font-weight:bold;letter-spacing:-0.8px;"><span style="color:#ffffff;">Owl</span><span style="color:#a9c4f2;">spire</span></div>
                <div class="tg" style="margin-top:7px;font-family:{{ $font }};font-size:9px;line-height:12px;font-weight:bold;letter-spacing:1.6px;color:#c9d8f5;white-space:nowrap;">{{ $tagline }}</div>
            </td>
        </tr></table>
    </td></tr>

    <tr><td class="px" style="padding:28px 32px 10px;font-family:{{ $font }};color:{{ $ink }};">
        <span style="display:inline-block;font-size:11px;font-weight:bold;letter-spacing:1px;padding:4px 10px;border-radius:4px;background:#e6efff;color:#1f3f8a;">{{ mb_strtoupper($broadcast->kindLabel()) }}</span>
        <h1 class="ttl" style="margin:14px 0 12px;font-family:{{ $font }};font-size:22px;line-height:1.3;font-weight:bold;color:{{ $ink }};">{{ $broadcast->title }}</h1>
        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:{{ $ink }};">Halo, {{ $recipientName }},</p>
        @foreach ($paragraphs as $p)
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:{{ $ink }};">{!! nl2br(e($p)) !!}</p>
        @endforeach

        {{-- Blok tambahan menurut jenis --}}
        @if ($broadcast->kind === 'jadwal' && $broadcast->detailLines())
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 16px;background:#f3f6fb;border-radius:8px;">
                <tr><td style="padding:14px 16px;font-family:{{ $font }};font-size:14px;line-height:1.8;color:{{ $ink }};">
                    @foreach ($broadcast->scheduleRows() as $row)
                        @if ($row['label'])<b>{{ $row['label'] }}:</b> {{ $row['value'] }}@else{{ $row['value'] }}@endif<br>
                    @endforeach
                </td></tr>
            </table>
        @elseif ($broadcast->kind === 'diskon' && (filled($broadcast->highlight) || $broadcast->detailLines()))
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 16px;border:2px dashed {{ $blue }};border-radius:8px;">
                <tr><td align="center" style="padding:16px;font-family:{{ $font }};">
                    @if (filled($broadcast->highlight))
                        <div style="font-size:11px;font-weight:bold;letter-spacing:1px;color:{{ $muted }};">KODE PROMO</div>
                        <div style="margin:4px 0;font-size:26px;line-height:1.2;font-weight:bold;letter-spacing:3px;color:{{ $blue }};">{{ $broadcast->highlight }}</div>
                    @endif
                    @foreach ($broadcast->detailLines() as $line)
                        <div style="font-size:13px;line-height:1.6;color:{{ $muted }};">{{ $line }}</div>
                    @endforeach
                </td></tr>
            </table>
        @elseif ($broadcast->kind === 'karier' && $broadcast->detailLines())
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 16px;">
                @foreach ($broadcast->jobItems() as $item)
                    <tr><td style="padding:10px 0;border-bottom:1px solid {{ $rule }};font-family:{{ $font }};font-size:14px;line-height:1.5;color:{{ $ink }};">
                        <b>{{ $item['title'] }}</b>@if ($item['sub'])<br><span style="color:{{ $muted }};">{{ $item['sub'] }}</span>@endif
                    </td></tr>
                @endforeach
            </table>
        @endif

        @if ($hasCta)
            <table role="presentation" class="btn" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 10px;">
                <tr><td align="center" bgcolor="{{ $blue }}" style="border-radius:6px;">
                    <a href="{{ $broadcast->cta_url }}" target="_blank" style="display:inline-block;padding:13px 26px;font-family:{{ $font }};font-size:15px;font-weight:bold;line-height:1.2;color:#ffffff;text-decoration:none;border-radius:6px;">{{ $broadcast->cta_label }}</a>
                </td></tr>
            </table>
            <p style="margin:0 0 8px;font-size:12px;line-height:1.5;color:{{ $muted }};word-break:break-all;">Kalau tombol tidak bisa dibuka, salin tautan ini: <a href="{{ $broadcast->cta_url }}" style="color:{{ $blue }};">{{ $broadcast->cta_url }}</a></p>
        @endif
    </td></tr>

    <tr><td class="px" style="background:#f3f6fb;border-top:1px solid {{ $rule }};padding:18px 32px;font-family:{{ $font }};font-size:12px;line-height:1.7;color:{{ $muted }};">
        <b style="color:{{ $ink }};">{{ $brand }} &middot; {{ config('platform.brand.tagline') }}</b><br>
        Kamu menerima email ini karena terdaftar di {{ $brand }}.<br>
        <a href="{{ $unsubscribeUrl }}" style="color:{{ $blue }};text-decoration:underline;">Berhenti menerima info</a>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
