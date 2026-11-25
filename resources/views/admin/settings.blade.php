@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('heading', 'Pengaturan')
@section('subheading', $system ? 'Akun, peran dan akses, pembayaran, dan sistem.' : 'Akun dan kata sandimu.')

@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $nav = ['Akun' => [['akun', 'Akun', 'user']]];
    if ($system) {
        $nav['Sistem'] = [['peran', 'Peran dan akses', 'shield'], ['pembayaran', 'Pembayaran', 'card'], ['fitur', 'Fitur dan kuota', 'sparkle'], ['platform', 'Platform', 'layers'], ['sistem', 'Info sistem', 'sliders']];
    }
    $roles = config('roles.roles');
    $site = app(\App\Services\Settings\SiteSettings::class);
    $grantable = config('roles.staff_grantable');
    $staffNow = $site->staffAreas();
    $ann = $site->announcement();
    $mnt = $site->maintenance();
    $periods = ['month' => 'per bulan', 'quarter' => 'per 3 bulan', 'half' => 'per 6 bulan', 'year' => 'per tahun'];
    if ($system) {
        // Tiap baris: [label, nilai, nada badge (ok/warn/off/null), ikon]
        $midtrans = config('platform.payment.driver') === 'midtrans';
        $payRows = [
            ['Gateway pembayaran', $midtrans ? 'Midtrans ('.(config('platform.payment.midtrans.is_production') ? 'produksi' : 'sandbox').')' : 'Uji coba (fake)', $midtrans ? 'ok' : 'warn', 'card'],
            ['Kunci server Midtrans', $filled(config('platform.payment.midtrans.server_key')) ? 'Terisi' : 'Belum diisi', $filled(config('platform.payment.midtrans.server_key')) ? 'ok' : 'warn', 'lock'],
            ['Harga tiket ujian penuh', $rp(config('platform.exam.pass_price')), null, 'ticket'],
            ['Masa berlaku tiket', config('platform.exam.pass_valid_days') ? config('platform.exam.pass_valid_days').' hari' : 'Tidak kedaluwarsa', null, 'clock'],
            ['Pesanan kedaluwarsa setelah', config('platform.exam.order_expires_hours').' jam', null, 'history'],
        ];
        $platformRows = [
            'Fitur' => [
                ['Menu Belajar', config('platform.learning_open') ? 'Terbuka' : 'Segera Hadir', config('platform.learning_open') ? 'ok' : 'off', 'book'],
                ['Rating di papan skor', config('platform.scoreboard.rating') !== null ? config('platform.scoreboard.rating') : 'Tidak ditampilkan', null, 'star'],
            ],
            'Kuota dan simulasi' => [
                ['Asisten AI per hari (Gratis)', config('platform.chatbot.free_daily').' pesan', null, 'sparkle'],
                ['Asisten AI per hari (Premium)', config('platform.chatbot.premium_daily').' pesan', null, 'crown'],
                ['Soal mode coba per segmen', config('platform.exam.trial_questions_per_segment').' soal', null, 'list'],
                ['Durasi simulasi karier', (int) (config('platform.career_exam.duration_seconds') / 60).' menit', null, 'clock'],
            ],
            'Sumber data eksternal' => [
                ['Bot Telegram lowongan', $filled(config('platform.telegram.bot')) ? 'Terhubung' : 'Belum diisi', $filled(config('platform.telegram.bot')) ? 'ok' : 'warn', 'bell'],
                ['Sumber lowongan (LinkedIn, Jobicy, RemoteOK, Arbeitnow)', 'Aktif, tanpa kunci', 'ok', 'briefcase'],
            ],
        ];
        $systemRows = [
            ['Aplikasi', config('platform.brand.name'), null, 'layout'],
            ['Lingkungan', app()->environment(), app()->environment('production') ? 'ok' : 'warn', 'sliders'],
            ['Zona waktu', config('app.timezone'), null, 'clock'],
            ['Basis data', config('database.default'), null, 'layers'],
            ['Penyimpanan sesi', config('session.driver'), null, 'lock'],
            ['Laravel', app()->version(), null, 'file'],
            ['PHP', PHP_VERSION, null, 'file'],
        ];
    }
    $toneClass = ['ok' => 'is-success has-dot', 'warn' => 'is-warning has-dot', 'off' => 'has-dot'];
@endphp

