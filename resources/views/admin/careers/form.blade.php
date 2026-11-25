@extends('layouts.admin')

@php $editing = $career->exists; @endphp
@section('title', $editing ? 'Ubah karier' : 'Tambah karier')
@section('heading', $editing ? 'Ubah karier' : 'Tambah karier')
@section('subheading', $editing ? $career->name : 'Simulasi dengan tiga bagian dibuat otomatis. Soalnya ditambahkan di Bank soal.')
@section('actions')
    <a href="{{ route('admin.careers.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <div class="ad-uf">
    <form method="POST" action="{{ $editing ? route('admin.careers.update', $career) : route('admin.careers.store') }}" class="pn-card ad-block ad-form">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="ui-form-grid">
            <div class="ui-field"><label for="name">Nama karier</label><input id="name" name="name" value="{{ old('name', $career->name) }}" class="ui-input" required></div>
            <div class="ui-field"><label for="division">Divisi / bidang</label><input id="division" name="division" value="{{ old('division', $career->division) }}" class="ui-input" list="divs" required>
                <datalist id="divs">@foreach ($divisions as $d)<option value="{{ $d }}">@endforeach</datalist></div>
            <div class="ui-field is-wide"><label for="summary">Ringkasan satu kalimat</label><input id="summary" name="summary" value="{{ old('summary', $career->summary) }}" class="ui-input" required></div>
            @if ($editing)
                <div class="ui-field"><label for="rank">Urutan</label><input id="rank" type="number" name="rank" value="{{ old('rank', $career->rank) }}" class="ui-input" min="1" required></div>
                <div class="ui-field"><label for="tags">Tag tes arah karier (pisah koma)</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $career->detail('tags', []))) }}" class="ui-input" placeholder="analitis, teknis"><small>Pilihan: analitis, kreatif, sosial, teknis, lapangan, bisnis.</small></div>
                <div class="ui-field is-wide"><label for="tren">Catatan tren (isi hanya bila ada sumbernya)</label><input id="tren" name="tren" value="{{ old('tren', $career->detail('tren')) }}" class="ui-input"></div>
            @endif
        </div>
        @if ($editing)
            <label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $career->is_active))><i></i> Tampilkan di situs</label>
        @endif

        <div class="ad-form-actions">
            <button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah & buat simulasi' }}</button>
            <a href="{{ route('admin.careers.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>

    <aside class="pn-card ad-ov ad-pv" aria-label="Pratinjau karier">
        <p class="ad-ov-k">Pratinjau kartu karier</p>
        <div class="ad-pv-card">
            <span class="ui-badge is-accent" data-bind="division" data-empty="Divisi"></span>
            <b class="ad-pv-name" data-bind="name" data-empty="Nama karier"></b>
            <p class="ad-pv-sum" data-bind="summary" data-empty="Ringkasan satu kalimat akan tampil di sini."></p>
            <div class="ad-pv-chips" data-chips="tags" hidden></div>
            @if ($editing)
                <span class="ui-badge has-dot is-success" data-check="is_active">Tampil di situs</span>
            @endif
        </div>
        <p class="ad-hint">Ringkasan yang terlalu panjang dipotong di kartu. Tulis satu kalimat singkat.</p>
    </aside>
    </div>
@include('admin.partials.live-preview')
@endsection
