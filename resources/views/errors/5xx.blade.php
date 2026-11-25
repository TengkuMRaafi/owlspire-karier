@php
    $status = $exception->getStatusCode();
    // [judul, penjelasan]; 502 dan 504 biasanya datang dari nginx, tetapi bila lewat Laravel tampil di sini.
    $copy = match ($status) {
        500 => ['Server lagi kami perbaiki', 'Terjadi kesalahan di sisi kami, bukan salahmu. Coba muat ulang beberapa saat lagi.'],
        502 => ['Server belum menjawab dengan benar', 'Bagian server kami sedang tidak nyambung. Tunggu sebentar lalu muat ulang.'],
        504 => ['Server kelamaan menjawab', 'Server kami butuh waktu lebih lama dari biasanya. Tunggu sebentar lalu coba lagi.'],
        default => ['Ada gangguan di sisi kami', 'Terjadi gangguan di server kami, bukan salahmu. Coba lagi beberapa saat lagi.'],
    };
    $scene = match ($status) { 502 => 'e502', 504 => 'e504', default => 'e500' };
@endphp

@extends('errors.layout')

@section('title', $copy[0])
@section('code', $status)
@section('heading', $copy[0])
@section('lead', $copy[1])
@section('scene')
    @include('errors.scenes.'.$scene)
@endsection
@section('actions')
    <a class="er-btn er-btn-primary" href="{{ request()->fullUrl() }}">Muat ulang</a>
    <a class="er-btn er-btn-ghost" href="{{ url('/') }}">Ke beranda</a>
@endsection
