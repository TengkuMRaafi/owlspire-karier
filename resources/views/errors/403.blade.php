@extends('errors.layout')

@section('title', 'Akses ditolak')
@section('code', '403')
@section('heading', 'Gerbang ini terkunci')
@section('lead')
    Kamu belum punya izin untuk membuka halaman ini. Kalau merasa seharusnya bisa, masuk dengan akun yang tepat atau hubungi admin.
@endsection
@section('scene')
    @include('errors.scenes.e403')
@endsection
@section('actions')
        <a class="er-btn er-btn-primary" href="{{ url('/') }}">Kembali ke beranda</a>
        <a class="er-btn er-btn-ghost" href="{{ url('/masuk') }}">Masuk dengan akun lain</a>
@endsection
