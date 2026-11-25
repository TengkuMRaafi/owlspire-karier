{{--
    Pratinjau CV bertanda panah. Dipakai Analisis CV.
    Laptop: kertas CV di kiri, catatan di kanan, tiap catatan punya panah ke bagian CV yang dimaksud.
    HP: tiap bagian CV diikuti catatannya tepat di bawah, dengan panah kecil mengarah ke atas.
    Variabel: $blocks (daftar blok), $counts (good/weak/bad), $legend (teks pendamping opsional).
--}}
@php
    $icons = ['good' => 'check-circle', 'weak' => 'alert', 'bad' => 'x-circle'];
    $rank = ['good' => 0, 'weak' => 1, 'bad' => 2];
    $toneOf = ['good', 'weak', 'bad'];
    $total = count($blocks);
@endphp
<div class="cvp-wrap">
<div class="cvp-legend" role="list" aria-label="Arti warna">
    <span class="is-good" role="listitem"><i></i><b>{{ $counts['good'] }}</b> Bagus</span>
    <span class="is-weak" role="listitem"><i></i><b>{{ $counts['weak'] }}</b> Kurang menonjol</span>
    <span class="is-bad" role="listitem"><i></i><b>{{ $counts['bad'] }}</b> Perlu diperbaiki</span>
</div>

<div class="cvp" style="--n: {{ max(1, $total) }}">
    <div class="cvp-sheet" aria-hidden="true"></div>
    @foreach ($blocks as $i => $b)
        @php
            $worst = collect($b['notes'])->map(fn ($x) => $rank[$x['t']])->max();
            $tone = $worst === null ? 'none' : $toneOf[$worst];
            $isHead = in_array($b['key'], ['kontak', 'isi'], true);
        @endphp
        <section data-key="{{ $b['key'] }}" class="cvp-cell is-{{ $tone }} {{ $b['ghost'] ? 'is-ghost' : '' }} {{ $b['notes'] ? '' : 'no-notes' }}" style="--r: {{ $i + 1 }}" aria-label="{{ $b['label'] }}">
            @if ($b['key'] !== 'kontak' && $b['key'] !== 'isi')
                <h4 class="cvp-h">{{ $b['label'] }}</h4>
            @elseif ($b['key'] === 'kontak')
                <h4 class="cvp-h cvp-h-quiet">{{ $b['label'] }}</h4>
            @endif

            @if ($b['ghost'])
                <p class="cvp-empty">Bagian ini belum ada di CV-mu</p>
            @else
                <div class="cvp-body">
                    @foreach ($b['lines'] as $j => $ln)
                        <p class="cvp-l {{ $ln['b'] ? 'is-b' : '' }} {{ $ln['m'] ? 'is-'.$ln['m'] : '' }} {{ $b['key'] === 'kontak' && $j === 0 ? 'is-name' : '' }}">{{ $ln['t'] }}</p>
                    @endforeach
                    @if ($b['more'] > 0)
                        <p class="cvp-more">+ {{ $b['more'] }} baris lainnya</p>
                    @endif
                </div>
            @endif
        </section>

        @if ($b['notes'])
            <div class="cvp-notes" style="--r: {{ $i + 1 }}">
                @foreach ($b['notes'] as $note)
                    <aside class="cvp-note is-{{ $note['t'] }}">
                        <span class="cvp-ic"><x-icon :name="$icons[$note['t']]" /></span>
                        <div class="cvp-nb">
                            <b>{{ $note['h'] }}</b>
                            <p>{{ $note['p'] }}</p>
                            @if (! empty($note['r']))
                                <div class="cvp-rw">
                                    <div class="cvp-rw-h"><small>Contoh tulisan ulang</small><button type="button" class="cvp-copy" data-cv-copy>Salin</button></div>
                                    <pre class="cvp-rw-t">{{ $note['r'] }}</pre>
                                </div>
                            @endif
                        </div>
                    </aside>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
</div>
