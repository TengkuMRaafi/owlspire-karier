@extends('layouts.admin')

@php
    $editing = $type->exists;
    $rules = $type->passing_rules ?? [];
    $pts = (int) config('platform.exam.points_per_correct', 5);
    $hasSeg = $editing && $type->segments->count() > 0;
    $mode = old('pass_mode', ($hasSeg && ! empty($rules['segments'])) ? 'segment' : 'total');
@endphp
@section('title', $editing ? 'Ubah jenis ujian' : 'Tambah jenis ujian')
@section('heading', $editing ? 'Ubah jenis ujian' : 'Tambah jenis ujian')
@section('subheading', $editing ? $type->name.' ('.$type->code.')' : 'Setelah disimpan, tambahkan segmennya di halaman detail.')
@section('actions')
    <a href="{{ $editing ? route('admin.setup.show', $type) : route('admin.setup.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.setup.types.update', $type) : route('admin.setup.types.store') }}" class="pn-card ad-block ad-form">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="ui-form-grid">
            @unless ($editing)
                <div class="ui-field"><label for="code">Kode</label><input id="code" name="code" value="{{ old('code') }}" class="ui-input" placeholder="mis. kedinasan" required></div>
            @endunless
            <div class="ui-field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $type->name) }}" class="ui-input" required></div>
            <div class="ui-field is-wide"><label for="description">Deskripsi</label><input id="description" name="description" value="{{ old('description', $type->description) }}" class="ui-input"></div>
            <div class="ui-field"><label for="duration_minutes">Durasi penuh (mnt)</label><input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', (int) ($type->duration_seconds / 60)) }}" class="ui-input" required></div>
            <div class="ui-field"><label for="trial_minutes">Durasi coba (mnt)</label><input id="trial_minutes" type="number" name="trial_minutes" value="{{ old('trial_minutes', (int) ($type->trial_duration_seconds / 60)) }}" class="ui-input" required></div>
            @if ($editing)
                <div class="ui-field"><label for="position">Urutan</label><input id="position" type="number" name="position" value="{{ old('position', $type->position) }}" class="ui-input"></div>
            @endif
            <fieldset class="ui-field is-wide ad-pgf">
                <legend class="ui-label">Syarat lulus</legend>
                @if ($hasSeg)
                    <div class="ad-pm" role="radiogroup" aria-label="Cara menilai kelulusan">
                        <label><input type="radio" name="pass_mode" value="total" @checked($mode === 'total')><span>Total skor</span></label>
                        <label><input type="radio" name="pass_mode" value="segment" @checked($mode === 'segment')><span>Per segmen</span></label>
                    </div>
                @else
                    <input type="hidden" name="pass_mode" value="total">
                @endif
                <div class="ad-pgf-total">
                    <label for="min_percent" class="ad-pgf-l">Nilai minimal (% dari skor total)</label>
                    <input id="min_percent" type="number" step="0.1" min="0" max="100" inputmode="decimal" name="min_percent" value="{{ old('min_percent', $rules['min_percent'] ?? 60) }}" class="ui-input">
                    <small>Contoh: isi 60 artinya peserta lulus bila benar minimal 60% dari seluruh soal.</small>
                    @unless ($hasSeg)
                        <small>Penilaian per segmen bisa diatur setelah segmen ditambahkan.</small>
                    @endunless
                </div>
                @if ($hasSeg)
                    <div class="ad-pgf-seg">
                        <small>Peserta harus mencapai nilai minimal di setiap segmen. Kosongkan segmen yang tidak ikut dinilai.</small>
                        @foreach ($type->segments as $seg)
                            @php
                                $maks = (int) $seg->question_count_full * $pts;
                                $pct = $rules['segments'][$seg->code] ?? null;
                                $cur = old('seg_min.'.$seg->code, $pct !== null && $maks > 0 ? (int) round($pct / 100 * $maks) : '');
                            @endphp
                            <div class="ad-pgf-row">
                                <div><b>{{ $seg->name }}</b><small>Nilai penuh {{ $maks }} poin ({{ $seg->question_count_full }} soal)</small></div>
                                <input type="number" min="0" @if ($maks > 0) max="{{ $maks }}" @endif step="1" inputmode="numeric" name="seg_min[{{ $seg->code }}]" value="{{ $cur }}" class="ui-input" placeholder="Poin" aria-label="Nilai minimal {{ $seg->name }} (poin)">
                            </div>
                        @endforeach
                        @error('seg_min')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                @endif
            </fieldset>
        </div>
        <label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active))><i></i> Aktif</label>
        <div class="ad-form-actions">
            <button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah jenis ujian' }}</button>
            <a href="{{ $editing ? route('admin.setup.show', $type) : route('admin.setup.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
