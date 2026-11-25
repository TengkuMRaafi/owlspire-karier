@extends('layouts.auth')

@section('title', 'Masuk')
@section('aside_title')Selamat datang kembali, <em>lanjutkan latihanmu.</em>@endsection

@section('content')
    <h1 class="au-h1 font-display">Masuk</h1>
    <p class="au-lead">Masuk untuk melanjutkan simulasi dan melihat riwayat hasil kamu.</p>

    @php $googleOnly = app(\App\Services\Auth\GoogleOAuth::class)->membersGoogleOnly(); @endphp
    @if ($googleOnly)
        @include('partials.google-button', ['label' => 'Masuk dengan Google', 'sep' => false])
    @endif

    @if ($googleOnly)
        <details class="au-staff" style="margin-top:1.25rem" @if ($errors->any() || old('email')) open @endif>
            <summary style="cursor:pointer;font-size:.875rem;font-weight:700;text-align:center">Admin atau staf? Masuk dengan email dan kata sandi</summary>
            <div style="margin-top:1rem">
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="au-form" novalidate>
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

        <div class="au-pass">
            <label for="password" class="label">Kata sandi</label>
            <div class="au-field">
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       @class(['field', 'border-red-600' => $errors->has('password')])
                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                <button type="button" data-toggle-password="password" class="au-eye" aria-controls="password" aria-pressed="false">Lihat</button>
            </div>
            @error('password')
                <p id="password-error" class="au-err" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <label class="au-remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Ingat saya di perangkat ini
            </label>
            <a href="{{ route('password.request') }}" class="au-alt-link" style="font-size:.875rem;font-weight:600">Lupa kata sandi?</a>
        </div>

        @include('partials.turnstile')

        <button type="submit" class="btn-primary au-submit">Masuk</button>

        @unless ($googleOnly)
            @include('partials.google-button', ['label' => 'Masuk dengan Google', 'sep' => 'atau', 'sepFirst' => true])
        @endunless
    </form>

    @if ($googleOnly)
            </div>
        </details>
    @endif

    <p class="au-alt">Belum punya akun? <a href="{{ route('register') }}">Daftar gratis</a></p>
@endsection
