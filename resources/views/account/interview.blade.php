@extends('layouts.dashboard')

@section('title', 'Wawancara AI')
@section('heading', 'Wawancara AI')
@section('subheading', 'Latihan wawancara dengan pewawancara virtual yang menanggapi jawabanmu langsung.')

@section('content')
    <style>
        .ivs { max-width: 48rem; margin-inline: auto; }
        .ivs-pick { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; margin-top: .6rem; }
        .ivs-card { position: relative; display: flex; align-items: center; gap: .8rem; padding: .8rem .95rem; border: 1.5px solid var(--color-graphite-200, #e2e6ec); border-radius: 1rem; cursor: pointer; transition: border-color .15s, background .15s; }
        .ivs-card:hover { border-color: var(--color-accent-300, #a9c4f2); }
        .ivs-card input { position: absolute; opacity: 0; pointer-events: none; }
        .ivs-card:has(input:checked) { border-color: var(--color-accent-600, #3b6cd4); background: var(--color-accent-50, #eef4fd); }
        .ivs-card:has(input:focus-visible) { outline: 3px solid var(--color-accent-300, #a9c4f2); outline-offset: 2px; }
        html[data-theme='dark'] .ivs-card:has(input:checked) { background: rgb(79 130 230 / .16); }
        .ivs-ph { flex: none; display: grid; place-items: center; width: 3.6rem; height: 3.6rem; border-radius: 50%; background: #2c4a82; overflow: hidden; }
        .ivs-ph svg { width: 100%; height: 100%; display: block; }
        .ivs-card b { display: block; font-size: .9rem; color: var(--color-graphite-800, #1f2a3d); } .ivs-card small { font-size: .78rem; color: var(--color-graphite-500, #667794); line-height: 1.35; }
        .ivs-row { display: grid; gap: 1rem; }
        .ivs-pv { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: 1rem; height: auto; min-height: 0; padding: .9rem 1.05rem; border: 1.5px solid var(--color-accent-300, #a9c4f2); border-radius: 1rem; background: var(--color-accent-50, #eef4fd); animation: ivs-pv-in .25s ease both; }
        .ivs-pv[hidden] { display: none; }
        html[data-theme='dark'] .ivs-pv { background: rgb(79 130 230 / .16); }
        .ivs-ph.is-big { width: 5.2rem; height: 5.2rem; }
        .ivs-pv-t { min-width: 0; display: block; height: auto; text-align: left; hyphens: manual; word-spacing: normal; letter-spacing: normal; }
        .ivs-pv-t b { display: block; font-size: 1rem; color: var(--color-graphite-800, #1f2a3d); }
        .ivs-pv-t em { display: block; margin-top: .1rem; font-style: normal; font-size: .8rem; font-weight: 700; color: var(--color-accent-700, #2f58b3); }
        .ivs-pv-t small { display: block; margin-top: .35rem; font-size: .82rem; line-height: 1.5; color: var(--color-graphite-500, #667794); }
        @keyframes ivs-pv-in { from { opacity: 0; transform: translateY(4px); } }
        @media (prefers-reduced-motion: reduce) { .ivs-pv { animation: none; } }
        @media (max-width: 639px) {
            body .ivs .ivs-pv { gap: .75rem; padding: .7rem .8rem; align-items: start; }
            body .ivs .ivs-ph.is-big { width: 3.6rem; height: 3.6rem; }
            body .ivs .ivs-pv-t b { font-size: .92rem; line-height: 1.3; }
            body .ivs .ivs-pv-t em { font-size: .74rem; line-height: 1.35; }
            body .ivs .ivs-pv-t small { margin-top: .3rem; font-size: .76rem; line-height: 1.5; }
        }
        .ivs-note { margin-top: .8rem; font-size: .8rem; line-height: 1.55; color: var(--color-graphite-500, #667794); }
        .ivs-list { display: grid; gap: .5rem; margin-top: .8rem; }
        .ivs-item { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem .95rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: .9rem; }
        .ivs-item b { display: block; font-size: .92rem; color: var(--color-graphite-800, #1f2a3d); } .ivs-item small { font-size: .8rem; color: var(--color-graphite-500, #667794); }
        .ivs-score { font-size: 1.2rem; font-weight: 800; color: var(--color-accent-700, #2f58b3); font-variant-numeric: tabular-nums; }
        @media (max-width: 560px) { .ivs-pick { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 639px) {
            /* Mobile: pewawancara disusun satu kartu per baris (foto di kiri, teks di kanan) supaya teks tidak berdesakan */
            body .ivs .ivs-pick { grid-template-columns: minmax(0, 1fr); gap: .5rem; }
            body .ivs .ivs-card { flex-direction: row; align-items: center; gap: .75rem; padding: .6rem .85rem; border-radius: .95rem; text-align: left; }
            body .ivs .ivs-ph { width: 2.9rem; height: 2.9rem; }
            body .ivs .ivs-card > span:last-child { display: block; flex: 1 1 auto; min-width: 0; }
            body .ivs .ivs-card b { font-size: .9rem; line-height: 1.25; }
            body .ivs .ivs-card small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.35; }
        }
    </style>
    <div class="pn-stack ivs">
        @if ($running)
            <x-alert type="warning" title="Ada wawancara yang belum selesai">
                <p>{{ $running->role }} &middot; {{ $running->answerCount() }} jawaban terkirim. <a href="{{ route('account.interview.show', $running) }}">Lanjutkan</a></p>
            </x-alert>
        @endif

        <x-quota-lock :locked="$aiOn && $remaining <= 0" :used="$limit - $remaining" :limit="$limit" :premium="$premium"
            title="Jatah wawancara hari ini habis"
            :lead="$premium ? 'Kamu sudah memakai '.$limit.' sesi wawancara paket Premium hari ini. Jatah terisi lagi otomatis saat hari berganti.' : 'Jatah wawancara gratis hari ini sudah terpakai. Jatah terisi lagi otomatis, atau latihan lebih sering tiap hari dengan Premium.'"
            keep="Riwayat dan hasil wawancara sebelumnya tetap bisa dibuka.">
        <section class="pn-card" aria-label="Mulai wawancara">
            <div class="pn-card-h"><h2>Mulai wawancara baru</h2><span class="text-sm text-graphite-500">Sisa jatah hari ini: {{ $remaining }}/{{ $limit }}</span></div>
            @if (! $aiOn)
                <p class="text-sm text-graphite-500">Pewawancara AI belum diaktifkan di server ini (butuh minimal satu kunci LLM di .env).</p>
            @else
                <form method="POST" action="{{ route('account.interview.start') }}" class="ivs-row">
                    @csrf
                    <div class="ui-field"><label for="iv-role">Posisi yang dilamar</label><input id="iv-role" name="role" class="ui-input" maxlength="120" placeholder="mis. Staf IT PLN, Analis Data" value="{{ old('role') }}" required>@error('role')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    <div class="ui-field"><label for="iv-context">Lowongan atau catatan (opsional)</label><textarea id="iv-context" name="context" class="ui-input" rows="3" maxlength="1500" placeholder="Tempel deskripsi lowongan atau tulis nama perusahaan, supaya pertanyaannya lebih sesuai.">{{ old('context') }}</textarea>@error('context')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    @php
                        $pickOpts = [];
                        foreach ($kinds as $code => $label) { $pickOpts[$code] = $personas[$code]['name']; }
                        $pickNow = array_key_exists(old('kind', 'hr'), $pickOpts) ? old('kind', 'hr') : array_key_first($pickOpts);
                    @endphp
                    <div class="ui-field">
                        <label for="iv-kind-btn">Pilih pewawancara</label>
                        <x-dropdown name="kind" id="iv-kind-btn" block aria="Pilih pewawancara" :options="$pickOpts" :value="$pickNow" />
                    </div>
                    <div class="ivs-pv-wrap no-rapi" id="ivs-preview" aria-live="polite">
                        @foreach ($kinds as $code => $label)
                            @php $p = $personas[$code]; @endphp
                            <div class="ivs-pv" data-kind="{{ $code }}" @if ($code !== $pickNow) hidden @endif>
                                <span class="ivs-ph is-big" aria-hidden="true">@include('account.partials.interviewer', ['gender' => $p['gender'], 'mood' => 'listening', 'emo' => 'neutral', 'crop' => true, 'look' => $p['look'] ?? [], 'label' => $p['name']])</span>
                                <span class="ivs-pv-t"><b>{{ $p['name'] }}</b><em>{{ $p['title'] }} &middot; {{ $label }}</em><small>{{ $p['blurb'] ?? '' }}</small></span>
                            </div>
                        @endforeach
                    </div>
                    <div><button class="btn-primary" type="submit" @disabled($remaining <= 0)>Mulai wawancara</button></div>
                </form>
                <p class="ivs-note">@if ($hasCv)CV yang kamu simpan dipakai agar pertanyaannya sesuai pengalamanmu. @else Simpan <span style="white-space:nowrap">CV-mu</span> di menu "Analisis CV" agar pertanyaannya sesuai pengalamanmu. @endif Jawabanmu dikirim ke penyedia AI, jadi jangan tulis data sensitif. Nilai hanya masukan latihan, bukan prediksi hasil asli.</p>
            @endif
        </section>
        </x-quota-lock>

        @if ($history->isNotEmpty())
            <section class="pn-card" aria-label="Riwayat wawancara">
                <div class="pn-card-h"><h2>Riwayat</h2></div>
                <div class="ivs-list">
                    @foreach ($history as $h)
                        <a href="{{ route('account.interview.show', $h) }}" class="ivs-item">
                            <span><b>{{ $h->role }}</b><small>{{ $personas[$h->kind]['name'] ?? '' }} &middot; {{ $h->created_at->translatedFormat('d M Y, H:i') }}</small></span>
                            @if ($h->status === 'done' && $h->total_score !== null)<span class="ivs-score">{{ $h->total_score }}</span>@elseif ($h->status === 'done')<span class="ui-badge">Belum dinilai</span>@else<span class="ui-badge is-warning">Berjalan</span>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
    <script>
        (function () {
            var box = document.getElementById('ivs-preview'), inp = document.querySelector('[data-dd] input[name="kind"]');
            if (!box || !inp) return;
            function show() { box.querySelectorAll('.ivs-pv').forEach(function (el) { el.hidden = el.dataset.kind !== inp.value; }); }
            inp.addEventListener('change', show); show();
        })();
    </script>
@endsection
