@extends('layouts.auth')

@section('title', 'Pendaftaran ditutup')

@section('content')
    <h1 class="au-h1 font-display">Pendaftaran ditutup sementara</h1>
    <p class="au-lead">Akun baru belum bisa dibuat saat ini. Sudah punya akun? Kamu tetap bisa masuk seperti biasa.</p>
    <a href="{{ route('login') }}" class="btn-primary au-submit" style="display:block;text-align:center">Masuk</a>
    <p class="au-alt"><a href="{{ route('home') }}">Kembali ke beranda</a></p>
@endsection
