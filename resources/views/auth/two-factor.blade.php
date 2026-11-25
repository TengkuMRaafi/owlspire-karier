@extends('layouts.auth')

@section('title', 'Verifikasi 2 langkah')
@section('aside_title')Satu lapis lagi, <em>supaya akunmu aman.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Kode verifikasi</h1>
    <p class="au-lead">Buka aplikasi autentikator di ponselmu, lalu masukkan kode 6 digit untuk {{ config('platform.brand.name') }}. Tidak punya ponselmu? Pakai salah satu kode pemulihan.</p>

    <form method="POST" action="{{ route('login.2fa.store') }}" class="au-form" novalidate>
        @csrf
        <div>
            <label for="code" class="label">Kode 6 digit atau kode pemulihan</label>
            <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" required autofocus maxlength="20" placeholder="123456"
                   @class(['field', 'border-red-600' => $errors->has('code')]) style="letter-spacing:.15em">
            @error('code')<p class="au-err" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary au-submit">Verifikasi</button>
    </form>

    <p class="au-alt"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
@endsection
