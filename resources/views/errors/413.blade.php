@extends('errors.layout')

@section('title', 'Berkas terlalu besar')
@section('code', '413')
@section('heading', 'Berkasnya kebesaran')
@section('lead')
    Berkas yang kamu unggah melebihi batas ukuran. Kecilkan ukurannya (misalnya kompres PDF-nya), lalu unggah lagi.
@endsection
@section('scene')
    @include('errors.scenes.e413')
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ url()->previous(url('/')) }}">Kembali dan coba lagi</a>
        <a class="er-btn er-btn-ghost" href="{{ url('/') }}">Ke beranda</a>
@endsection
