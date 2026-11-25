@extends('layouts.auth')

@section('title', $optedOut ? 'Info dihentikan' : 'Berhenti menerima info')
@section('aside_title')Kamu yang <em>atur sendiri.</em>@endsection

@section('content')
    @if ($optedOut)
        <h1 class="au-h1 font-display">Kamu sudah berhenti menerima info</h1>
        @if (session('status'))<p class="au-lead"><b>{{ session('status') }}</b></p>@endif
        <p class="au-lead">Halo, {{ $name }}. Email info dari {{ config('platform.brand.name') }} (jadwal seleksi, diskon, karier baru) tidak akan dikirim lagi. Email penting akunmu, seperti verifikasi dan reset sandi, tetap dikirim.</p>
        <form method="POST" action="{{ $action }}" class="au-form">
            @csrf
            <input type="hidden" name="aksi" value="aktifkan">
            <button type="submit" class="btn-outline au-submit">Aktifkan lagi</button>
        </form>
    @else
        <h1 class="au-h1 font-display">Berhenti menerima info?</h1>
        @if (session('status'))<p class="au-lead"><b>{{ session('status') }}</b></p>@endif
        <p class="au-lead">Halo, {{ $name }}. Kalau kamu berhenti, kamu tidak akan menerima email info dari {{ config('platform.brand.name') }} (jadwal seleksi, diskon, karier baru). Email penting akunmu tetap dikirim.</p>
        <form method="POST" action="{{ $action }}" class="au-form">
            @csrf
            <button type="submit" class="btn-primary au-submit">Ya, berhenti menerima info</button>
        </form>
    @endif

    <p class="au-alt"><a href="{{ route('home') }}">Ke beranda {{ config('platform.brand.name') }}</a></p>
@endsection
