@extends('errors.layout')

@section('title', 'Sesi berakhir')
@section('code', '419')
@section('heading', 'Sesimu sudah habis')
@section('lead')
    Halaman ini terlalu lama dibiarkan terbuka, jadi sesinya berakhir demi keamanan. Muat ulang halamannya, lalu coba lagi.
@endsection
@section('scene')
    @include('errors.scenes.e419')
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ url()->previous(url('/')) }}">Muat ulang halaman</a>
        <a class="er-btn er-btn-ghost" href="{{ url('/') }}">Ke beranda</a>
@endsection
