@extends('layouts.admin')

@php $editing = $segment->exists; @endphp
@section('title', $editing ? 'Ubah segmen' : 'Tambah segmen')
@section('heading', $editing ? 'Ubah segmen' : 'Tambah segmen')
@section('subheading', $type->name)
@section('actions')
    <a href="{{ route('admin.setup.show', $type) }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.setup.segments.update', $segment) : route('admin.setup.segments.store', $type) }}" class="pn-card ad-block ad-form">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="ui-form-grid">
            <div class="ui-field">
                <label for="code">Kode</label>
                @if ($editing)
                    <input class="ui-input" value="{{ $segment->code }}" disabled><input type="hidden" name="code" value="{{ $segment->code }}">
                    <small>Kode dikunci supaya impor soal tidak putus.</small>
                @else
                    <input id="code" name="code" value="{{ old('code') }}" class="ui-input" placeholder="mis. twk" required>
                @endif
            </div>
            <div class="ui-field"><label for="name">Nama segmen</label><input id="name" name="name" value="{{ old('name', $segment->name) }}" class="ui-input" required></div>
            <div class="ui-field is-wide"><label for="description">Deskripsi</label><input id="description" name="description" value="{{ old('description', $segment->description) }}" class="ui-input"></div>
            <div class="ui-field"><label for="qt">Soal mode coba</label><input id="qt" type="number" name="question_count_trial" value="{{ old('question_count_trial', $segment->question_count_trial) }}" class="ui-input" required></div>
            <div class="ui-field"><label for="qf">Soal ujian penuh</label><input id="qf" type="number" name="question_count_full" value="{{ old('question_count_full', $segment->question_count_full) }}" class="ui-input" required></div>
        </div>
        <div class="ad-form-actions">
            <button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah segmen' }}</button>
            <a href="{{ route('admin.setup.show', $type) }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