@section('content')
    {{-- Pola sama dengan Pengaturan akun pengguna: sidebar bagian + satu panel tampil sekaligus. --}}
    <div class="ac-set" data-set>
        <aside class="ac-set-side">
            <nav class="ac-set-nav" aria-label="Bagian pengaturan">
                @foreach ($nav as $label => $items)
                    @if (count($items) === 1)
                        @foreach ($items as [$id, $text, $icon])
                            <div class="ac-set-col is-single"><a href="#{{ $id }}" data-set-tab="{{ $id }}" class="ac-set-colbtn"><x-icon :name="$icon" /><span class="ac-set-colt"><small>{{ $label }}</small><b>{{ $text }}</b></span></a></div>
                        @endforeach
                    @else
                        <div class="ac-set-col" data-set-col>
                            <button type="button" class="ac-set-colbtn" aria-haspopup="true" aria-expanded="false" data-set-colbtn>
                                <x-icon :name="$items[0][2]" />
                                <span class="ac-set-colt"><small>{{ $label }}</small><b data-set-cur data-set-def="Pilih bagian">Pilih bagian</b></span>
                                <svg class="ac-set-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div class="ac-set-menu" hidden>
                                @foreach ($items as [$id, $text, $icon])
                                    <a href="#{{ $id }}" data-set-tab="{{ $id }}"><x-icon :name="$icon" /><span>{{ $text }}</span></a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="ac-set-body">
            {{-- ===== Akun ===== --}}
            <section id="akun" class="pn-card ac-set-sec" aria-labelledby="h-akun">
                <header class="ac-set-h">
                    <span class="ac-set-ic" aria-hidden="true"><x-icon name="user" /></span>
                    <div><h2 id="h-akun">Akun</h2><p>Identitas dan kata sandi akun {{ mb_strtolower($user->roleLabel()) }} ini.</p></div>
                </header>

                <div class="ad-st-me">
                    <span class="ad-st-av" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div class="ad-st-me-t">
                        <b>{{ $user->name }}</b>
                        <small>{{ $user->email }}</small>
                    </div>
                    <span class="ui-badge {{ $user->isAdmin() ? 'is-accent' : 'is-dark' }}">@if ($user->isAdmin())<x-icon name="lock" class="size-3" /> @endif{{ $user->roleLabel() }}</span>
                    @if (! empty($roles[$user->role]['desc']))<p class="ad-st-me-d">{{ $roles[$user->role]['desc'] }}</p>@endif
                </div>

                <form method="POST" action="{{ route('admin.settings.password') }}" class="ac-set-form">
                    @csrf @method('PUT')
                    <h3 class="ac-set-sub">Ganti kata sandi</h3>
                    <div class="ui-field"><label for="current_password">Kata sandi saat ini</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" class="ui-input" required></div>
                    <div class="ui-form-grid">
                        <div class="ui-field"><label for="password">Kata sandi baru</label><input id="password" type="password" name="password" autocomplete="new-password" class="ui-input" required><small>Minimal 8 karakter.</small></div>
                        <div class="ui-field"><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="ui-input" required></div>
                    </div>
                    <div class="ac-set-foot"><button class="btn-primary" type="submit">Simpan kata sandi</button></div>
                </form>
            </section>

            @if ($system)
                {{-- ===== Peran dan akses ===== --}}
                <section id="peran" class="pn-card ac-set-sec" aria-labelledby="h-peran">
                    <header class="ac-set-h">
                        <span class="ac-set-ic" aria-hidden="true"><x-icon name="shield" /></span>
                        <div><h2 id="h-peran">Peran dan akses</h2><p>Siapa boleh membuka apa. Paket Gratis dan Premium hanya berlaku untuk pengguna biasa.</p></div>
                    </header>

                    <div class="ad-st-roles">
                        @foreach ($roles as $key => $r)
                            <div class="ad-st-role {{ $key === 'admin' ? 'is-lock' : '' }}">
                                <span class="ad-st-role-n">{{ number_format($roleCounts[$key] ?? 0) }}</span>
                                <div>
                                    <b>{{ $r['label'] }}@if ($key === 'admin') <x-icon name="lock" class="size-3" />@endif</b>
                                    <small>{{ $r['desc'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.settings.staff') }}" class="ac-set-staff">
                        @csrf @method('PUT')
                        <div class="ui-table-wrap">
                            <div class="ui-table-scroll">
                                <table class="ui-table no-stack ac-cmp">
                                    <thead><tr><th>Area panel</th><th class="is-num" style="text-align:center">Admin</th><th class="is-num" style="text-align:center">Staf</th></tr></thead>
                                    <tbody>
                                        @foreach (config('roles.areas') as $area => $allowed)
                                            @php $isAdm = in_array('admin', $allowed, true); $canGrant = in_array($area, $grantable, true); @endphp
                                            <tr>
                                                <td><span class="ui-cell"><b>{{ config("roles.area_labels.{$area}") }}</b></span></td>
                                                <td style="text-align:center">@if ($isAdm)<span class="ac-yes" role="img" aria-label="Boleh"><x-icon name="check" /></span>@else<span class="ac-no" role="img" aria-label="Tidak"><x-icon name="x" /></span>@endif</td>
                                                <td style="text-align:center">
                                                    @if ($area === 'dashboard')
                                                        <span class="ac-yes" role="img" aria-label="Selalu boleh"><x-icon name="check" /></span>
                                                    @elseif ($canGrant)
                                                        <label class="ac-cmp-chk"><input type="checkbox" name="areas[]" value="{{ $area }}" @checked(in_array($area, $staffNow, true))> <span class="sr-only">Izinkan staf membuka {{ config("roles.area_labels.{$area}") }}</span></label>
                                                    @else
                                                        <span class="ac-no" role="img" aria-label="Tidak"><x-icon name="x" /></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td><span class="ui-cell"><b>Fitur pengguna</b><small>Simulasi, belajar, CV, Asisten AI</small></span></td>
                                            <td style="text-align:center"><span class="ac-yes" role="img" aria-label="Akses penuh"><x-icon name="check" /></span></td>
                                            <td style="text-align:center"><span class="ac-yes" role="img" aria-label="Akses penuh"><x-icon name="check" /></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="ad-hint">Centang area yang boleh dibuka staf. Pengguna, Log aktivitas, dan pengaturan sistem selalu khusus admin.</p>
                        <div class="ac-set-foot"><button class="btn-primary" type="submit">Simpan akses staf</button></div>
                    </form>
                </section>

                {{-- ===== Pembayaran ===== --}}
                <section id="pembayaran" class="pn-card ac-set-sec" aria-labelledby="h-pembayaran">
                    <header class="ac-set-h">
                        <span class="ac-set-ic" aria-hidden="true"><x-icon name="card" /></span>
                        <div><h2 id="h-pembayaran">Pembayaran</h2><p>Gateway, harga, dan paket Premium. Nilai dibaca dari konfigurasi.</p></div>
                    </header>

                    <div class="ad-st-tiles">
                        @foreach ($payRows as [$k, $v, $t, $ic])
                            @include('admin.partials.setting-tile', ['label' => $k, 'value' => $v, 'tone' => $t, 'icon' => $ic, 'tones' => $toneClass])
                        @endforeach
                    </div>

                    <div class="ac-set-form">
                        <h3 class="ac-set-sub"><x-icon name="crown" /> Paket Premium</h3>
                        <div class="ad-st-plans">
                            @forelse ($plans as $p)
                                <div class="ad-st-plan {{ $p->is_active ? '' : 'is-off' }}">
                                    <span class="ad-st-plan-n">{{ $p->name }}</span>
                                    @if ($p->isDiscounted())
                                        <b>{{ $rp($p->finalPrice()) }} <s class="ad-st-plan-old">{{ $rp($p->price) }}</s></b>
                                    @else
                                        <b>{{ $rp($p->price) }}</b>
                                    @endif
                                    <small>{{ $periods[$p->interval] ?? $p->interval }}</small>
                                    @if ($p->follow_quarter)<span class="ui-badge is-accent">Ikut harga 3 bulan</span>@endif
                                    @if ($p->isDiscounted())<span class="ui-badge is-success">Diskon {{ $p->discountPercent() }}%</span>@elseif ($p->isDiscountScheduled())<span class="ui-badge is-warning">Diskon terjadwal</span>@endif
                                    @unless ($p->is_active)<span class="ui-badge">nonaktif</span>@endunless
                                </div>
                            @empty
                                <div class="ui-empty is-sm" style="grid-column:1/-1">Belum ada paket. Jalankan PlanSeeder.</div>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('admin.plans.prices') }}" class="ad-st-prices">
                            @csrf @method('PUT')
                            <h3 class="ac-set-sub">Atur harga dan diskon</h3>
                            <p class="ad-hint">Pilih jenis diskon di bawah tiap paket untuk mengaktifkannya. Selama rentang waktunya, harga coret dan harga baru muncul di halaman Harga, dan spanduk promo tampil di seluruh situs.</p>
                            @php $fmtAt = fn ($d) => $d?->copy()->timezone(config('platform.promo.timezone', 'Asia/Jakarta'))->format('Y-m-d\TH:i'); @endphp
                            @foreach ($plans as $p)
                                @php $k = "plans.{$p->id}."; @endphp
                                <div class="ad-st-pg" data-disc-form>
                                    <div class="ad-st-price">
                                        <label for="price-{{ $p->id }}">{{ $p->name }} <small>({{ $periods[$p->interval] ?? $p->interval }})</small></label>
                                        @php $canFollow = $p->code !== \App\Models\Plan::QUARTER_CODE && in_array($p->interval, ['half', 'year'], true); $isFollow = $canFollow && (bool) (old('plans') ? old("plans.{$p->id}.follow_quarter") : $p->follow_quarter); @endphp
                                        <span class="ad-st-price-f">Rp <input id="price-{{ $p->id }}" type="number" min="0" name="plans[{{ $p->id }}][price]" value="{{ old("plans.{$p->id}.price", $p->price) }}" class="ui-input" required data-disc-price @readonly($isFollow)></span>
                                        <label class="au-remember"><input type="checkbox" name="plans[{{ $p->id }}][is_active]" value="1" @checked(old('plans') ? old("plans.{$p->id}.is_active") : $p->is_active)> Aktif</label>
                                        @if ($canFollow)
                                            <label class="au-remember"><input type="checkbox" name="plans[{{ $p->id }}][follow_quarter]" value="1" @checked($isFollow) onchange="var f=this.closest('[data-disc-form]');f.querySelector('[data-disc-price]').readOnly=this.checked;f.querySelector('.ad-st-disc').style.opacity=this.checked?.5:1;f.querySelector('.ad-st-disc').style.pointerEvents=this.checked?'none':''"> Ikuti harga 3 bulan <small>(otomatis {{ intdiv($p->interval === 'year' ? 12 : 6, 3) }}x harga dan diskon paket 3 bulan)</small></label>
                                        @endif
                                    </div>
                                    <div class="ad-st-disc" @if ($isFollow) style="opacity:.5;pointer-events:none" @endif>
                                        <p class="ad-st-disc-h"><x-icon name="ticket" /> Diskon
                                            @if ($p->isDiscounted())<span class="ui-badge is-success">Sedang berjalan</span>
                                            @elseif ($p->isDiscountScheduled())<span class="ui-badge is-warning">Terjadwal</span>
                                            @elseif ($p->discount_type)<span class="ui-badge">Sudah berakhir</span>@endif
                                        </p>
                                        <div class="ad-st-disc-g">
                                            <div class="ui-field">
                                                <label for="dt-{{ $p->id }}">Jenis</label>
                                                <x-dropdown :name="'plans['.$p->id.'][discount_type]'" :id="'dt-'.$p->id" block all="Tanpa diskon" aria="Jenis diskon" :options="['percent' => 'Persen (%)', 'amount' => 'Potongan rupiah (Rp)']" :value="old($k.'discount_type', $p->discount_type)" />
                                                @error($k.'discount_type')<small class="text-red-700">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="ui-field">
                                                <label for="dv-{{ $p->id }}">Besar diskon</label>
                                                <input id="dv-{{ $p->id }}" type="number" min="1" inputmode="numeric" name="plans[{{ $p->id }}][discount_value]" value="{{ old($k.'discount_value', $p->discount_value) }}" class="ui-input" placeholder="mis. 30" data-disc-value>
                                                @error($k.'discount_value')<small class="text-red-700">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="ui-field">
                                                <label for="dl-{{ $p->id }}">Nama promo</label>
                                                <input id="dl-{{ $p->id }}" maxlength="60" name="plans[{{ $p->id }}][discount_label]" value="{{ old($k.'discount_label', $p->discount_label) }}" class="ui-input" placeholder="mis. Promo Akhir Tahun">
                                            </div>
                                            <div class="ui-field">
                                                <label for="ds-{{ $p->id }}">Mulai</label>
                                                <input id="ds-{{ $p->id }}" type="datetime-local" name="plans[{{ $p->id }}][discount_starts_at]" value="{{ old($k.'discount_starts_at', $fmtAt($p->discount_starts_at)) }}" class="ui-input">
                                                @error($k.'discount_starts_at')<small class="text-red-700">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="ui-field">
                                                <label for="de-{{ $p->id }}">Berakhir</label>
                                                <input id="de-{{ $p->id }}" type="datetime-local" name="plans[{{ $p->id }}][discount_ends_at]" value="{{ old($k.'discount_ends_at', $fmtAt($p->discount_ends_at)) }}" class="ui-input">
                                                @error($k.'discount_ends_at')<small class="text-red-700">{{ $message }}</small>@enderror
                                            </div>
                                        </div>
                                        <div class="ad-disc-sum" data-disc-preview aria-live="polite" hidden>
                                            <span>Harga normal <s data-disc-from></s></span>
                                            <b data-disc-to></b>
                                            <em data-disc-save></em>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="ac-set-foot">
                                <button class="btn-primary" type="submit">Simpan harga dan diskon</button>
                                <a href="{{ route('admin.plans.benefits') }}" class="btn-outline">Ubah keuntungan Premium</a>
                            </div>
                        </form>
                        <p class="ad-hint">Harga tiket ujian ada di bagian Fitur dan kuota. Gateway pembayaran dan kuncinya tetap lewat <code>.env</code>.</p>
                    </div>
                </section>

                {{-- ===== Fitur dan kuota ===== --}}
                <section id="fitur" class="pn-card ac-set-sec" aria-labelledby="h-fitur">
                    <header class="ac-set-h">
                        <span class="ac-set-ic" aria-hidden="true"><x-icon name="sparkle" /></span>
                        <div><h2 id="h-fitur">Fitur dan kuota</h2><p>Buka-tutup fitur, kuota AI, harga tiket, pengumuman, dan mode pemeliharaan. Berlaku langsung tanpa deploy.</p></div>
                    </header>
                    <form method="POST" action="{{ route('admin.settings.features') }}" class="ac-set-form">
                        @csrf @method('PUT')
                        <h3 class="ac-set-sub">Fitur</h3>
                        <div style="display:grid;gap:.5rem">
                            <label class="au-remember"><input type="checkbox" name="learning_open" value="1" @checked(config('platform.learning_open'))> Menu Belajar dibuka (mati = tampil "Segera Hadir")</label>
                            <label class="au-remember"><input type="checkbox" name="registration_open" value="1" @checked($site->registrationOpen())> Pendaftaran akun baru dibuka</label>
                        </div>

                        <h3 class="ac-set-sub">Kuota harian</h3>
                        <div class="qt" role="group" aria-label="Kuota harian per fitur">
                            <div class="qt-row qt-head" aria-hidden="true"><span>Fitur</span><span class="qt-free">Gratis</span><span class="qt-prem">Premium</span></div>
                            @foreach ([['chatbot', 'Asisten AI', 'pesan'], ['interview', 'Wawancara AI', 'sesi']] as [$k, $lbl, $unit])
                                <div class="qt-row">
                                    <span class="qt-name">{{ $lbl }} <small>{{ $unit }}/hari</small></span>
                                    <label class="qt-f qt-free"><span class="qt-fl">Gratis</span><input id="{{ $k }}_free_daily" type="number" min="0" name="{{ $k }}_free_daily" value="{{ old($k.'_free_daily', config($k === 'chatbot' ? 'platform.chatbot.free_daily' : "platform.ai_tools.{$k}.free_daily")) }}" class="ui-input" aria-label="{{ $lbl }}: kuota Gratis" required></label>
                                    <label class="qt-f qt-prem"><span class="qt-fl">Premium</span><input id="{{ $k }}_premium_daily" type="number" min="0" name="{{ $k }}_premium_daily" value="{{ old($k.'_premium_daily', config($k === 'chatbot' ? 'platform.chatbot.premium_daily' : "platform.ai_tools.{$k}.premium_daily")) }}" class="ui-input" aria-label="{{ $lbl }}: kuota Premium" required></label>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="ac-set-sub">Simulasi</h3>
                        <div class="qt-grid">
                            <div class="ui-field is-wide"><label for="exam_pass_price">Harga tiket ujian penuh (Rp)</label><input id="exam_pass_price" type="number" min="0" name="exam_pass_price" value="{{ old('exam_pass_price', config('platform.exam.pass_price')) }}" class="ui-input" required></div>
                            <div class="ui-field"><label for="exam_pass_valid_days">Masa berlaku tiket (hari)</label><input id="exam_pass_valid_days" type="number" min="0" name="exam_pass_valid_days" value="{{ old('exam_pass_valid_days', config('platform.exam.pass_valid_days')) }}" class="ui-input" placeholder="kosong = selamanya"></div>
                            <div class="ui-field"><label for="exam_trial_questions">Soal coba per segmen</label><input id="exam_trial_questions" type="number" min="1" max="50" name="exam_trial_questions" value="{{ old('exam_trial_questions', config('platform.exam.trial_questions_per_segment')) }}" class="ui-input" required></div>
                        </div>

                        <h3 class="ac-set-sub">Banner promo</h3>
                        @php
                            $pbn = $site->promoBanner();
                            $autoP = app(\App\Services\Billing\Promo::class)->current();
                            $pbOn = (bool) (session()->hasOldInput() ? old('promo_enabled') : $pbn['enabled']);
                            $pbTone = old('promo_tone', $pbn['tone']);
                            $pbFb = [
                                'eyebrow' => 'Penawaran terbaik', 'headline' => $autoP['label'] ?? 'Diskon spesial',
                                'prefix' => ($autoP['multiple'] ?? false) ? 'Hingga' : '', 'number' => $autoP ? $autoP['percent'].'%' : '50%',
                                'suffix' => 'OFF', 'cta' => 'Ambil diskon',
                            ];
                            $pbVal = fn (string $k) => (string) old('promo_'.$k, $pbn[$k]);
                            $pbShow = fn (string $k) => ($pbOn && $pbVal($k) !== '') ? $pbVal($k) : $pbFb[$k];
                            // warna: [nama, dasar, warna 1, warna 2] (sama dengan warna banner di situs)
                            $pbTones = [
                                'sunset' => ['Sunset', '#1b1038', '#ff9f1c', '#ff3d77'],
                                'violet' => ['Violet', '#0d1236', '#8b5cf6', '#ec4899'],
                                'emerald' => ['Emerald', '#04231f', '#14b8a6', '#84cc16'],
                                'ocean' => ['Ocean', '#06182f', '#38bdf8', '#6366f1'],
                                'gold' => ['Gold', '#1c1206', '#fbbf24', '#f97316'],
                                'crimson' => ['Crimson', '#2b0711', '#f87171', '#be123c'],
                            ];
                        @endphp
                        <div class="pv @unless ($pbOn) is-auto @endunless" data-pvroot
                             data-fb-eyebrow="{{ $pbFb['eyebrow'] }}" data-fb-headline="{{ $pbFb['headline'] }}" data-fb-prefix="{{ $pbFb['prefix'] }}"
                             data-fb-number="{{ $pbFb['number'] }}" data-fb-suffix="{{ $pbFb['suffix'] }}" data-fb-cta="{{ $pbFb['cta'] }}" data-has-auto="{{ $autoP ? 1 : 0 }}">
                            <div class="pv-top">
                                <label class="au-remember"><input type="checkbox" name="promo_enabled" value="1" @checked($pbOn)> Isi banner sendiri</label>
                                <span class="pv-state" data-pv-state></span>
                            </div>

                            <div class="pv-stage">
                                @include('partials.promo-bar', ['inset' => true, 'promoPreview' => [
                                    'tone' => $pbOn ? $pbTone : 'sunset', 'key' => 'preview', 'url' => '#',
                                    'eyebrow' => $pbShow('eyebrow'), 'headline' => $pbShow('headline'), 'prefix' => $pbShow('prefix'),
                                    'number' => $pbShow('number'), 'suffix' => $pbShow('suffix'), 'cta' => $pbShow('cta'), 'ends_at' => null,
                                ]])
                                <p class="pv-cap">Pratinjau langsung, ikut berubah saat kamu mengetik. Di layar HP banner lebih ringkas.</p>
                            </div>

                            <div class="pv-fields">
                                <fieldset class="pv-tones">
                                    <legend>Warna</legend>
                                    @foreach ($pbTones as $key => [$name, $base, $c1, $c2])
                                        <label class="pv-tone">
                                            <input type="radio" name="promo_tone" value="{{ $key }}" @checked($pbTone === $key)>
                                            <span class="pv-sw" style="--s0: {{ $base }}; --s1: {{ $c1 }}; --s2: {{ $c2 }}" aria-hidden="true"></span>
                                            {{ $name }}
                                        </label>
                                    @endforeach
                                </fieldset>

                                <div>
                                    <p class="pv-grp">Teks</p>
                                    <div class="pv-grid">
                                        <div class="ui-field is-wide"><label for="promo_headline">Teks utama</label><input id="promo_headline" name="promo_headline" maxlength="40" value="{{ old('promo_headline', $pbn['headline']) }}" class="ui-input" placeholder="{{ $pbFb['headline'] }}"></div>
                                        <div class="ui-field"><label for="promo_cta">Label tombol</label><input id="promo_cta" name="promo_cta" maxlength="24" value="{{ old('promo_cta', $pbn['cta']) }}" class="ui-input" placeholder="{{ $pbFb['cta'] }}"></div>
                                        <div class="ui-field is-full"><label for="promo_eyebrow">Teks kecil di atas judul</label><input id="promo_eyebrow" name="promo_eyebrow" maxlength="30" value="{{ old('promo_eyebrow', $pbn['eyebrow']) }}" class="ui-input" placeholder="{{ $pbFb['eyebrow'] }}"></div>
                                    </div>
                                </div>

                                <div>
                                    <p class="pv-grp">Angka besar</p>
                                    <div class="pv-grid">
                                        <div class="ui-field"><label for="promo_prefix">Awalan</label><input id="promo_prefix" name="promo_prefix" maxlength="12" value="{{ old('promo_prefix', $pbn['prefix']) }}" class="ui-input" placeholder="Hingga"></div>
                                        <div class="ui-field"><label for="promo_number">Angka</label><input id="promo_number" name="promo_number" maxlength="12" value="{{ old('promo_number', $pbn['number']) }}" class="ui-input" placeholder="75%"></div>
                                        <div class="ui-field"><label for="promo_suffix">Akhiran</label><input id="promo_suffix" name="promo_suffix" maxlength="12" value="{{ old('promo_suffix', $pbn['suffix']) }}" class="ui-input" placeholder="OFF"></div>
                                    </div>
                                </div>

                                <div>
                                    <p class="pv-grp">Tujuan dan waktu</p>
                                    <div class="pv-grid">
                                        <div class="ui-field is-wide"><label for="promo_url">Tautan saat diklik</label><input id="promo_url" type="url" name="promo_url" value="{{ old('promo_url', $pbn['url']) }}" class="ui-input" placeholder="kosong = halaman Harga"></div>
                                        <div class="ui-field"><label for="promo_ends_at">Berakhir pada</label><input id="promo_ends_at" type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at', $pbn['ends_at'] ? \Illuminate\Support\Carbon::parse($pbn['ends_at'])->timezone(config('platform.promo.timezone', 'Asia/Jakarta'))->format('Y-m-d\TH:i') : '') }}" class="ui-input"></div>
                                    </div>
                                </div>

                                <p class="pv-note">Kolom yang dikosongkan memakai isi otomatis dari diskon paket. Tanpa waktu berakhir, banner tampil sampai kotak "Isi banner sendiri" dimatikan. Pengunjung tidak bisa menutupnya.</p>
                            </div>
                        </div>
                        <script>(function () {
                            var root = document.querySelector('[data-pvroot]'); if (!root) return;
                            var q = function (s) { return root.querySelector(s); };
                            var bar = q('.pb-bar'), on = q('[name=promo_enabled]'), state = q('[data-pv-state]'), fb = root.dataset;
                            var val = function (n) { var e = q('[name=promo_' + n + ']'); return e ? e.value.trim() : ''; };
                            var pick = function (n) { return (on.checked && val(n)) || fb['fb' + n.charAt(0).toUpperCase() + n.slice(1)] || ''; };
                            var put = function (k, t) { var e = q('[data-pv=' + k + ']'); if (e) e.textContent = t; };
                            var hide = function (k, h) { var e = q('[data-pv=' + k + ']'); if (e) e.hidden = h; };
                            function left() {
                                var v = on.checked ? val('ends_at') : '', ms = v ? new Date(v).getTime() - Date.now() : 0;
                                if (!(ms > 0)) { hide('left', true); return; }
                                var m = Math.floor(ms / 60000), d = Math.floor(m / 1440), h = Math.floor(m % 1440 / 60);
                                put('left-t', d > 0 ? d + ' hari ' + h + ' jam' : (h > 0 ? h + ' jam ' + (m % 60) + ' menit' : (m % 60) + ' menit'));
                                hide('left', false);
                            }
                            function render() {
                                var manual = on.checked, tone = q('[name=promo_tone]:checked');
                                root.classList.toggle('is-auto', !manual);
                                bar.dataset.tone = manual && tone ? tone.value : 'sunset';
                                ['eyebrow', 'headline', 'cta', 'prefix', 'number', 'suffix'].forEach(function (k) { put(k, pick(k)); });
                                hide('prefix', !pick('prefix')); hide('suffix', !pick('suffix')); hide('off', !pick('number'));
                                left();
                                state.classList.toggle('is-manual', manual);
                                state.textContent = manual ? 'Isi sendiri: ini yang tampil di situs'
                                    : (fb.hasAuto === '1' ? 'Otomatis dari diskon paket yang berjalan' : 'Otomatis: belum ada diskon berjalan, banner tidak tampil');
                            }
                            root.addEventListener('input', render); root.addEventListener('change', render); render();
                        })();</script>

                        <h3 class="ac-set-sub">Pengumuman</h3>
                        <label class="au-remember"><input type="checkbox" name="announcement_enabled" value="1" @checked($ann['enabled'])> Tampilkan spanduk pengumuman di atas halaman</label>
                        <div class="ui-form-grid">
                            <div class="ui-field" style="grid-column:1/-1"><label for="announcement_text">Teks pengumuman</label><input id="announcement_text" name="announcement_text" maxlength="300" value="{{ old('announcement_text', $ann['text']) }}" class="ui-input" placeholder="mis. Simulasi CPNS 2026 sudah diperbarui"></div>
                            <div class="ui-field"><label for="announcement_tone">Warna</label><x-dropdown name="announcement_tone" id="announcement_tone" block aria="Warna pengumuman" :options="['info' => 'Info (biru)', 'warning' => 'Peringatan (kuning)', 'success' => 'Kabar baik (hijau)']" :value="$ann['tone']" /></div>
                            <div class="ui-field"><label for="announcement_url_label">Teks tautan (opsional)</label><input id="announcement_url_label" name="announcement_url_label" maxlength="40" value="{{ old('announcement_url_label', $ann['url_label']) }}" class="ui-input"></div>
                            <div class="ui-field" style="grid-column:1/-1"><label for="announcement_url">Alamat tautan (opsional)</label><input id="announcement_url" type="url" name="announcement_url" value="{{ old('announcement_url', $ann['url']) }}" class="ui-input" placeholder="https://"></div>
                        </div>

                        <div class="pv-stage ann-pv" data-annpv>
                            <div class="sx-banner is-{{ $ann['tone'] }}" data-ap-bar>
                                <span data-ap-text></span>
                                <a href="#" data-ap-link tabindex="-1" onclick="return false"></a>
                            </div>
                            <p class="pv-cap" data-ap-cap></p>
                        </div>

                        <h3 class="ac-set-sub">Mode pemeliharaan</h3>
                        <label class="au-remember"><input type="checkbox" name="maintenance_enabled" value="1" @checked($mnt['enabled'])> Aktifkan: pengunjung biasa melihat halaman pemeliharaan, admin dan staf tetap bisa bekerja</label>
                        <div class="ui-field"><label for="maintenance_message">Pesan untuk pengunjung</label><input id="maintenance_message" name="maintenance_message" maxlength="300" value="{{ old('maintenance_message', $mnt['message']) }}" class="ui-input"></div>

                        <p><a href="{{ route('admin.maintenance.preview') }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Lihat halaman pemeliharaan</a></p>

                        <div class="pv-stage mt-pv" data-mtpv>
                            <div class="mt-pv-card">
                                <b class="mt-pv-h">Sedang pemeliharaan</b>
                                <p class="mt-pv-m" data-mp-msg></p>
                                <small class="mt-pv-a">Admin atau staf? <u>Masuk</u></small>
                            </div>
                            <p class="pv-cap" data-mp-cap></p>
                        </div>

                        <script>(function () {
                            var form = document.currentScript.closest('form'); if (!form) return;
                            var q = function (s) { return form.querySelector(s); };
                            var f = function (n) { return q('[name=' + n + ']'); };
                            var v = function (n) { var e = f(n); return e ? e.value.trim() : ''; };
                            var on = function (n) { var e = f(n); return !!(e && e.checked); };
                            function ann() {
                                var root = q('[data-annpv]'); if (!root) return;
                                var bar = root.querySelector('[data-ap-bar]'), link = root.querySelector('[data-ap-link]'), text = v('announcement_text'), url = v('announcement_url');
                                var tone = v('announcement_tone') || 'info';
                                bar.className = 'sx-banner is-' + tone;
                                root.querySelector('[data-ap-text]').textContent = text || 'Teks pengumuman akan tampil di sini.';
                                root.classList.toggle('is-ph', !text);
                                root.classList.toggle('is-off', !on('announcement_enabled'));
                                link.hidden = !url; link.textContent = v('announcement_url_label') || 'Selengkapnya';
                                root.querySelector('[data-ap-cap]').textContent = on('announcement_enabled')
                                    ? 'Pratinjau langsung: begini spanduknya tampil di atas halaman situs.'
                                    : 'Pratinjau langsung. Spanduk belum tampil di situs karena kotak di atas belum dicentang.';
                            }
                            function mnt() {
                                var root = q('[data-mtpv]'); if (!root) return;
                                root.querySelector('[data-mp-msg]').textContent = v('maintenance_message') || 'Pesan untuk pengunjung akan tampil di sini.';
                                root.classList.toggle('is-off', !on('maintenance_enabled'));
                                root.querySelector('[data-mp-cap]').textContent = on('maintenance_enabled')
                                    ? 'Pratinjau langsung: ini yang dilihat pengunjung biasa. Admin dan staf tetap bisa bekerja.'
                                    : 'Pratinjau langsung. Mode pemeliharaan belum aktif, pengunjung masih melihat situs seperti biasa.';
                            }
                            function all() { ann(); mnt(); }
                            form.addEventListener('input', all); form.addEventListener('change', all);
                            form.addEventListener('click', function () { setTimeout(all, 0); });
                            all();
                        })();</script>

                        <div class="ac-set-foot"><button class="btn-primary" type="submit">Simpan pengaturan</button></div>
                    </form>
                </section>

                {{-- ===== Platform ===== --}}
                <section id="platform" class="pn-card ac-set-sec" aria-labelledby="h-platform">
                    <header class="ac-set-h">
                        <span class="ac-set-ic" aria-hidden="true"><x-icon name="layers" /></span>
                        <div><h2 id="h-platform">Platform</h2><p>Fitur yang dibuka, kuota, dan sumber data eksternal.</p></div>
                    </header>

                    @foreach ($platformRows as $group => $rows)
                        <div class="ad-st-group">
                            <h3 class="ad-st-gh">{{ $group }}</h3>
                            <div class="ad-st-tiles">
                                @foreach ($rows as [$k, $v, $t, $ic])
                                    @include('admin.partials.setting-tile', ['label' => $k, 'value' => $v, 'tone' => $t, 'icon' => $ic, 'tones' => $toneClass])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p class="ad-hint">Diatur lewat <code>.env</code> atau <code>config/platform.php</code>; perubahan berlaku setelah deploy ulang.</p>
                </section>

                {{-- ===== Info sistem ===== --}}
                <section id="sistem" class="pn-card ac-set-sec" aria-labelledby="h-sistem">
                    <header class="ac-set-h">
                        <span class="ac-set-ic" aria-hidden="true"><x-icon name="sliders" /></span>
                        <div><h2 id="h-sistem">Info sistem</h2><p>Lingkungan tempat aplikasi berjalan.</p></div>
                    </header>

                    <div class="ad-st-tiles">
                        @foreach ($systemRows as [$k, $v, $t, $ic])
                            @include('admin.partials.setting-tile', ['label' => $k, 'value' => $v, 'tone' => $t, 'icon' => $ic, 'tones' => $toneClass])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
    <script>(function (s) { var r = s.previousElementSibling, e = null; try { e = document.getElementById(decodeURIComponent(location.hash.slice(1))); } catch (x) {} if (!e || !e.classList.contains('ac-set-sec')) e = r.querySelector('.ac-set-sec'); r.classList.add('is-tabs'); e.classList.add('is-on'); })(document.currentScript);</script>
@endsection
