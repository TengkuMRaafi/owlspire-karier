@extends('layouts.auth')

@section('title', 'Ganti kata sandi')
@section('aside_title')Satu langkah singkat, <em>lalu lanjut.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Ganti kata sandi</h1>
    <p class="au-lead">Admin meminta kamu membuat kata sandi baru sebelum melanjutkan. Masukkan kata sandi sementara dari admin, lalu pilih yang baru.</p>

    <form method="POST" action="{{ route('password.force.update') }}" class="au-form" novalidate>
        @csrf @method('PUT')
        <div>
            <label for="current_password" class="label">Kata sandi saat ini</label>
            <input id="current_password" name="current_password" type="password" required autofocus autocomplete="current-password" @class(['field', 'border-red-600' => $errors->has('current_password')])>
            @error('current_password')<p class="au-err" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="label">Kata sandi baru</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" @class(['field', 'border-red-600' => $errors->has('password')])>
            <p class="au-hint">Minimal 8 karakter, campuran huruf dan angka.</p>
            @error('password')<p class="au-err" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Ulangi kata sandi baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
        </div>
        <button type="submit" class="btn-primary au-submit">Simpan dan lanjut</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="au-alt">@csrf<button type="submit" style="background:none;border:0;color:inherit;text-decoration:underline;cursor:pointer">Keluar</button></form>
@endsection
