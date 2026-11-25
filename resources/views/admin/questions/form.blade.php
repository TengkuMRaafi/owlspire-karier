@extends('layouts.admin')

@php $editing = $question->exists; @endphp
@section('title', $editing ? 'Ubah soal' : 'Tambah soal')
@section('heading', $editing ? 'Ubah soal' : 'Tambah soal')
@section('actions')
    <a href="{{ route('admin.questions.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    @php
        $segments = [];
        foreach ($types as $t) { foreach ($t->segments as $s) { $segments[$s->id] = $t->name.' - '.$s->name; } }
    @endphp
    <div class="ad-uf">
    <form method="POST" action="{{ $editing ? route('admin.questions.update', $question) : route('admin.questions.store') }}" class="pn-card ad-block ad-form" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="ui-field">
            <label for="segment_id-btn">Segmen</label>
            <x-dropdown name="segment_id" id="segment_id-btn" block aria="Segmen" all="Pilih segmen" :options="$segments" :value="old('segment_id', $question->segment_id)" />
        </div>
        <div class="ui-field">
            <label for="body">Teks soal</label>
            <textarea id="body" name="body" rows="4" class="ui-input" required>{{ old('body', $question->body) }}</textarea>
        </div>
        <div class="ui-field">
            <label for="image">Gambar soal <span class="ui-hint">(opsional)</span></label>
            <div class="ad-img" data-img data-current="{{ $question->image_url }}">
                <input type="hidden" name="remove_image" value="0" data-img-remove>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-img-file>
                <label for="image" class="ad-img-drop" data-img-drop>
                    <span class="ad-img-ic"><x-icon name="upload" class="size-5" /></span>
                    <b>Pilih gambar</b>
                    <small>JPG, PNG, WEBP, atau GIF &middot; maks. 2 MB</small>
                </label>
                <figure class="ad-img-prev" data-img-prev hidden>
                    <img alt="Pratinjau gambar soal" data-img-el>
                    <figcaption>
                        <span class="ad-img-name" data-img-name>Gambar saat ini</span>
                        <span class="ad-img-acts">
                            <label for="image" class="ad-img-btn">Ganti</label>
                            <button type="button" class="ad-img-btn is-danger" data-img-clear>Hapus</button>
                        </span>
                    </figcaption>
                </figure>
                <p class="ad-img-err" data-img-err hidden></p>
            </div>
            @error('image')<small class="text-red-700">{{ $message }}</small>@enderror
        </div>
        <fieldset class="ui-field ad-oc-set">
            <legend class="ui-label">Pilihan jawaban <span class="ui-hint">(minimal 2, kosongkan yang tidak dipakai)</span></legend>
            <p class="ad-oc-help">Ketuk huruf di kiri untuk menandai <b>kunci jawaban</b>.</p>
            @php $keyNow = old('answer', $question->answer ?: 'A'); @endphp
            <div class="ad-oc">
                @foreach (['A', 'B', 'C', 'D', 'E'] as $k)
                    <div class="ad-oc-row">
                        <label class="ad-oc-key" title="Jadikan pilihan {{ $k }} sebagai kunci jawaban">
                            <input type="radio" name="answer" value="{{ $k }}" @checked($keyNow === $k) aria-label="Kunci jawaban: pilihan {{ $k }}">
                            <span><b>{{ $k }}</b><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
                        </label>
                        <input id="opt-{{ $k }}" name="options[{{ $k }}]" value="{{ old('options.'.$k, $question->options[$k] ?? '') }}" class="ad-oc-in" placeholder="Pilihan {{ $k }}" aria-label="Teks pilihan {{ $k }}" autocomplete="off">
                        <em class="ad-oc-tag">Kunci</em>
                    </div>
                @endforeach
            </div>
            @error('options')<small class="text-red-700">{{ $message }}</small>@enderror
            @error('answer')<small class="text-red-700">{{ $message }}</small>@enderror
        </fieldset>
        <div class="ui-field">
            <label class="ui-check"><input type="checkbox" name="is_trial" value="1" @checked(old('is_trial', $question->is_trial))><i></i> Tampilkan di mode coba gratis</label>
        </div>
        <div class="ui-field">
            <label for="explanation">Pembahasan</label>
            <textarea id="explanation" name="explanation" rows="3" class="ui-input">{{ old('explanation', $question->explanation) }}</textarea>
        </div>
        <div class="ad-form-actions">
            <button type="submit" class="btn-primary">{{ $editing ? 'Simpan perubahan' : 'Tambah soal' }}</button>
            <a href="{{ route('admin.questions.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>

    <aside class="pn-card ad-ov ad-pv" aria-label="Pratinjau soal">
        <p class="ad-ov-k">Pratinjau soal</p>
        <p class="ad-pv-seg"><x-icon name="layers" class="size-4" /> <span data-bind-dd="segment_id" data-empty="Segmen belum dipilih"></span> <span class="ui-badge is-accent" data-check="is_trial">Mode coba</span></p>
        <p class="ad-pv-body" data-bind="body" data-empty="Teks soal akan tampil di sini."></p>
        <figure class="ad-pv-img" data-pv-img hidden><img alt="Gambar soal"></figure>
        <ul class="ad-opts">
            @foreach (['A', 'B', 'C', 'D', 'E'] as $k)
                <li data-show="options[{{ $k }}]" data-key-of="answer" data-key-val="{{ $k }}"><b>{{ $k }}</b><span data-bind="options[{{ $k }}]"></span><em>Kunci</em></li>
            @endforeach
        </ul>
        <div class="ad-pv-ex" data-show="explanation"><small>Pembahasan</small><p data-bind="explanation"></p></div>
        <p class="ad-hint">Begini tampilan soal bagi peserta. Pilihan yang dikosongkan tidak ikut tampil.</p>
    </aside>
    </div>
