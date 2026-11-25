@extends('layouts.admin')

@php $editing = $lesson->exists; @endphp
@section('title', $editing ? 'Ubah video' : 'Tambah video')
@section('heading', $editing ? 'Ubah video' : 'Tambah video')
@section('subheading', 'Tempel tautan YouTube; thumbnail dan tombol play dibuat otomatis.')
@section('actions')
    <a href="{{ route('admin.learning.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <div class="ad-uf">
    <form method="POST" action="{{ $editing ? route('admin.learning.update', $lesson) : route('admin.learning.store') }}" class="pn-card ad-block ad-form">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="ui-form-grid">
            @unless ($editing)
                <div class="ui-field"><label for="course_id-btn">Jalur belajar</label><x-dropdown name="course_id" id="course_id-btn" block aria="Jalur belajar" :options="$courses->pluck('title', 'id')->all()" :value="old('course_id')" /></div>
            @endunless
            @unless ($editing)
                <div class="ui-field is-wide"><label for="topic_id">Pasang ke topik jalur (opsional)</label>
                    @php($topicOpts = collect($topics)->mapWithKeys(fn ($t) => [(string) $t['id'] => $t['label'].' (jalur #'.$t['course_id'].')'])->all())
                    <x-dropdown name="topic_id" id="topic_id" block aria="Pasang ke topik jalur" all="Video tambahan (bukan topik)" :options="$topicOpts" :value="old('topic_id')" /><small>Topik yang dipilih akan memakai video ini. Pastikan jalur belajar di atas sesuai topiknya.</small></div>
            @endunless
            <div class="ui-field"><label for="title">Judul</label><input id="title" name="title" class="ui-input" value="{{ old('title', $lesson->title) }}" required></div>
            @unless ($editing)
                <div class="ui-field is-wide"><label for="url">Tautan atau ID video YouTube</label><input id="url" name="url" class="ui-input" value="{{ old('url') }}" required></div>
            @endunless
            <div class="ui-field"><label for="credit_name">Nama kreator / kanal</label><input id="credit_name" name="credit_name" class="ui-input" value="{{ old('credit_name', $lesson->credit_name) }}" placeholder="mis. nama kanal"></div>
            <div class="ui-field"><label for="license">Lisensi</label><input id="license" name="license" class="ui-input" value="{{ old('license', $lesson->license) }}" placeholder="kosong = pemutar resmi YouTube"></div>
            <div class="ui-field is-wide"><label for="goal">Tujuan belajar (opsional)</label><input id="goal" name="goal" class="ui-input" value="{{ old('goal', $lesson->body) }}"></div>
        </div>
        <p class="ad-hint">Video diputar lewat pemutar resmi YouTube. Pastikan videonya mengizinkan penyematan.</p>
        <div class="ad-form-actions">
            <button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah video' }}</button>
            <a href="{{ route('admin.learning.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>

    <aside class="pn-card ad-ov ad-pv" aria-label="Pratinjau video">
        <p class="ad-ov-k">Pratinjau video</p>
        <div class="ad-pv-video" data-yt="url" data-yt-default="{{ $lesson->video_id }}">
            <iframe title="Pratinjau video" loading="lazy" allowfullscreen hidden></iframe>
            <p data-yt-empty>Tempel tautan YouTube untuk melihat videonya di sini.</p>
        </div>
        <div class="ad-pv-meta">
            <b data-bind="title" data-empty="Judul video"></b>
            <small><span data-bind="credit_name" data-empty="Nama kreator"></span> &middot; <span data-bind="license" data-empty="Lisensi"></span></small>
            @unless ($editing)<small data-bind-dd="course_id" data-empty="Jalur belajar belum dipilih"></small>@endunless
            <p data-bind="goal" data-show="goal"></p>
        </div>
    </aside>
    </div>
@include('admin.partials.live-preview')
@endsection
