@extends('layouts.dashboard')

@section('title', 'Analisis CV')
@section('heading', 'Analisis CV')
@section('subheading', 'Pratinjau CV bertanda panah, skill terbaca, dan karier yang paling cocok.')

@section('content')
    <div class="cv">
        @if (! $premium)
            <div class="ac-lock">
                <span class="ac-lock-ic"><x-icon name="lock" /></span>
                <h2>Fitur ini khusus Premium.</h2>
                <p>Unggah CV atau tempel isinya. Kami membaca skill, menunjukkan bagian CV yang bagus dan yang kurang menonjol, lalu mencocokkannya dengan karier.</p>
                <ul>
                    <li><x-icon name="check" /> Pratinjau CV dengan panah ke bagian yang bagus dan yang perlu diperbaiki</li>
                    <li><x-icon name="check" /> Skill terbaca otomatis dari CV</li>
                    <li><x-icon name="check" /> Kecocokan dengan tiap karier dalam persen</li>
                    <li><x-icon name="check" /> Celah skill dan jalur belajar untuk menutupnya</li>
                </ul>
                <a href="{{ route('account.subscription') }}" class="btn-primary px-8 py-3">Lihat paket Premium</a>
            </div>
        @else
            <link rel="stylesheet" href="{{ asset('css/cv-ats.css') }}?v={{ @filemtime(public_path('css/cv-ats.css')) }}">
            @include('careers.partials.cv-loading', [
                'title' => 'Sedang menganalisis CV-mu',
                'note' => 'Biasanya hanya beberapa detik. Jangan tutup halaman ini.',
                'steps' => ['Membaca isi CV', 'Mendeteksi skill', 'Mencocokkan dengan karier', 'Menyusun pratinjau bertanda panah'],
            ])

            <div class="cv-wrap" data-cv-wrap>
                @unless ($profile)
                    <div class="cv-intro">
                        <div class="cv-intro-t">
                            <span class="cv-ic"><x-icon name="file" /></span>
                            <div>
                                <h2>Cocokkan <span class="cv-nb">CV-mu</span> dengan karier</h2>
                                <p>Kami membaca skill, menandai bagian <span class="cv-nb">CV-mu</span> yang sudah bagus dan yang kurang menonjol, lalu mencocokkannya dengan karier.</p>
                            </div>
                        </div>
                        <ol class="cv-steps">
                            <li>Unggah atau tempel CV</li>
                            <li>Lihat pratinjau bertanda panah</li>
                            <li>Lihat karier yang cocok</li>
                        </ol>
                    </div>
                @endunless

                @if ($profile)
                    <details class="cv-new" @if ($errors->any()) open @endif>
                        <summary><x-icon name="upload" /> <span>Analisis CV baru</span><x-icon name="chevron-down" class="cv-new-chev" /></summary>
                @endif

                <x-quota-lock radius="1.4rem" :locked="($quotaLeft ?? 1) <= 0" :used="($quotaLimit ?? 0) - ($quotaLeft ?? 0)" :limit="$quotaLimit ?? 0" :premium="$premium"
                    title="Jatah analisis CV hari ini habis"
                    :lead="'Kamu sudah memakai '.($quotaLimit ?? 0).' analisis CV hari ini. Jatah terisi lagi otomatis saat hari berganti.'"
                    :keep="$profile ? 'Hasil analisis CV terakhirmu tetap bisa dilihat di bawah.' : null">
                <form method="POST" action="{{ route('cv.store') }}" enctype="multipart/form-data" class="cv-form" data-cv-form data-cv-pdfjs="{{ rtrim(parse_url(asset('vendor/pdfjs'), PHP_URL_PATH), '/') }}/">
                    @csrf
                    @php $cvType = old('cv_type', $ats ? 'ats' : 'standard'); @endphp
                    <fieldset class="cv-type">
                        <legend class="ui-label">Jenis CV</legend>
                        <label class="cv-type-opt">
                            <input type="radio" name="cv_type" value="standard" @checked($cvType !== 'ats')>
                            <span><b>CV biasa</b><small>CV bergambar atau berdesain (foto, kolom, warna). Dinilai dari isi dan tampilannya.</small></span>
                        </label>
                        <label class="cv-type-opt">
                            <input type="radio" name="cv_type" value="ats" @checked($cvType === 'ats')>
                            <span><b>CV ATS</b><small>CV teks polos yang dibaca sistem rekrutmen. Dinilai dari kata kunci jabatan dan format.</small></span>
                        </label>
                    </fieldset>

                    <div class="ui-field cv-ats-field">
                        <label for="target_role" class="ui-label">Jabatan yang dicari</label>
                        <div class="cv-role" data-cv-role data-options="{{ json_encode($roleSuggestions, JSON_UNESCAPED_UNICODE) }}">
                            <input id="target_role" name="target_role" maxlength="80" value="{{ old('target_role', $ats['role'] ?? '') }}" class="ui-input" placeholder="mis. Data Analyst, Backend Developer, Staff Akuntansi" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="cv-role-list">
                            <button type="button" class="cv-role-chev" tabindex="-1" aria-label="Tampilkan saran jabatan"><x-icon name="chevron-down" /></button>
                            <ul id="cv-role-list" class="cv-role-list" role="listbox" hidden></ul>
                        </div>
                        <p class="cv-ats-hint">Hasil disesuaikan dengan kata kunci yang umum dicari di lowongan jabatan ini. Boleh ketik jabatan lain.</p>
                        @error('target_role')<p class="ui-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="ui-field">
                        <span class="ui-label">Unggah CV</span>
                        <label class="cv-drop" data-cv-drop>
                            <input id="cv_file" type="file" name="cv_file" accept="{{ $ocr ? '.pdf,.docx,.txt,.png,.jpg,.jpeg' : '.pdf,.docx,.txt' }}" data-cv-input>
                            <span class="cv-drop-ic"><x-icon name="upload" /></span>
                            <b>Pilih berkas atau seret ke sini</b>
                            <small>PDF, DOCX, TXT{{ $ocr ? ', atau gambar/scan (PNG, JPG)' : '' }}, maksimal 4 MB</small>
                        </label>
                        <div class="cv-file" data-cv-file hidden>
                            <x-icon name="file" />
                            <span data-cv-name></span>
                            <small data-cv-size></small>
                            <button type="button" data-cv-clear aria-label="Hapus berkas"><x-icon name="x" /></button>
                        </div>
                        @error('cv_file')<p class="ui-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="cv-or">atau</div>

                    <div class="ui-field">
                        <label for="cv_text" class="ui-label">Tempel isi CV</label>
                        <textarea id="cv_text" name="cv_text" rows="6" class="ui-input" placeholder="Tempel teks CV, terutama bagian keahlian dan pengalaman" data-cv-text>{{ old('cv_text') }}</textarea>
                        @error('cv_text')<p class="ui-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="ui-field">
                        <label for="cv_label" class="ui-label">Nama versi (opsional)</label>
                        <input id="cv_label" name="label" maxlength="80" value="{{ old('label') }}" class="ui-input" placeholder="mis. CV untuk lamaran Data Analyst">
                    </div>

                    <button class="btn-primary cv-submit" data-cv-submit @disabled(($quotaLeft ?? 1) <= 0)><x-icon name="sparkle" /><span data-cv-label>Analisis CV</span></button>
                    <p class="cv-privacy">Sisa jatah analisis hari ini: {{ $quotaLeft ?? 0 }}/{{ $quotaLimit ?? 0 }}</p>
                    <p class="cv-privacy"><x-icon name="shield" /> Teks CV dan tampilan halamannya hanya disimpan di akunmu (terenkripsi) untuk analisis ini dan bisa dihapus kapan saja.</p>
                </form>
                </x-quota-lock>
                <script src="{{ asset('js/cv-role.js') }}?v={{ @filemtime(public_path('js/cv-role.js')) }}" defer></script>
                <script src="{{ asset('js/cv-pdf.js') }}?v={{ @filemtime(public_path('js/cv-pdf.js')) }}" defer></script>

                @if ($profile)
                    </details>
                @endif
            </div>

            @if ($profile)
                <div class="cv-result" data-cv-result>
                    <div class="cv-stats">
                        <div class="cv-stat"><b>{{ $skills->count() }}</b><span>Skill terbaca</span></div>
                        <div class="cv-stat"><b>{{ $top->count() }}</b><span>Karier cocok</span></div>
                        <div class="cv-stat"><b>{{ $top->isNotEmpty() ? $top->max('score') : 0 }}%</b><span>Kecocokan tertinggi</span></div>
                    </div>

                    @if ($ats)
                        <section class="cv-card" id="ats" aria-label="Analisis CV ATS">
                            @include('careers.partials.cv-ats', ['ats' => $ats])
                            <script src="{{ asset('js/cv-ats.js') }}?v={{ @filemtime(public_path('js/cv-ats.js')) }}" defer></script>
                        </section>
                    @endif

                    @if ($preview)
                        <section class="cv-card cvp-card" id="pratinjau" aria-labelledby="cvp-title">
                            <div class="cvp-top">
                                <div>
                                    <h2 id="cvp-title">Pratinjau CV-mu</h2>
                                    @if (! empty($preview['pages']))
                                        <p>Ini CV aslimu. Klik bagian yang bertanda: CV diperbesar dan muncul catatan singkatnya. Hijau sudah bagus, kuning kurang menonjol, merah perlu diperbaiki.</p>
                                    @else
                                        <p>Panah menunjuk bagian CV yang sudah bagus, kurang menonjol, atau perlu diperbaiki. Baris hijau sudah berisi pencapaian ber-angka, baris kuning perlu ditulis ulang.</p>
                                    @endif
                                </div>
                            </div>
                            @if (! empty($preview['pages']))
                                <link rel="stylesheet" href="{{ asset('css/cv-doc.css') }}?v={{ @filemtime(public_path('css/cv-doc.css')) }}">
                                @include('careers.partials.cv-doc-preview', ['blocks' => $preview['blocks'], 'counts' => $preview['counts'], 'pages' => $preview['pages']])
                                <script src="{{ asset('js/cv-doc.js') }}?v={{ @filemtime(public_path('js/cv-doc.js')) }}" defer></script>
                            @else
                                @include('careers.partials.cv-preview', ['blocks' => $preview['blocks'], 'counts' => $preview['counts']])
                            @endif
                        </section>
                    @endif

                    <div>
                        <div class="cv-sec">
                            <h2>Karier yang paling cocok</h2>
                            <p>Diurutkan dari kecocokan tertinggi. Celah skill bisa dibuka untuk mulai belajar.</p>
                        </div>
                        <ul class="cv-list">
                            @foreach ($top->take(3) as $m)
                                <li class="cv-match {{ $loop->first ? 'is-top' : '' }}">
                                    <div class="cv-match-h">
                                    <div class="cv-ring" style="--p: {{ (int) $m['score'] }}%" role="img" aria-label="Kecocokan {{ $m['score'] }} persen"><b>{{ $m['score'] }}%</b></div>
                                    <div class="cv-match-b">
                                        <span class="cv-rank">{{ $loop->first ? 'Paling cocok' : 'Peringkat '.$loop->iteration }}</span>
                                        <h3><a href="{{ route('careers.show', $m['career']) }}">{{ $m['career']->name }}</a></h3>
                                    </div>
                                    </div>
                                    @if ($m['missing'])
                                        <div class="cv-gap">
                                            <span>Celah skill</span>
                                            <ul>
                                                @foreach (array_slice($m['missing'], 0, 3) as $miss)
                                                    <li><a href="{{ \App\Models\Lesson::ccSearchUrl('belajar '.$miss.' untuk pemula') }}" target="_blank" rel="noopener">{{ $miss }} &#8599;</a></li>
                                                @endforeach
                                                @if (count($m['missing']) > 3)
                                                    <li><span class="cv-more" title="{{ implode(', ', array_slice($m['missing'], 3)) }}">+{{ count($m['missing']) - 3 }}</span></li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                    @if ($m['career']->course)
                                        <a href="{{ route('learning.show', $m['career']->course) }}" class="cv-go">Buka jalur belajar <x-icon name="arrow-right" /></a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <p class="cv-privacy"><x-icon name="shield" /> Skill dari CV ikut masuk ke profilmu dan tidak menimpa level yang sudah kamu atur. Teks CV disimpan terenkripsi.</p>
                    <form method="POST" action="{{ route('cv.destroy') }}" data-confirm="Hapus data CV, riwayat analisis, dan skill yang berasal dari CV?" class="cv-del">
                        @csrf @method('DELETE')
                        <button><x-icon name="trash" /> Hapus data CV saya</button>
                    </form>
                </div>
            @endif
        @endif
    </div>
@endsection
