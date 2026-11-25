@extends('errors.layout')

@php
    // Lama tunggu dari header Retry-After yang dikirim throttle; dibatasi 1 detik sampai 10 menit.
    $headers = method_exists($exception, 'getHeaders') ? $exception->getHeaders() : [];
    $wait = max(1, min(600, (int) ($headers['Retry-After'] ?? 30)));
@endphp

@section('title', 'Terlalu banyak permintaan')
@section('code', '429')
@section('heading', 'Pelan-pelan dulu ya')
@section('lead')
    Kamu mengirim terlalu banyak permintaan dalam waktu singkat. Istirahat sebentar, lalu coba lagi.
@endsection
@section('scene')
    @include('errors.scenes.e429')
@endsection
@section('extra')
    <div class="er-timer er-in" style="--d:.7s" data-er-wait="{{ $wait }}" role="timer" aria-label="Hitung mundur sebelum bisa mencoba lagi"><svg class="er-ring" viewBox="0 0 64 64" aria-hidden="true"><circle class="er-ring-bg" cx="32" cy="32" r="28"/><circle class="er-ring-fg" cx="32" cy="32" r="28" pathLength="100"/></svg><b data-er-left>{{ $wait }}</b><small>detik</small></div>
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ url()->previous(url('/')) }}" data-er-retry>Coba lagi</a>
        <a class="er-btn er-btn-ghost" href="{{ url('/') }}">Ke beranda</a>
@endsection