<script>
    (function () {
        var box = document.querySelector('[data-img]'); if (!box) return;
        var file = box.querySelector('[data-img-file]'), rm = box.querySelector('[data-img-remove]'),
            drop = box.querySelector('[data-img-drop]'), prev = box.querySelector('[data-img-prev]'),
            img = box.querySelector('[data-img-el]'), nameEl = box.querySelector('[data-img-name]'),
            err = box.querySelector('[data-img-err]'), clear = box.querySelector('[data-img-clear]'),
            pv = document.querySelector('.ad-pv [data-pv-img]'), pvImg = pv ? pv.querySelector('img') : null,
            cur = box.dataset.current || '', objUrl = null;
        function show(src, name) {
            if (src) {
                img.src = src; nameEl.textContent = name; prev.hidden = false; drop.hidden = true;
                if (pv) { pvImg.src = src; pv.hidden = false; }
            } else {
                img.removeAttribute('src'); prev.hidden = true; drop.hidden = false;
                if (pv) { pvImg.removeAttribute('src'); pv.hidden = true; }
            }
        }
        function fallback() { show(rm.value === '1' ? '' : cur, 'Gambar saat ini'); }
        file.addEventListener('change', function () {
            err.hidden = true;
            var f = file.files && file.files[0];
            if (!f) { fallback(); return; }
            if (f.size > 2 * 1024 * 1024) { file.value = ''; err.textContent = 'Ukuran gambar melebihi 2 MB. Pilih gambar yang lebih kecil.'; err.hidden = false; fallback(); return; }
            if (objUrl) URL.revokeObjectURL(objUrl);
            objUrl = URL.createObjectURL(f); rm.value = '0'; show(objUrl, f.name);
        });
        clear.addEventListener('click', function () { file.value = ''; err.hidden = true; rm.value = cur ? '1' : '0'; show('', ''); });
        show(cur, 'Gambar saat ini');
    })();
</script>
@include('admin.partials.live-preview')
@endsection
