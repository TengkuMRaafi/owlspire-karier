@extends('layouts.auth')

@section('title', 'Cek emailmu')
@section('aside_title')Satu langkah lagi: <em>konfirmasi emailmu.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Cek emailmu</h1>
    <p class="au-lead">Kami mengirim link verifikasi ke <b>{{ $email }}</b>. Klik link itu untuk mengaktifkan akunmu, lalu masuk. Belum ada? Cek folder spam.</p>

    <form method="POST" action="{{ route('verification.resend') }}" class="au-form">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <button type="submit" class="btn-primary au-submit">Kirim ulang link verifikasi</button>
        <p class="au-hint" style="text-align:center">Link berlaku terbatas waktu. Pengiriman ulang dibatasi beberapa kali per 10 menit.</p>
    </form>

    <p class="au-alt">Salah email? <a href="{{ route('register') }}">Daftar ulang</a> &middot; <a href="{{ route('login') }}">Masuk</a></p>
@endsection
