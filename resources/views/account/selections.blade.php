@extends('layouts.dashboard')

@section('title', 'Info seleksi')
@section('heading', 'Info seleksi')
@section('subheading', 'Jadwal pendaftaran dan tahapan seleksi, lengkap dengan hitung mundur.')

@section('content')
    <div class="pn-stack" style="max-width:56rem;margin-inline:auto">
        @if ($upcoming->isNotEmpty())
            @include('selection.partials.next', ['event' => $upcoming->first()])
        @endif

        @include('selection.partials.filters', ['route' => 'account.selections', 'category' => $category])

        <section class="pn-card" aria-labelledby="ac-up">
            <div class="pn-card-h"><h2 id="ac-up">Akan datang dan berlangsung</h2></div>
            @if ($upcoming->isNotEmpty())
                @include('selection.partials.list', ['events' => $upcoming])
            @else
                <p class="text-sm text-graphite-500">Belum ada jadwal{{ $category !== '' ? ' untuk kategori ini' : '' }}. Pantau halaman ini, jadwal baru ditambahkan begitu pengumuman resminya keluar.</p>
            @endif
        </section>

        @if ($past->isNotEmpty())
            <section class="pn-card" aria-labelledby="ac-past">
                <div class="pn-card-h"><h2 id="ac-past">Sudah lewat</h2></div>
                @include('selection.partials.list', ['events' => $past])
            </section>
        @endif

        <section class="pn-card" aria-labelledby="ac-portal">
            <div class="pn-card-h"><h2 id="ac-portal">Portal resmi</h2></div>
            @include('selection.partials.portals', ['portals' => $portals])
        </section>
    </div>
@endsection
