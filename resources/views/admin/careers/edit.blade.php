@extends('layouts.admin')

@section('title', 'Ubah karier')
@section('heading', $career->name)

@section('content')
    <form method="POST" action="{{ route('admin.careers.update', $career) }}" class="pn-card ad-block" style="max-width:48rem">
        @csrf @method('PUT')
        <div class="ui-form-grid">
            <div class="ui-field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $career->name) }}" class="ui-input" required></div>
            <div class="ui-field"><label for="division">Divisi</label><input id="division" name="division" value="{{ old('division', $career->division) }}" class="ui-input" required></div>
            <div class="ui-field is-wide"><label for="summary">Ringkasan</label><input id="summary" name="summary" value="{{ old('summary', $career->summary) }}" class="ui-input" required></div>
            <div class="ui-field"><label for="rank">Urutan</label><input id="rank" type="number" name="rank" value="{{ old('rank', $career->rank) }}" class="ui-input" min="1" required></div>
            <div class="ui-field"><label for="tags">Tag tes arah karier (pisah koma)</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $career->detail('tags', []))) }}" class="ui-input" placeholder="analitis, teknis"><small>Pilihan: analitis, kreatif, sosial, teknis, lapangan, bisnis.</small></div>
            <div class="ui-field is-wide"><label for="tren">Catatan tren (isi hanya bila ada sumbernya)</label><input id="tren" name="tren" value="{{ old('tren', $career->detail('tren')) }}" class="ui-input"></div>
        </div>
        <label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $career->is_active))><i></i> Tampilkan di situs</label>
        <div class="flex gap-3"><button class="btn-primary">Simpan</button><a href="{{ route('admin.careers.index') }}" class="btn-outline">Batal</a></div>
    </form>
@endsection
