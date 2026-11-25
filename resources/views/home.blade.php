@extends('layouts.app')

@section('title', config('platform.brand.tagline'))

@section('content')
    {{-- Muat ulang selalu mulai dari paling atas (kecuali URL membawa #anchor). --}}
    <script>
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        if (!location.hash) scrollTo(0, 0);
    </script>
    @include('partials.intro')

    {{-- Ikon (SVG sprite). Dipakai di hero dan roadmap, tanpa emoji. --}}
    <svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
        <defs>
            @foreach ([
                'compass' => '<circle cx="12" cy="12" r="10"/><polygon points="16.2 7.8 14.1 14.1 7.8 16.2 9.9 9.9 16.2 7.8"/>',
                'target' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
                'timer' => '<path d="M10 2h4M12 14l3-3"/><circle cx="12" cy="14" r="8"/>',
                'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
                'unlock' => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
                'briefcase' => '<rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
                'file' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7zM14 2v4a2 2 0 0 0 2 2h4M8 13h8M8 17h8"/>',
                'cap' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
            ] as $id => $paths)
                <symbol id="i-{{ $id }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $paths !!}</symbol>
            @endforeach
        </defs>
    </svg>

    {{-- ============ HERO (panggung gambar berganti) ============ --}}
    @include('partials.hero')

    {{-- ============ PAPAN SKOR OWLSPIRE (antara hero dan roadmap) ============ --}}
    @include('partials.scoreboard')

    {{-- ============ ROADMAP: JALUR OWLSPIRE (interaktif) ============ --}}
    @include('partials.roadmap')

    {{-- ============ PILIH SIMULASI (3 seleksi + jalur karier) ============ --}}
    @include('partials.sim-pilih')

    {{-- ============ SELEKSI TERDEKAT (hanya tampil bila admin sudah mengisi jadwal) ============ --}}
    @include('partials.selection-strip')

    {{-- ============ BANNER BERJALAN (pemisah simulasi dan bagian karier/peringkat) ============ --}}
    @include('partials.marquee')

    {{-- ============ PAPAN KARIER (kiri) + PERINGKAT (kanan) ============ --}}
    @include('partials.duo')

    {{-- ============ CARI ARAH KARIERMU | COBA SIMULASI ============ --}}
    @include('partials.direction')

    {{-- ============ FAQ ============ --}}
    @php
        $faqs = [
            ['Apakah perlu daftar untuk mencoba?', 'Tidak. Mode coba gratis bisa dikerjakan tanpa akun. Akun dibutuhkan untuk menyimpan riwayat, skor kesiapan, dan membeli Premium.'],
            ['Apa bedanya mode coba dan ujian penuh?', 'Mode coba memakai soal singkat dengan durasi 5 menit. Mode lengkap (Premium) memakai soal level lanjut atau jumlah soal dan durasi seperti aslinya, masuk papan peringkat, dan mendapat status lulus passing grade.'],
            ['Bagaimana peringkat dihitung?', 'Peringkat hanya memakai ujian penuh. Skor tertinggi di atas, dan bila skor sama, pengerjaan tercepat menang. Nama peserta disamarkan.'],
            ['Bagaimana cara membayar Premium?', 'Lewat QRIS dari aplikasi bank atau e-wallet apa pun. Pembayaran dikonfirmasi otomatis, jadi Premium aktif beberapa detik setelah kamu membayar.'],
            ['Dari mana video belajarnya?', 'Dari YouTube. Tiap topik punya penjelasan singkat dan tombol play; videonya diputar lewat pemutar resmi YouTube di dalam halaman Owlspire, dan tautan ke sumber aslinya selalu ada.'],
            ['Bagaimana CV saya diperlakukan?', 'Analisis CV khusus Premium. Teks CV hanya dipakai untuk membaca skill dan bisa kamu hapus kapan saja dari halaman Analisis CV.'],
            ['Kalau waktu habis saat saya mengerjakan?', 'Jawaban yang sudah kamu pilih otomatis dinilai. Waktu dihitung server, jadi tetap berjalan walau tab ditutup.'],
            ['Apa isi menu Belajar?', 'Satu jalur belajar per karier, empat topik berurutan. Dua topik pertama gratis, sisanya untuk Premium.'],
        ];
    @endphp
    <section class="fq" data-fq>
        <div class="fq-wrap">
            <div class="fq-side" data-reveal>
                <p class="eyebrow">FAQ</p>
                <h2 class="font-display fq-title">Pertanyaan yang sering muncul</h2>
                <p class="fq-sub">Klik satu pertanyaan untuk melihat jawabannya.</p>
            </div>

            <div class="fq-list">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="fq-item" data-reveal style="--k: {{ $i }}">
                        <summary class="fq-q">
                            <span class="fq-no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="fq-qt">{{ $q }}</span>
                            <span class="fq-ico" aria-hidden="true"><i></i><i></i></span>
                        </summary>
                        <div class="fq-a"><p>{{ $a }}</p></div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <script>
        (function () {
            var root = document.querySelector('[data-fq]'); if (!root) return;
            var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            var items = [].slice.call(root.querySelectorAll('.fq-item'));
            function toggle(d, want) {
                var a = d.querySelector('.fq-a'), isOpen = d.open && d.dataset.state !== 'closing';
                if (want === undefined) want = !isOpen;
                if (want === isOpen) return;
                if (want) items.forEach(function (o) { if (o !== d) toggle(o, false); }); // akordeon: buka satu, yang lain menutup
                if (d.anim) d.anim.cancel();
                if (reduce || !a.animate) { d.open = want; d.dataset.state = want ? 'open' : ''; return; }
                if (want) {
                    d.open = true; d.dataset.state = 'open';
                    var h = a.offsetHeight;
                    d.anim = a.animate({ height: ['0px', h + 'px'], opacity: [0, 1] }, { duration: 380, easing: 'cubic-bezier(.2,.8,.2,1)' });
                    d.anim.onfinish = function () { d.anim = null; };
                } else {
                    d.dataset.state = 'closing';
                    d.anim = a.animate({ height: [a.offsetHeight + 'px', '0px'], opacity: [1, 0] }, { duration: 260, easing: 'cubic-bezier(.4,0,.2,1)' });
                    d.anim.onfinish = function () { d.open = false; d.dataset.state = ''; d.anim = null; };
                }
            }
            items.forEach(function (d) {
                d.querySelector('summary').addEventListener('click', function (e) { e.preventDefault(); toggle(d); });
            });
        })();
    </script>

    {{-- ============ PEMANDU OWLSPIRE (maskot yang menemani sepanjang halaman) ============ --}}
    @include('partials.guide')
@endsection
