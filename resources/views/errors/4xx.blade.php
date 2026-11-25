@php
    $status = $exception->getStatusCode();
    // [judul, penjelasan]; kode yang tidak terdaftar memakai teks umum.
    $copy = match ($status) {
        400 => ['Permintaan tidak dikenali', 'Ada bagian alamat atau isian yang tidak bisa kami baca. Coba ulangi dari halaman sebelumnya.'],
        401 => ['Masuk dulu ya', 'Halaman ini hanya untuk yang sudah masuk. Masuk ke akunmu, lalu coba lagi.'],
        405 => ['Cara aksesnya keliru', 'Halaman ini tidak bisa dibuka dengan cara tadi. Kembali ke halaman sebelumnya dan ikuti tombol yang tersedia.'],
        408 => ['Koneksi kelamaan menunggu', 'Permintaanmu terlalu lama sampai ke kami. Periksa internetmu, lalu coba lagi.'],
        410 => ['Halaman ini sudah tiada', 'Halaman yang kamu cari sudah dihapus dan tidak akan kembali.'],
        422 => ['Isiannya belum bisa diproses', 'Ada isian yang tidak sesuai. Kembali, periksa lagi, lalu kirim ulang.'],
        default => ['Ada yang janggal', 'Permintaanmu tidak bisa kami proses. Coba kembali ke halaman sebelumnya atau mulai dari beranda.'],
    };
@endphp

@extends('errors.layout')

@section('title', $copy[0])
@section('code', $status)
@section('heading', $copy[0])
@section('lead', $copy[1])
@section('scene')
    @include('errors.scenes.e4xx')
@endsection
@section('actions')
    <a class="er-btn er-btn-primary" href="{{ url()->previous(url('/')) }}">Kembali</a>
    <a class="er-btn er-btn-ghost" href="{{ url('/') }}">Ke beranda</a>
@endsection
