@extends('layouts.dashboard')

@section('title', 'CV & surat lamaran')
@section('heading', 'CV & surat lamaran')
@section('subheading', 'Saran perbaikan CV per bagian dan draf surat lamaran, sesuai posisi yang ingin kamu lamar.')

@section('content')
    @include('careers.partials.tabs')
    <style>
        .ct-grid { display: grid; gap: .9rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .ct-acts { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
        .ct-note { margin-top: .8rem; font-size: .82rem; line-height: 1.55; color: var(--color-graphite-500, #667794); }
        .ct-out { margin-top: .8rem; padding: 1rem 1.1rem; border-radius: 1rem; background: var(--color-graphite-100, #edf0f5); font-family: inherit; font-size: .92rem; line-height: 1.65; white-space: pre-wrap; word-break: break-word; }
        .ct-left { margin-top: .85rem; font-size: .8rem; line-height: 1.5; color: var(--color-graphite-500, #667794); }
        @media (max-width: 639px) {
            /* Mobile: dua tombol selebar penuh dan sama besar, teks di tengah, lalu info jatah dengan jarak yang lega */
            body .ct-acts { flex-direction: column; flex-wrap: nowrap; gap: .5rem; margin-top: 1rem; }
            body .ct-acts > button { width: 100%; min-height: 2.9rem; justify-content: center; text-align: center; }
            body .ct-left { display: block; }
            body .ct-note { margin-top: .45rem; }
        }
    </style>
    <div class="cv ct" style="max-width:56rem">
        @unless ($hasCv)
            <x-alert type="warning" title="CV belum ada">
                <p>Alat ini membaca CV yang sudah kamu unggah. <a href="{{ route('cv.index') }}">Unggah CV di Analisis CV</a> dulu.</p>
            </x-alert>
        @endunless

        @include('careers.partials.cv-loading', [
            'kind' => 'improve',
            'title' => 'Sedang menyusun saran untuk CV-mu',
            'titleTpl' => 'Menyusun saran CV untuk posisi {role}',
            'note' => 'AI membaca CV-mu bagian demi bagian. Biasanya 10 sampai 20 detik, jangan tutup halaman ini.',
            'steps' => ['Membaca CV-mu per bagian', 'Menyesuaikan dengan posisi dan lowongan', 'Menulis saran dan contoh perbaikan', 'Menyusun pratinjau bertanda panah'],
        ])
        @include('careers.partials.cv-loading', [
            'kind' => 'letter',
            'title' => 'Sedang menulis surat lamaranmu',
            'titleTpl' => 'Menulis surat lamaran untuk posisi {role}',
            'note' => 'Biasanya 10 sampai 20 detik, jangan tutup halaman ini.',
            'steps' => ['Membaca CV-mu', 'Mencocokkan dengan posisi dan lowongan', 'Menulis empat paragraf surat'],
        ])

        <div class="cv-wrap" data-cv-wrap>
            @if ($result)
                <div class="cv-result" data-cv-result>
                    @if ($advice)
                        @php $d = $result['data']; @endphp
                        <section class="cv-card ct-sum" aria-label="Ringkasan saran">
                            <div class="cvp-top">
                                <div>
                                    <span class="ct-eyebrow">Saran untuk posisi</span>
                                    <h2>{{ $result['role'] }}</h2>
                                    @if (! empty($d['summary']))<p>{{ $d['summary'] }}</p>@endif
                                </div>
                                <a class="btn-outline cvp-cta" href="#ct-form"><x-icon name="refresh" /> Coba posisi lain</a>
                            </div>
                            @if (! empty($d['keywords']))
                                <div class="ct-kw">
                                    <span>Kata kunci posisi yang belum ada di CV</span>
                                    <ul class="cvx-tags">@foreach ($d['keywords'] as $kw)<li>{{ $kw }}</li>@endforeach</ul>
                                    <small>Tambahkan hanya bila memang sesuai pengalamanmu.</small>
                                </div>
                            @endif
                            @if (! empty($d['remove']))
                                <div class="ct-kw">
                                    <span>Sebaiknya dihapus atau diringkas</span>
                                    <ul class="ct-rm">@foreach ($d['remove'] as $rm)<li>{{ $rm }}</li>@endforeach</ul>
                                </div>
                            @endif
                        </section>

                        <section class="cv-card cvp-card" aria-labelledby="ctp-title">
                            <div class="cvp-top">
                                <div>
                                    <h2 id="ctp-title">CV-mu dengan saran per bagian</h2>
                                    <p>Panah menunjuk bagian CV yang dimaksud. Saran dan contoh tulisan ulang disesuaikan dengan posisi <b>{{ $result['role'] }}</b>.</p>
                                </div>
                            </div>
                            @include('careers.partials.cv-preview', ['blocks' => $advice['blocks'], 'counts' => $advice['counts']])
                        </section>
                        @if ($updated && $updated['changed'])
                            <section class="pn-card" aria-labelledby="ct-new-title">
                                <div class="pn-card-h"><h2 id="ct-new-title">CV versi update</h2>
                                    <div style="display:flex;gap:.5rem">
                                        <button type="button" class="btn-outline btn-sm" data-copy-target="ct-cv-new">Salin</button>
                                        <button type="button" class="btn-outline btn-sm" data-ct-download="ct-cv-new">Unduh .txt</button>
                                    </div>
                                </div>
                                <p class="ct-note" style="margin-top:0">Bagian yang diubah: <b>{{ implode(', ', $updated['changed']) }}</b>. Bagian lain, termasuk kontak, sama persis dengan CV aslimu.@if ($updated['placeholders']) Ada <b>{{ $updated['placeholders'] }} isian [kurung siku]</b> yang harus kamu isi atau hapus sendiri.@endif</p>
                                <pre id="ct-cv-new" class="ct-out">{{ $updated['text'] }}</pre>
                            </section>
                        @endif
                        <p class="ct-note">Ini saran dari AI. Periksa semua isinya, pastikan hanya memuat hal yang benar tentang dirimu, dan isi bagian dalam [kurung siku] sebelum dipakai.</p>
                    @else
                        <section class="pn-card" aria-label="Hasil">
                            <div class="pn-card-h"><h2>{{ $result['type'] === 'cover_letter' ? 'Draf surat lamaran' : 'Saran perbaikan CV' }} &middot; {{ $result['role'] }}</h2>
                                <button type="button" class="btn-outline btn-sm" data-copy-target="ct-result">Salin</button></div>
                            <pre id="ct-result" class="ct-out">{{ $result['text'] ?? ($result['data']['raw'] ?? '') }}</pre>
                            <p class="ct-note">Ini draf dari AI. Periksa semua isinya, pastikan hanya memuat hal yang benar tentang dirimu, dan isi bagian dalam [kurung siku] sebelum dipakai.</p>
                        </section>
                    @endif
                </div>
            @endif

            <details class="cv-new ct-new {{ $result ? '' : 'is-plain' }}" id="ct-form" @if (! $result || $errors->any()) open @endif>
                <summary><x-icon name="refresh" /> <span>Ubah posisi atau coba lagi</span><x-icon name="chevron-down" class="cv-new-chev" /></summary>
                <section class="pn-card" aria-label="Formulir">
                    <div class="pn-card-h"><h2>Untuk lowongan apa?</h2></div>
                    @if (! $aiOn)
                        <p class="text-sm text-graphite-500">Penulis AI belum diaktifkan di server ini (butuh minimal satu kunci LLM di .env).</p>
                    @endif
                    <form method="POST" action="{{ route('account.cvtools.improve') }}" data-ct-form>
                        @csrf
                        <div class="ct-grid">
                            <div class="ui-field"><label for="ct-role">Posisi yang ingin dilamar</label><input id="ct-role" name="role" class="ui-input" maxlength="120" value="{{ old('role', $prefill['role']) }}" placeholder="mis. Data Analyst" required>@error('role')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                            <div class="ui-field"><label for="ct-company">Perusahaan (untuk surat)</label><input id="ct-company" name="company" class="ui-input" maxlength="120" value="{{ old('company', $prefill['company']) }}"></div>
                            <div class="ui-field"><label for="dd-ct-tone">Gaya surat</label><x-dropdown name="tone" id="dd-ct-tone" aria="Gaya surat" :options="['formal' => 'Formal', 'santai' => 'Ramah']" :value="old('tone', 'formal')" block /></div>
                        </div>
                        <div class="ui-field" style="margin-top:.9rem"><label for="ct-job">Deskripsi lowongan (tempel; makin lengkap makin tepat)</label>
                            <textarea id="ct-job" name="job" rows="7" maxlength="4000" class="ui-input" placeholder="Tempel kualifikasi dan tanggung jawab dari iklan lowongan...">{{ old('job') }}</textarea></div>
                        <div class="ct-acts">
                            <button class="btn-primary" type="submit" data-ct-kind="improve" @disabled(! $hasCv || ! $aiOn)>Perbaiki CV</button>
                            <button class="btn-outline" type="submit" data-ct-kind="letter" formaction="{{ route('account.cvtools.letter') }}" @disabled(! $hasCv || ! $aiOn)>Buat surat lamaran</button>
                        </div>
                        <p class="ct-left">Sisa jatah hari ini: perbaikan CV {{ $left['cv_rewrite'] }}/{{ $limits['cv_rewrite'] }}, surat lamaran {{ $left['cover_letter'] }}/{{ $limits['cover_letter'] }}</p>
                        <p class="ct-note">Teks CV dan deskripsi lowongan dikirim ke penyedia AI untuk diproses. AI diminta tidak mengarang pengalaman, tapi tetap periksa hasilnya.</p>
                    </form>
                </section>
            </details>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-ct-download]').forEach(function (b) {
            b.addEventListener('click', function () {
                var el = document.getElementById(b.getAttribute('data-ct-download'));
                if (!el) return;
                var a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([el.textContent], { type: 'text/plain;charset=utf-8' }));
                a.download = 'cv-versi-update.txt';
                a.click();
                setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
            });
        });
        document.querySelectorAll('[data-copy-target]').forEach(function (b) {
            b.addEventListener('click', function () {
                var el = document.getElementById(b.getAttribute('data-copy-target'));
                if (!el) return;
                var done = function () { var t = b.textContent; b.textContent = 'Tersalin'; setTimeout(function () { b.textContent = t; }, 1600); };
                if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(el.textContent).then(done, function () {}); }
            });
        });
    </script>
@endsection
