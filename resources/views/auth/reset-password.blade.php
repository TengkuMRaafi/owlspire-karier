@extends('layouts.auth')

@section('title', 'Atur ulang kata sandi')
@section('aside_title')Buat kata sandi baru, <em>lalu lanjutkan.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Kata sandi baru</h1>
    <p class="au-lead">Pilih kata sandi yang belum pernah kamu pakai di tempat lain.</p>

    <form method="POST" action="{{ route('password.update') }}" class="au-form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username" @class(['field', 'border-red-600' => $errors->has('email')])>
            @error('email')<p class="au-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="au-pass">
            <label for="password" class="label">Kata sandi baru</label>
            <div class="au-field">
                <input id="password" name="password" type="password" required autofocus autocomplete="new-password" @class(['field', 'border-red-600' => $errors->has('password')])>
                <button type="button" data-toggle-password="password" class="au-eye" aria-controls="password" aria-pressed="false">Lihat</button>
            </div>
            <p class="au-hint">Minimal 8 karakter, campuran huruf dan angka.</p>
            @error('password')<p class="au-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
        </div>

        <button type="submit" class="btn-primary au-submit">Simpan kata sandi</button>
    </form>

    <p class="au-alt"><a href="{{ route('password.request') }}">Minta link baru</a></p>
@endsection
