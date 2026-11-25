@extends('layouts.admin')

@php $editing = $event->exists; @endphp
@section('title', $editing ? 'Ubah jadwal seleksi' : 'Tambah jadwal seleksi')
@section('heading', $editing ? 'Ubah jadwal seleksi' : 'Tambah jadwal seleksi')
@section('subheading', 'Isi dari pengumuman resmi dan sertakan tautannya supaya pengunjung bisa mengecek sendiri.')
@section('actions')
    <a href="{{ route('admin.selections.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.selections.update', $event) : route('admin.selections.store') }}" class="pn-card ad-block ad-form">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="ui-form-grid">
            <div class="ui-field is-wide">
                <label for="title">Judul</label>
                <input id="title" name="title" value="{{ old('title', $event->title) }}" class="ui-input" maxlength="160" placeholder="mis. Pendaftaran CPNS 2026" required>
                @error('title')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="category">Kategori</label>
                <x-dropdown name="category" id="category" aria="Kategori" :options="\App\Models\SelectionEvent::CATEGORIES" :value="old('category', $event->category)" block />
                @error('category')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="stage">Tahap (opsional)</label>
                <input id="stage" name="stage" value="{{ old('stage', $event->stage) }}" class="ui-input" maxlength="60" placeholder="mis. Pendaftaran, SKD, Pengumuman">
                @error('stage')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="starts_on">Tanggal mulai</label>
                <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $event->starts_on?->toDateString()) }}" class="ui-input" required>
                @error('starts_on')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="ends_on">Tanggal selesai (opsional)</label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $event->ends_on?->toDateString()) }}" class="ui-input">
                <small>Kosongkan bila hanya satu hari.</small>
                @error('ends_on')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field is-wide">
                <label for="source_url">Tautan pengumuman resmi (opsional)</label>
                <input id="source_url" type="url" name="source_url" value="{{ old('source_url', $event->source_url) }}" class="ui-input" maxlength="500" placeholder="https://">
                @error('source_url')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field is-wide">
                <label for="note">Catatan (opsional)</label>
                <textarea id="note" name="note" rows="3" maxlength="1000" class="ui-input">{{ old('note', $event->note) }}</textarea>
                @error('note')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
        </div>
        <label class="ui-check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published))><i></i> Terbit (tampil ke pengunjung)</label>
        <div class="ad-form-actions">
            <button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah jadwal' }}</button>
            <a href="{{ route('admin.selections.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
