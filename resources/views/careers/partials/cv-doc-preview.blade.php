{{--
    Pratinjau CV asli: gambar halaman PDF yang diunggah, dengan titik bernomor di bagian yang dinilai.
    Klik titik (atau barisnya di daftar) -> gambar zoom ke bagian itu dan muncul popup catatan singkat. Tinggi tetap muat satu layar.
    Tanpa JS: gambar tampil biasa dan catatan terbuka di daftar. Logika ada di public/js/cv-doc.js.
    Variabel: $blocks (blok dengan 'rects'), $counts (good/weak/bad), $pages (url, w, h tiap halaman).
--}}
@php
    $icons = ['good' => 'check-circle', 'weak' => 'alert', 'bad' => 'x-circle'];
    $rank = ['good' => 0, 'weak' => 1, 'bad' => 2];
    $toneOf = ['good', 'weak', 'bad'];

    $num = [];
    $tone = [];
    $worst = [];
    $n = 0;
    foreach ($blocks as $i => $b) {
        if (! $b['notes']) {
            continue;
        }
        $ranks = collect($b['notes'])->map(fn ($x) => $rank[$x['t']]);
        $tone[$i] = $toneOf[$ranks->max()];
        $worst[$i] = $b['notes'][$ranks->search($ranks->max())];
        if ($b['rects']) {
            $num[$i] = ++$n;
        }
    }
    $listed = collect($blocks)->filter(fn ($b, $i) => isset($tone[$i]));
    $first = $pages[0];
@endphp
<div class="cvp-wrap">
<div class="cvp-legend" role="list" aria-label="Arti warna">
    <span class="is-good" role="listitem"><i></i><b>{{ $counts['good'] }}</b> Bagus</span>
    <span class="is-weak" role="listitem"><i></i><b>{{ $counts['weak'] }}</b> Kurang menonjol</span>
    <span class="is-bad" role="listitem"><i></i><b>{{ $counts['bad'] }}</b> Perlu diperbaiki</span>
</div>

<div class="cvd" data-cvd>
    <div class="cvd-view">
        @if (count($pages) > 1)
            <div class="cvd-tabs" role="tablist" aria-label="Halaman CV">
                @foreach ($pages as $pi => $pg)
                    <button type="button" role="tab" class="cvd-tab" data-cvd-tab="{{ $pi }}" aria-selected="{{ $pi === 0 ? 'true' : 'false' }}">Halaman {{ $pi + 1 }}</button>
                @endforeach
            </div>
        @endif

        <div class="cvd-stage" data-cvd-stage style="--ar: {{ (int) $first['w'] }} / {{ (int) $first['h'] }}; --arn: {{ round($first['w'] / max(1, $first['h']), 4) }}">
            <div class="cvd-sheet" data-cvd-sheet>
                @foreach ($pages as $pi => $pg)
                    <div class="cvd-page" data-cvd-page data-p="{{ $pi }}" data-ar="{{ (int) $pg['w'] }} / {{ (int) $pg['h'] }}" data-arn="{{ round($pg['w'] / max(1, $pg['h']), 4) }}" @if ($pi > 0) hidden @endif>
                        <img src="{{ $pg['url'] }}" alt="Halaman {{ $pi + 1 }} dari CV-mu" width="{{ (int) $pg['w'] }}" height="{{ (int) $pg['h'] }}" decoding="async" draggable="false">
                        @foreach ($blocks as $i => $b)
                            @if (isset($num[$i]))
                                @foreach ($b['rects'] as $ri => $r)
                                    @if ($r['p'] === $pi)
                                        <button type="button" class="cvd-box is-{{ $tone[$i] }}" style="left: {{ $r['x'] * 100 }}%; top: {{ $r['y'] * 100 }}%; width: {{ $r['w'] * 100 }}%; height: {{ $r['h'] * 100 }}%" data-b="{{ $i }}" @if ($ri === 0) data-primary @endif aria-label="{{ $b['label'] }}: lihat catatan"><b>{{ $num[$i] }}</b></button>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
            <button type="button" class="cvd-reset" data-cvd-reset hidden>Lihat seluruh CV</button>
            <div class="cvd-pop" data-cvd-pop hidden role="dialog" aria-live="polite"></div>
        </div>
        <p class="cvd-hint">Klik bagian yang bertanda untuk memperbesar dan membaca catatannya.</p>
    </div>

    <div class="cvd-list" data-cvd-list>
        @foreach ($listed as $i => $b)
            @php($lead = $worst[$i])
            <section class="cvd-grp is-{{ $tone[$i] }}" data-b="{{ $i }}" data-key="{{ $b['key'] }}" aria-label="{{ $b['label'] }}">
                <button type="button" class="cvd-row" data-cvd-open="{{ $i }}">
                    @if (isset($num[$i]))
                        <span class="cvd-no">{{ $num[$i] }}</span>
                    @else
                        <span class="cvd-no is-plain"><x-icon :name="$icons[$lead['t']]" /></span>
                    @endif
                    <span class="cvd-rl">
                        <small>{{ $b['label'] }}@if ($b['ghost']) &middot; belum ada di CV @endif</small>
                        <b>{{ $lead['h'] }}</b>
                    </span>
                    @if (count($b['notes']) > 1)
                        <span class="cvd-more">+{{ count($b['notes']) - 1 }}</span>
                    @endif
                </button>
                <div class="cvd-detail" data-cvd-detail>
                    @include('careers.partials.cv-doc-notes', ['notes' => $b['notes'], 'icons' => $icons])
                </div>
            </section>
        @endforeach
    </div>
</div>
</div>
