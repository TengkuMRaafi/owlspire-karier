@extends('layouts.app')

@section('title', 'Harga & Premium')

@section('content')
    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $periode = ['month' => '/ bulan', 'quarter' => '/ 3 bulan', 'half' => '/ 6 bulan', 'year' => '/ tahun'];
        $lamaBulan = ['month' => 1, 'quarter' => 3, 'half' => 6, 'year' => 12];
        $when = fn ($d) => $d->translatedFormat('j M Y, H:i').' WIB';
        // Paket dengan harga per bulan terendah diberi tanda "Paling hemat" (hanya bila ada lebih dari satu paket).
        $perPass = (int) config('platform.exam.pass_attempts', 2);
        // Kolom Premium di tabel perbandingan: paket aktif dari admin dengan periode terpendek (lalu termurah), harga akhir setelah diskon.
        $cmpPlan = $plans->sortBy(fn ($p) => [$lamaBulan[$p->interval] ?? 99, $p->finalPrice()])->first();
        // Angka kuota di tabel perbandingan ikut config, supaya tidak beda dengan aturan sebenarnya.
        $cvPrem = (int) config('platform.ai_tools.cv_analysis.premium_daily', 5);
        $botFree = (int) config('platform.chatbot.free_daily', 10);
        $botPrem = (int) config('platform.chatbot.premium_daily', 50);
        $ivFree = (int) config('platform.ai_tools.interview.free_daily', 1);
        $ivPrem = (int) config('platform.ai_tools.interview.premium_daily', 5);
        $ticketSel = old('choice') === 'ticket' || $errors->has('exam_type_id') || $errors->has('quantity');
        $sel = $plans->firstWhere('id', (int) old('plan_id', $cmpPlan?->id));
        $lama = fn ($p) => ($b = $lamaBulan[$p->interval] ?? 1) >= 12 ? intdiv($b, 12).' tahun' : $b.' bulan';
        $planSorted = $plans->sortBy(fn ($p) => [$lamaBulan[$p->interval] ?? 99, $p->finalPrice()]);
        $planOptions = $planSorted->mapWithKeys(fn ($p) => [$p->id => $lama($p).' · '.$fmt($p->finalPrice())])->all();
        $planData = $plans->mapWithKeys(fn ($p) => [$p->id => [
            'name' => $p->name, 'normal' => (int) $p->price, 'discount' => $p->isDiscounted() ? (int) $p->discountAmount() : 0,
            'total' => (int) $p->finalPrice(), 'label' => $p->discount_label ?: 'Diskon Premium',
            'percent' => $p->isDiscounted() ? (int) $p->discountPercent() : 0,
            'sub' => 'Dibayar sekali '.($periode[$p->interval] ?? '').(($lamaBulan[$p->interval] ?? 1) > 1 ? ' · setara '.$fmt(intdiv($p->finalPrice(), $lamaBulan[$p->interval])).' per bulan' : ''),
        ]])->all();
        $qtyOptions = collect(range(1, 15))->mapWithKeys(fn ($n) => [$n => $n.' tiket'])->all();
        $cmpUnit = ['month' => 'per bulan', 'quarter' => 'per 3 bulan', 'half' => 'per 6 bulan', 'year' => 'per tahun'];
        $bestId = $plans->count() > 1 ? $plans->sortBy(fn ($p) => $p->finalPrice() / ($lamaBulan[$p->interval] ?? 1))->first()->id : null;
    @endphp

    {{-- ============ Hero: kartu Premium ============ --}}
    <section class="pm-hero on-dark">
        <div class="pm-hero-grid mx-auto max-w-6xl px-4">
            <div class="pm-copy">
                <p class="pm-eyebrow"><span aria-hidden="true">&#9670;</span> Harga</p>
                <h1 class="font-display pm-title">Mulai gratis. <br>Naik ke <em>Premium</em> saat siap diuji sungguhan.</h1>
                <p class="pm-lead">Gratis untuk mencoba semua simulasi. Premium membuka soal level ujian, analisis CV, dan jalur belajar lengkap.</p>
                <div class="pm-cta">
                    <a href="#paket" class="btn-primary">{{ $promo ? 'Ambil diskon '.($promo['multiple'] ? 'hingga ' : '').$promo['percent'].'%' : 'Lihat paket' }}</a>
                    <a href="{{ route('exam.index') }}" class="btn-outline-light">Coba gratis dulu</a>
                </div>
            </div>

            {{-- Kartu Premium animasi: hiasan saja --}}
            <div class="pm-stage" aria-hidden="true">
                <span class="pm-glow"></span>
                <div class="pm-card">
                    <span class="pm-shine"></span>
                    <div class="pm-card-top">
                        <span class="pm-badge">{{ $promo ? 'Diskon '.($promo['multiple'] ? 'hingga ' : '').$promo['percent'].'%' : 'Paling lengkap' }}</span>
                        <svg class="pm-crown" viewBox="0 0 24 24"><path d="M3 18h18M4 8l4.5 4L12 5l3.5 7L20 8l-1.5 10h-13z"/></svg>
                    </div>
                    <p class="pm-card-name font-display">Premium</p>
                    <ul class="pm-feats">
                        @foreach (collect(config('platform.premium.features'))->take(4) as $f)
                            <li style="--k: {{ $loop->index }}">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.5l3 3 6-6.5"/></svg>
                                <span>{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <span class="pm-spark pm-sp1"></span>
                <span class="pm-spark pm-sp2"></span>
                <span class="pm-spark pm-sp3"></span>
            </div>
        </div>
    </section>

    @if ($isPremium)
        <div class="mx-auto mt-8 max-w-5xl px-4">
            <div class="ck-note is-ok">
                <x-icon name="crown" />
                <p>Premium kamu aktif{{ $premiumUntil ? ' sampai '.$premiumUntil->translatedFormat('d M Y') : '' }}. Membeli paket lagi akan memperpanjang dari tanggal berakhir.</p>
            </div>
        </div>
    @endif

    {{-- ============ Perbandingan singkat: label + Gratis | Tiket | Premium. Harga ikut pengaturan admin. ============ --}}
    <section id="bandingkan" class="cmp mx-auto max-w-6xl px-4" aria-label="Perbandingan Gratis, Tiket, dan Premium">
        <table class="cmp-t">
            <colgroup><col class="cmp-lab"><col><col><col></colgroup>
            <thead>
                <tr>
                    <td class="cmp-blank"></td>
                    <th scope="col" class="cmp-tab is-free"><b>Rp 0</b><small>selamanya</small><span>Gratis</span></th>
                    <th scope="col" class="cmp-tab is-tiket"><b>{{ $fmt($price) }}</b><small>per simulasi</small><span>Tiket</span></th>
                    <th scope="col" class="cmp-tab is-prem"><b>{{ $cmpPlan ? $fmt($cmpPlan->finalPrice()) : '-' }}</b><small>{{ $cmpPlan ? ($cmpUnit[$cmpPlan->interval] ?? '') : 'segera hadir' }}</small><span>Premium</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['Simulasi coba (15 soal)', 'Rasakan atmosfer ujian sesungguhnya, tanpa biaya.', [true, true, true]],
                    ['Ujian penuh', 'Latihan utuh dengan tempo dan penilaian layaknya hari-H.', [false, $perPass.'x', 'Tanpa batas']],
                    ['Passing grade dan peringkat', 'Ketahui posisimu dengan pasti, sebelum hari-H tiba.', [false, true, true]],
                    ['Jalur belajar', 'Langkah belajar yang tertata, dari dasar hingga siap diuji.', ['2 topik', '2 topik', 'Semua topik']],
                    ['Analisis arah karier lengkap', 'Arah karier yang jelas, lengkap dengan langkah nyatanya.', [false, false, true]],
                    ['Analisis CV', 'Dinilai AI, agar CV-mu tampil lebih meyakinkan.', [false, false, $cvPrem.'x / hari']],
                    ['Wawancara AI', 'Berlatih tampil percaya diri, lengkap dengan umpan balik.', $ivFree > 0 ? [$ivFree.'x / hari', $ivFree.'x / hari', $ivPrem.'x / hari'] : [false, false, $ivPrem.'x / hari']],
                    ['Asisten AI', 'Pendamping yang siap menjawab, kapan pun kamu butuh.', [$botFree.' / hari', $botFree.' / hari', $botPrem.' / hari']],
                ] as [$label, $desc, $cells])
                    <tr>
                        <th scope="row">{{ $label }}<small class="cmp-d">{{ $desc }}</small></th>
                        @foreach ($cells as $cell)
                            <td>
                                @if ($cell === true)
                                    <svg class="cmp-yes" viewBox="0 0 24 24" role="img" aria-label="Ya"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                @elseif ($cell === false)
                                    <svg class="cmp-no" viewBox="0 0 24 24" role="img" aria-label="Tidak"><path d="M7 7l10 10M17 7L7 17"/></svg>
                                @else
                                    <span class="cmp-txt">{{ $cell }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="cmp-note">Tiket: {{ $perPass }}x ujian penuh per simulasi, habis berarti terkunci lagi. Harga mengikuti pengaturan terbaru.</p>
    </section>

    {{-- ============ Pilih paket + ringkasan pesanan ============ --}}
    <section id="paket" class="ck mx-auto max-w-6xl px-4" aria-labelledby="ck-h">
        @if ($promo)
            <div class="ck-promo" role="status">
                <span class="ck-promo-ic" aria-hidden="true"><x-icon name="ticket" /></span>
                <div class="ck-promo-body">
                    <p class="ck-promo-t">{{ $promo['label'] }}: diskon {{ $promo['multiple'] ? 'hingga ' : '' }}{{ $promo['percent'] }}% untuk Premium</p>
                    <p class="ck-promo-d">
                        @if ($promo['starts_at'] && $promo['ends_at'])
                            Berlaku {{ $when($promo['starts_at']) }} sampai {{ $when($promo['ends_at']) }}.
                        @elseif ($promo['ends_at'])
                            Berlaku sampai {{ $when($promo['ends_at']) }}.
                        @else
                            Berlaku untuk pembelian saat ini.
                        @endif
                        Harga di bawah sudah dipotong otomatis.
                    </p>
                </div>
                @if ($promo['ends_at'])
                    <div class="ck-promo-cd"><span>Berakhir dalam</span><time data-promo-countdown="{{ $promo['ends_at']->getTimestamp() * 1000 }}" datetime="{{ $promo['ends_at']->toIso8601String() }}" data-promo-done="Promo berakhir">{{ $promo['ends_at']->diffForHumans(null, true) }}</time></div>
                @endif
            </div>
        @endif

        <div class="ck-head">
            <h2 id="ck-h" class="font-display ck-title">Pilih paket, bayar lewat QRIS</h2>
            <ol class="ck-steps" aria-label="Langkah pembelian">
                <li class="is-now"><b>1</b> Pilih paket</li>
                <li><b>2</b> Bayar QRIS</li>
                <li><b>3</b> Premium aktif</li>
            </ol>
        </div>

        {{-- Satu form untuk Premium dan tiket. JS (billing.js) mengganti action: billing.plan atau billing.buy. --}}
        <form method="POST" action="{{ route('billing.plan') }}" class="ck-grid" data-action-plan="{{ route('billing.plan') }}" data-action-ticket="{{ route('billing.buy') }}" data-plans="{{ json_encode($planData) }}" data-ticket-price="{{ $price }}">
            @csrf
            <input type="hidden" name="ranking_shown" value="1">
            <div class="ck-main">
                <div class="ck-pick">
                    <fieldset class="ck-plans">
                        <legend class="sr-only">Pilih Premium atau tiket</legend>

                        {{-- Premium: lama langganan lewat dropdown --}}
                        <div class="ck-opt">
                            <label class="ck-plan">
                                <input type="radio" name="choice" value="premium" class="sr-only" data-kind="premium" @checked(! $ticketSel) @disabled($plans->isEmpty())>
                                <span class="ck-tick" aria-hidden="true"></span>
                                <span class="ck-plan-main">
                                    <span class="ck-plan-name">Premium</span>
                                    <span class="ck-plan-sub" data-card-sub>{{ $sel ? 'Dibayar sekali '.($periode[$sel->interval] ?? '').(($lamaBulan[$sel->interval] ?? 1) > 1 ? ' · setara '.$fmt(intdiv($sel->finalPrice(), $lamaBulan[$sel->interval])).' per bulan' : '') : 'Paket belum disiapkan.' }}</span>
                                </span>
                                <span class="ck-plan-price">
                                    <s class="pr-old" data-card-old @unless ($sel?->isDiscounted()) hidden @endunless>{{ $fmt($sel?->price ?? 0) }}</s>
                                    <b data-card-price>{{ $fmt($sel?->finalPrice() ?? 0) }}</b>
                                </span>
                                <span class="ck-save" data-card-save @unless ($sel?->isDiscounted()) hidden @endunless>Hemat {{ $sel?->discountPercent() }}%</span>
                            </label>
                            <div class="ck-opts" data-premium-opts @if ($ticketSel) hidden @endif>
                                <div class="ck-ticket-pick">
                                    <span class="label">Lama langganan</span>
                                    <x-dropdown name="plan_id" :options="$planOptions" :value="$sel?->id" aria="Lama langganan Premium" block />
                                    @error('plan_id')
                                        <p class="ck-err" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tiket: jumlah 1 sampai 15, per simulasi --}}
                        <div class="ck-opt" id="tiket">
                            <label class="ck-plan">
                                <input type="radio" name="choice" value="ticket" class="sr-only" data-kind="ticket" @checked($ticketSel)>
                                <span class="ck-tick" aria-hidden="true"></span>
                                <span class="ck-plan-main">
                                    <span class="ck-plan-name">Tiket simulasi<i class="ck-tag is-alt">Tanpa langganan</i></span>
                                    <span class="ck-plan-sub">Tiap tiket = {{ $perPass }}x ujian penuh untuk 1 simulasi, lengkap dengan passing grade dan peringkat.</span>
                                </span>
                                <span class="ck-plan-price"><b>{{ $fmt($price) }}</b><small class="ck-per">per tiket</small></span>
                            </label>
                            <div class="ck-opts" data-tkt-opts @unless ($ticketSel) hidden @endunless>
                                <div class="ck-ticket-pick">
                                    <span class="label">Pilih simulasi</span>
                                    <x-dropdown name="exam_type_id" :options="$ticketOptions" :value="old('exam_type_id', optional($types->firstWhere('code', $selected))->id)" aria="Pilih simulasi" block />
                                    @error('exam_type_id')
                                        <p class="ck-err" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="ck-ticket-pick">
                                    <span class="label">Jumlah tiket</span>
                                    <x-dropdown name="quantity" :options="$qtyOptions" :value="old('quantity', 1)" aria="Jumlah tiket" block />
                                    @error('quantity')
                                        <p class="ck-err" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                @guest
                                    <div class="ck-ticket-pick">
                                        <label class="label" for="tkt-name">Nama di peringkat <small>(opsional)</small></label>
                                        <input id="tkt-name" name="display_name" value="{{ old('display_name') }}" maxlength="40" autocomplete="nickname" class="ui-input" placeholder="mis. Budi S.">
                                        @error('display_name')
                                            <p class="ck-err" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <label class="au-remember"><input type="checkbox" name="show_in_ranking" value="1" @checked(old('ranking_shown') ? old('show_in_ranking') : true)> Tampilkan aku di papan peringkat</label>
                                @endguest
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <aside class="ck-sum" data-sum aria-label="Ringkasan pesanan">
                <h3 class="ck-sum-h">Ringkasan pesanan</h3>
                <dl class="ck-sum-rows">
                    <div><dt>Paket</dt><dd data-sum-name>{{ $sel?->name ?? '-' }}</dd></div>
                    <div data-sum-sim-row hidden><dt>Simulasi</dt><dd data-sum-sim></dd></div>
                    <div data-sum-qty-row hidden><dt>Jumlah</dt><dd data-sum-qty></dd></div>
                    <div><dt>Harga normal</dt><dd data-sum-normal>{{ $fmt($sel?->price ?? 0) }}</dd></div>
                    <div class="is-cut" data-sum-cut-row @if (! $sel?->isDiscounted()) hidden @endif><dt data-sum-label>{{ $sel?->discount_label ?: 'Diskon Premium' }}</dt><dd data-sum-cut>-{{ $fmt($sel?->discountAmount() ?? 0) }}</dd></div>
                    <div class="is-total"><dt>Total bayar</dt><dd data-sum-total>{{ $fmt($sel?->finalPrice() ?? 0) }}</dd></div>
                </dl>

                @guest
                    <div class="ck-email">
                        <label for="email" class="ck-email-l">Email untuk akses Premium</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="nama@email.com" class="ui-input"
                               @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                        @error('email')
                            <p id="email-error" class="ck-err" role="alert">{{ $message }}</p>
                        @enderror
                        <p class="ck-hint">Tidak perlu daftar atau masuk. Setelah bayar, akunmu dibuatkan otomatis dan sandinya muncul di halaman pembayaran (bisa disalin, nanti bisa diganti).</p>
                    </div>
                @endguest

                <button type="submit" class="btn-primary ck-pay" @disabled($plans->isEmpty())>Bayar dengan QRIS</button>
                @guest
                    <p class="ck-login">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
                @endguest

                <ul class="ck-trust">
                    <li><x-icon name="shield" /> Bisa dibayar dari semua bank dan e-wallet yang mendukung QRIS</li>
                    <li><x-icon name="clock" /> Aktif otomatis begitu pembayaran masuk</li>
                    <li><x-icon name="check" /> Tidak diperpanjang otomatis. Bayar sekali per periode</li>
                </ul>
                @include('billing._refund-note', ['class' => 'ck-hint'])
            </aside>
        </form>
    </section>

@endsection
