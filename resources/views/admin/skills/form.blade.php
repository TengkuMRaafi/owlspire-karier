@extends('layouts.admin')

@php $editing = $skill->exists; @endphp
@section('title', $editing ? 'Ubah skill' : 'Tambah skill')
@section('heading', $editing ? 'Ubah skill' : 'Tambah skill')
@section('subheading', 'Alias adalah cara lain orang menulis skill ini di CV (mis. "js" untuk JavaScript).')
@section('actions')
    <a href="{{ route('admin.skills.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.skills.update', $skill) : route('admin.skills.store') }}" class="pn-card ad-block ad-form" autocomplete="off">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="ui-form-grid">
            <div class="ui-field"><label for="name">Nama skill</label><input id="name" name="name" value="{{ old('name', $skill->name) }}" class="ui-input" required maxlength="80"></div>
            <div class="ui-field"><label for="category">Kategori</label><input id="category" name="category" value="{{ old('category', $skill->category) }}" class="ui-input" maxlength="60" list="skill-cats" placeholder="mis. Pemrograman"><datalist id="skill-cats">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist></div>
            <div class="ui-field" style="grid-column:1/-1"><label for="aliases">Alias</label><textarea id="aliases" name="aliases" rows="5" class="ui-input" placeholder="satu per baris, atau pisahkan dengan koma">{{ old('aliases', collect($skill->aliases)->implode("\n")) }}</textarea><small>Pencarian tidak membedakan huruf besar/kecil dan hanya cocok pada kata utuh.</small></div>
        </div>
        <div class="ad-form-actions">
            <button type="submit" class="btn-primary">{{ $editing ? 'Simpan perubahan' : 'Tambah skill' }}</button>
            <a href="{{ route('admin.skills.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
