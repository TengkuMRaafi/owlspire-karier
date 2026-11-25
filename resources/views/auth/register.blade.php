@extends('layouts.auth')

@section('title', 'Daftar')
@section('aside_title')Mulai perjalanan <em>kariermu</em> bersama Owlspire.@endsection

@section('content')
    <h1 class="au-h1 font-display">Buat akun</h1>
    <p class="au-lead">Gratis. Simpan riwayat simulasi dan ulangi kapan saja. Daftar cepat dengan Google, atau pakai emailmu sendiri.</p>

    @if ($googleOnly ?? false)
        @include('partials.google-button', ['label' => 'Daftar dengan Google', 'sep' => false])
    @endif

    @if ($googleOnly ?? false)
        <p class="au-lead" style="margin-top:1rem">Akun dibuat dengan akun Google-mu supaya emailnya pasti milikmu. Kami hanya menerima nama dan email, bukan kata sandi Google-mu.</p>
    @else
    <form method="POST" action="{{ route('register.store') }}" class="au-form" novalidate>
        @csrf

        @foreach ([['name', 'Nama', 'text', 'name', 'Nama lengkap'], ['email', 'Email', 'email', 'username', 'nama@email.com']] as [$id, $label, $type, $auto, $ph])
            <div>
                <label for="{{ $id }}" class="label">{{ $label }}</label>
                <input id="{{ $id }}" name="{{ $id }}" type="{{ $type }}" value="{{ old($id) }}" required autocomplete="{{ $auto }}" placeholder="{{ $ph }}"
                       @class(['field', 'border-red-600' => $errors->has($id)])
                       @if ($errors->has($id)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
                @error($id)
                    <p id="{{ $id }}-error" class="au-err" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="au-pass">
            <label for="password" class="label">Kata sandi</label>
            <div class="au-field">
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       @class(['field', 'border-red-600' => $errors->has('password')])
                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                <button type="button" data-toggle-password="password" class="au-eye" aria-controls="password" aria-pressed="false">Lihat</button>
            </div>
            <p class="au-hint">Minimal 8 karakter, campuran huruf dan angka.</p>
            @error('password')
                <p id="password-error" class="au-err" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
        </div>

        @include('partials.turnstile')

        <button type="submit" class="btn-primary au-submit">Daftar dengan email</button>
        <p class="au-hint" style="text-align:center">Kami kirim link verifikasi ke emailmu. Akun aktif setelah link diklik.</p>

        @include('partials.google-button', ['label' => 'Daftar dengan Google', 'sep' => 'atau', 'sepFirst' => true])
    </form>
    @endif

    <p class="au-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
@endsection
