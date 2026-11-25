@extends('errors.layout')

@section('title', 'Halaman tidak ditemukan')
@section('code', '404')
@section('heading', 'Tersesat?')
@section('lead')
    Halaman yang kamu cari tidak ada atau sudah dipindahkan. Periksa lagi alamatnya, atau kembali ke jalur yang benar.
@endsection
@section('scene')
    @include('errors.scenes.e404')
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ url('/') }}">Kembali ke beranda</a>
        <a class="er-btn er-btn-ghost" href="{{ url('/arah-karier') }}">Cari arah kariermu</a>
@endsection
