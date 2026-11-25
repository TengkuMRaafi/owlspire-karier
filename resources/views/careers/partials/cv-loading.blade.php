{{--
    Panel "sedang diproses" yang menggantikan formulir selama CV dianalisis atau disarankan AI.
    Variabel: $title, $note, $steps (daftar kalimat); opsional $kind (improve/letter) dan $titleTpl (judul dengan {role}). Disembunyikan sampai JS (cv.js) menampilkannya saat formulir dikirim.
--}}
<section class="cvl" data-cv-loading @isset($kind) data-kind="{{ $kind }}" @endisset hidden role="status" aria-live="polite">
    <div class="cvl-top">
        <span class="cvl-spin" aria-hidden="true"></span>
        <div>
            <h2 data-cvl-title @isset($titleTpl) data-tpl="{{ $titleTpl }}" @endisset>{{ $title }}</h2>
            <p>{{ $note }}</p>
        </div>
    </div>
    <ol class="cvl-steps">
        @foreach ($steps as $step)
            <li data-cvl-step><span class="cvl-dot" aria-hidden="true"></span><span>{{ $step }}</span></li>
        @endforeach
    </ol>
    <div class="cvl-paper" aria-hidden="true">
        <i style="--w: 46%"></i><i style="--w: 72%"></i><i style="--w: 58%"></i>
        <i class="gap" style="--w: 30%"></i>
        <i style="--w: 88%"></i><i style="--w: 80%"></i><i style="--w: 64%"></i>
    </div>
</section>
