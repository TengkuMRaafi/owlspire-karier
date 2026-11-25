@extends('layouts.auth')

@section('title', 'Verifikasi email')
@section('aside_title')Pastikan email ini <em>memang milikmu.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Cek emailmu</h1>
    <p class="au-lead">Kami mengirim link verifikasi ke <b>{{ auth()->user()->email }}</b>. Klik link itu untuk mengonfirmasi. Belum masuk? Cek folder spam.</p>

    <form method="POST" action="{{ route('verification.send') }}" class="au-form">
        @csrf
        <button type="submit" class="btn-primary au-submit">Kirim ulang link verifikasi</button>
    </form>
    <p class="au-alt"><a href="{{ route('dashboard.index') }}">Lewati dulu, lanjut ke dashboard</a></p>
@endsection
