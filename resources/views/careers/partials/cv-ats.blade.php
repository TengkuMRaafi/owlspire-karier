{{--
    Hasil mode ATS di Analisis CV (ringkas): skor, kata kunci (ada / belum ada) dalam satu kotak, dan hanya pemeriksaan format yang gagal.
    Variabel: $ats (hasil CvAtsKeywords::analyze).
--}}
@php
    $score = (int) $ats['score'];
    $tone = $score >= 70 ? '' : ($score >= 45 ? 'is-mid' : 'is-low');
    $found = collect($ats['found']);
    $hardMiss = collect($ats['missing'])->where('group', 'hard')->values();
    $softMiss = collect($ats['missing'])->where('group', 'soft')->values();
    $shown = $hardMiss->take(8);
    $rest = $hardMiss->slice(8)->concat($softMiss);
    $passed = collect($ats['checks'])->where('ok', true);
    $failed = collect($ats['checks'])->where('ok', false)->values();
    // Tiap pemeriksaan menunjuk ke bagian pratinjau CV yang terkait (dicoba berurutan). Klik: pratinjau zoom ke bagian itu.
    $goto = ['heading' => 'isi,kontak', 'contact' => 'kontak,isi', 'plain' => 'kontak,isi', 'role' => 'ringkasan,kontak,isi', 'dates' => 'pengalaman,proyek', 'bullets' => 'pengalaman,proyek', 'length' => 'isi,kontak', 'skills' => 'skill'];
@endphp
<div class="cva">
    <div class="cva-top">
        <div class="cva-ring {{ $tone }}" style="--p: {{ $score }}%" role="img" aria-label="Kesiapan ATS {{ $score }} dari 100"><b>{{ $score }}</b></div>
        <div>
            <h2>Kesiapan ATS: {{ $ats['role'] }}</h2>
            <p>{{ $ats['coverage'] }}% kata kunci tampil &middot; rumpun {{ $ats['family_label'] }}</p>
            <span class="cva-chip">{{ $ats['grade'] }}</span>
        </div>
    </div>

    <div class="cva-box">
        <h3>Kata kunci <button type="button" class="cva-see" data-ats-go="skill,isi">{{ $found->count() }}/{{ $ats['keyword_total'] }} tampil &middot; lihat di CV <x-icon name="arrow-right" /></button></h3>
        <ul class="cva-tags">
            @foreach ($found as $k)<li>{{ $k['label'] }}</li>@endforeach
        </ul>
        @if ($ats['missing'])
            <p class="cva-sub">Belum tampil, tambahkan yang kamu kuasai:</p>
            <ul class="cva-tags is-miss">
                @foreach ($shown as $k)<li>{{ $k['label'] }}</li>@endforeach
            </ul>
            @if ($rest->isNotEmpty())
                <details class="cva-more">
                    <summary>+{{ $rest->count() }} lainnya</summary>
                    <ul class="cva-tags is-miss">
                        @foreach ($rest as $k)<li class="{{ $k['group'] === 'soft' ? 'is-soft' : '' }}">{{ $k['label'] }}</li>@endforeach
                    </ul>
                </details>
            @endif
        @endif
    </div>

    @if ($failed->isNotEmpty())
        <ul class="cva-checks" aria-label="Format yang perlu diperbaiki">
            @foreach ($failed->take(3) as $c)
                <li><button type="button" class="cva-go" data-ats-go="{{ $goto[$c['key']] ?? 'isi,kontak' }}"><x-icon name="x-circle" /><div><b>{{ $c['label'] }}</b><span>{{ $c['tip'] }}</span></div><x-icon name="arrow-right" class="cva-arrow" /></button></li>
            @endforeach
        </ul>
        @if ($failed->count() > 3)
            <details class="cva-more">
                <summary>+{{ $failed->count() - 3 }} perbaikan lain</summary>
                <ul class="cva-checks">
                    @foreach ($failed->slice(3) as $c)
                        <li><button type="button" class="cva-go" data-ats-go="{{ $goto[$c['key']] ?? 'isi,kontak' }}"><x-icon name="x-circle" /><div><b>{{ $c['label'] }}</b><span>{{ $c['tip'] }}</span></div><x-icon name="arrow-right" class="cva-arrow" /></button></li>
                    @endforeach
                </ul>
            </details>
        @endif
    @endif

    @if ($passed->isNotEmpty())
        <p class="cva-ok"><x-icon name="check-circle" /> Lolos: {{ $passed->pluck('label')->implode(', ') }}</p>
    @endif

    <p class="cva-note">Klik satu poin untuk memperbesar bagian CV yang dimaksud di pratinjau. Panduan dari pola umum lowongan, bukan jaminan lolos. Cocokkan lagi dengan lowongan yang dituju.</p>
</div>
