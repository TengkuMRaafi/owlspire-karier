@extends('layouts.auth')

@section('title', 'Lupa kata sandi')
@section('aside_title')Tenang, akunmu <em>bisa dipulihkan.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Lupa kata sandi?</h1>
    <p class="au-lead">Masukkan email akunmu. Kami kirim link untuk membuat kata sandi baru.</p>

    @if (app(\App\Services\Auth\GoogleOAuth::class)->membersGoogleOnly())
        <p class="au-lead" style="font-weight:600">Halaman ini khusus admin dan staf. Pengguna biasa tidak memakai kata sandi: masuk saja dengan tombol Google.</p>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="au-form" novalidate>
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@email.com"
                   @class(['field', 'border-red-600' => $errors->has('email')])
                   @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="au-err" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @include('partials.turnstile')

        <button type="submit" class="btn-primary au-submit">Kirim link atur ulang</button>
    </form>

    <p class="au-alt">Ingat kata sandimu? <a href="{{ route('login') }}">Masuk</a></p>
@endsection
