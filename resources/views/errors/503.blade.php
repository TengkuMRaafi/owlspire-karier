@extends('errors.layout')

@php
    // Dipakai juga oleh maintenance.blade.php (mode pemeliharaan dari panel admin) yang mengirim $message.
    $msg = filled($message ?? null) ? $message : 'Kami sedang melakukan pemeliharaan singkat. Halaman ini memuat ulang sendiri begitu Owlspire kembali.';
@endphp

@section('title', 'Sedang pemeliharaan')
@section('code', '503')
@section('heading', 'Sedang dirapikan')
@section('lead')
    {{ $msg }}
@endsection
@section('scene')
    @include('errors.scenes.e503')
@endsection
@section('extra')
    <div class="er-poll er-in" style="--d:.7s" data-er-poll="{{ url()->current() }}"><span class="er-bar" aria-hidden="true"><i></i></span><span>Mengecek lagi dalam <b data-er-poll-left>30</b> detik</span></div>
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ request()->fullUrl() }}">Coba lagi sekarang</a>
@endsection
@section('note')
    Admin atau staf? <a href="{{ url('/masuk') }}">Masuk</a>
@endsection
