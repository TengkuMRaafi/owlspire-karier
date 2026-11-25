<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1b2638">
@if (auth()->check() || request()->routeIs('exam.*', 'billing.*'))
<meta name="app-secure" content="1">
@endif
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- Perataan teks di seluruh situs: teks pendek/judul rata kiri dengan baris dirapikan; paragraf kalimat rata kiri-kanan lewat resources/js/rapi.js + css/base/rapi.css (kelas .rapi). --}}
<style>
    body { text-wrap: pretty; }
    :where(h1, h2, h3, h4) { text-wrap: balance; }
    :where(pre, code, textarea, input, select) { text-wrap: wrap; }
</style>
