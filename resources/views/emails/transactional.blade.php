{{-- Templat email sistem (verifikasi, reset sandi, login baru). Tabel + gaya inline supaya aman di Gmail/Outlook.
     Logo ditanam langsung di email (CID bernama tetap 'owl-mark'), jadi tampil walau APP_URL berupa tunnel atau localhost. --}}
@php
    $brand = config('platform.brand.name');
    $tagline = mb_strtoupper(config('platform.brand.tagline'));
    $font = 'Arial,Helvetica,sans-serif';
    $ink = '#1f2a3d'; $muted = '#5f6b7a'; $blue = '#2f58b3'; $rule = '#d8dee8';
    $logoPath = public_path('images/email/owl-mark.png');
    $logo = null;
    if (isset($message) && is_file($logoPath)) {
        $message->getSymfonyMessage()->embedFromPath($logoPath, 'owl-mark', 'image/png');
        $logo = 'cid:owl-mark';
    }
    $preheader = \Illuminate\Support\Str::limit(strip_tags((string) ($lines[0] ?? '')), 110, '');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>{{ $subject }}</title>
<style>
    body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table { border-collapse: collapse; }
    img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
    @media only screen and (max-width: 480px) {
        .outer { padding: 0 !important; }
        .card { border-radius: 0 !important; }
        .hd { padding: 18px 20px !important; }
        .px { padding-left: 20px !important; padding-right: 20px !important; }
        .btn, .btn a { width: 100% !important; }
        .btn a { display: block !important; text-align: center !important; }
        .wm { font-size: 28px !important; }
    }
</style>
</head>
<body style="margin:0;padding:0;background:#eef2f8;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">{{ $preheader }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f8;">
<tr><td class="outer" align="center" style="padding:24px 12px;">
<table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">

    <tr><td class="hd" style="background:{{ $blue }};padding:24px 32px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            @if ($logo)
            <td valign="middle" style="padding:0 14px 0 0;"><img src="{{ $logo }}" width="46" height="54" alt="" style="display:block;width:46px;height:54px;"></td>
            @endif
            <td valign="middle" style="padding:0;">
                <div class="wm" style="font-family:{{ $font }};font-size:32px;line-height:32px;font-weight:bold;letter-spacing:-0.8px;"><span style="color:#ffffff;">Owl</span><span style="color:#a9c4f2;">spire</span></div>
                <div style="margin-top:6px;font-family:{{ $font }};font-size:9px;line-height:12px;font-weight:bold;letter-spacing:1.6px;color:#c9d8f5;">{{ $tagline }}</div>
            </td>
        </tr></table>
    </td></tr>

    <tr><td class="px" style="padding:32px 32px 8px;font-family:{{ $font }};color:{{ $ink }};">
        <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;font-weight:bold;color:{{ $ink }};">{{ $greeting }}</h1>
        @foreach ($lines as $line)
            <p style="margin:0 0 14px;font-size:15px;line-height:1.65;color:{{ $ink }};">{!! nl2br(e($line)) !!}</p>
        @endforeach
    </td></tr>

    @if (! empty($details))
    <tr><td class="px" style="padding:0 32px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f7fc;border:1px solid {{ $rule }};border-radius:8px;">
            @foreach ($details as $label => $value)
            <tr>
                <td style="padding:10px 16px;font-family:{{ $font }};font-size:13px;color:{{ $muted }};width:34%;vertical-align:top;">{{ $label }}</td>
                <td style="padding:10px 16px;font-family:{{ $font }};font-size:13px;font-weight:bold;color:{{ $ink }};vertical-align:top;">{{ $value }}</td>
            </tr>
            @endforeach
        </table>
    </td></tr>
    @endif

    @if (! empty($actionUrl))
    <tr><td class="px" align="left" style="padding:16px 32px 8px;">
        <table role="presentation" class="btn" cellpadding="0" cellspacing="0" border="0"><tr>
            <td align="center" style="background:{{ $blue }};border-radius:8px;">
                <a href="{{ $actionUrl }}" target="_blank" style="display:inline-block;padding:14px 28px;font-family:{{ $font }};font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">{{ $actionText }}</a>
            </td>
        </tr></table>
    </td></tr>
    @endif

    <tr><td class="px" style="padding:16px 32px 28px;font-family:{{ $font }};color:{{ $ink }};">
        @foreach ($outro as $line)
            <p style="margin:0 0 12px;font-size:13.5px;line-height:1.65;color:{{ $muted }};">{!! nl2br(e($line)) !!}</p>
        @endforeach
        <p style="margin:18px 0 0;font-size:14px;line-height:1.6;color:{{ $ink }};">{{ $salutation }}</p>
    </td></tr>

    @if (! empty($actionUrl))
    <tr><td class="px" style="padding:16px 32px;border-top:1px solid {{ $rule }};font-family:{{ $font }};font-size:12px;line-height:1.6;color:{{ $muted }};">
        Tombol tidak berfungsi? Salin tautan ini ke browser:<br>
        <a href="{{ $actionUrl }}" target="_blank" style="color:{{ $blue }};word-break:break-all;">{{ $actionUrl }}</a>
    </td></tr>
    @endif

    <tr><td align="center" style="background:#f4f7fc;padding:16px 24px;font-family:{{ $font }};font-size:11.5px;line-height:1.6;color:{{ $muted }};">
        Email otomatis dari {{ $brand }}. Mohon tidak membalas email ini.
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
