@extends('layouts.admin')

@section('title', $user->name)
@section('heading', $user->name)
@section('subheading', 'Detail akun')
@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
    <a href="{{ route('admin.users.edit', $user) }}" class="btn-primary"><x-icon name="edit" class="size-4" /> Ubah</a>
@endsection

@section('content')
    @php
        $planText = $user->isInternal() ? 'Tanpa paket (akses penuh)'
            : ($premium ? 'Premium '.($premiumUntil ? 'sampai '.$premiumUntil->translatedFormat('d M Y') : '(tanpa batas)') : 'Gratis');
        $best = $attempts->max('total_score');
        $self = $user->id === auth()->id();
        $member = ! $user->isInternal();
        $aiLabels = ['interview' => 'Wawancara AI'];
    @endphp
    <div class="pn-stack">
        <x-detail-hero :title="$user->name" :sub="$user->email" :initial="\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1))">
            @if ($user->isAdmin())
                <span class="ui-badge is-accent"><x-icon name="lock" class="size-3" /> Admin</span>
            @elseif ($user->isStaff())
                <span class="ui-badge is-dark">Staf</span>
            @else
                <span class="ui-badge">Pengguna</span>
            @endif
            @unless ($user->isInternal())
                <span @class(['ui-badge', 'is-premium' => $premium])>{{ $premium ? 'Premium' : 'Gratis' }}</span>
            @endunless
            @if ($user->isSuspended())<span class="ui-badge is-warning">Ditangguhkan</span>@endif
            @unless ($user->hasVerifiedEmail())<span class="ui-badge">Email belum diverifikasi</span>@endunless
            @if ($twoFactor)<span class="ui-badge is-dark">2 langkah aktif</span>@endif
            <span class="ad-d-chip"><x-icon name="calendar" /> Bergabung {{ $user->created_at->translatedFormat('M Y') }}</span>
        </x-detail-hero>

        @if ($user->isSuspended())
            <div class="pn-card" style="border-color:rgba(217,119,6,.5)">
                <p class="ad-d-sub" style="margin:0"><b>Akun ditangguhkan</b> sejak {{ $user->suspended_at->translatedFormat('d M Y, H:i') }}{{ $user->suspended_reason ? ' dengan alasan: '.$user->suspended_reason : '' }}. Pemiliknya tidak bisa masuk sampai penangguhan dicabut.</p>
            </div>
        @endif

        <div class="ui-stats is-compact">
            <x-detail-stat icon="bars" label="Ujian selesai" :value="$attemptCount" hint="semua mode" />
            <x-detail-stat icon="ticket" label="Simulasi terbuka" :value="$tickets" hint="lewat tiket, jatah belum habis" :accent="$tickets > 0" />
            <x-detail-stat icon="cart" label="Pesanan" :value="$orderCount" hint="semua status" />
            <x-detail-stat icon="trophy" label="Skor terbaik" :value="$best ?? '-'" hint="dari 5 ujian terakhir" />
        </div>

        <div class="pn-split is-even">
            <div class="pn-card">
                <x-detail-head icon="user" title="Informasi akun" />
                <div class="ad-d-info">
                    <x-detail-item icon="user" label="Nama">{{ $user->name }}</x-detail-item>
                    <x-detail-item icon="file" label="Email">{{ $user->email }} {!! $user->hasVerifiedEmail() ? '<small>(terverifikasi)</small>' : '<small>(belum diverifikasi)</small>' !!}</x-detail-item>
                    <x-detail-item icon="shield" label="Role">{{ $user->roleLabel() }}</x-detail-item>
                    <x-detail-item icon="crown" label="Paket">{{ $planText }}</x-detail-item>
                    <x-detail-item icon="calendar" label="Bergabung">{{ $user->created_at->translatedFormat('d M Y, H:i') }}</x-detail-item>
                    <x-detail-item icon="clock" label="Login terakhir">{{ $user->last_login_at ? $user->last_login_at->translatedFormat('d M Y, H:i').($user->last_login_ip ? ' dari '.$user->last_login_ip : '') : 'Belum pernah masuk' }}</x-detail-item>
                </div>
            </div>

            <div class="pn-card">
                <x-detail-head icon="ticket" title="Beri tiket ujian" />
                <p class="ad-d-sub">Satu tiket memberi {{ (int) config('platform.exam.pass_attempts', 2) }}x ujian penuh untuk simulasi pilihan (sama seperti membeli). Jumlah = banyaknya tiket; memberi lagi menambah jatahnya.</p>
                <form method="POST" action="{{ route('admin.users.ticket', $user) }}" class="ui-form-row">
                    @csrf
                    <div class="ui-field" style="min-width:14rem"><label for="ticket-type-btn">Jenis ujian</label><x-dropdown name="exam_type_id" id="ticket-type-btn" block aria="Jenis ujian" :options="$types->pluck('name', 'id')->all()" /></div>
                    <div class="ui-field"><label for="qty">Jumlah tiket</label><input id="qty" type="number" name="quantity" value="1" min="1" max="50" class="ui-input" style="width:6rem"></div>
                    <button class="btn-dark" type="submit">Beri tiket</button>
                </form>
            </div>
        </div>

        <div @class(['pn-split', 'is-even' => $member, 'ad-act-split'])>
            @if ($member)
                <div class="pn-card">
                    <x-detail-head icon="crown" title="Premium" />
                    <div class="ad-act-now"><small>Saat ini</small><b>{{ $planText }}</b></div>
                    <p class="ad-d-sub">Untuk kompensasi, promo, atau pembayaran di luar sistem. Jika sudah Premium, masa berlakunya diperpanjang.</p>
                    <form method="POST" action="{{ route('admin.users.premium', $user) }}" class="ad-act-form">
                        @csrf
                        <input type="hidden" name="mode" value="grant">
                        <div class="ui-field"><label for="prem-dur-btn">Tambah masa berlaku</label>
                            <x-dropdown name="duration" id="prem-dur-btn" block aria="Durasi Premium" :options="['30' => '30 hari', '90' => '90 hari', '180' => '180 hari', '365' => '1 tahun', 'lifetime' => 'Tanpa batas waktu']" /></div>
                        <button class="btn-dark" type="submit">Beri Premium</button>
                    </form>
                    @if ($premium)
                        <form method="POST" action="{{ route('admin.users.premium', $user) }}" class="ad-act-more" data-confirm="Premium {{ $user->name }} akan dihentikan sekarang juga." data-confirm-title="Cabut Premium?" data-confirm-ok="Ya, cabut">
                            @csrf <input type="hidden" name="mode" value="revoke">
                            <button class="btn-outline" type="submit">Cabut Premium sekarang</button>
                        </form>
                    @endif
                </div>
            @endif

            <div class="pn-card">
                <x-detail-head icon="shield" title="Keamanan dan akses" />
                @if (! $user->isSuspended() && ! $user->isAdmin() && ! $self)
                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="ad-act-form" data-confirm="{{ $user->name }} langsung keluar dari semua perangkat dan tidak bisa masuk lagi." data-confirm-title="Tangguhkan akun?" data-confirm-ok="Ya, tangguhkan">
                        @csrf
                        <div class="ui-field"><label for="reason">Alasan penangguhan <span class="ad-act-opt">(opsional, hanya terlihat admin)</span></label><input id="reason" name="reason" class="ui-input" maxlength="255" placeholder="mis. Pelanggaran ketentuan"></div>
                        <button class="btn-outline" type="submit">Tangguhkan akun</button>
                    </form>
                @endif

                <div class="ad-act-grid">
                    <form method="POST" action="{{ route('admin.users.reset', $user) }}" data-confirm="Link atur ulang kata sandi dikirim ke {{ $user->email }}.">@csrf<button class="btn-outline" type="submit">Kirim link reset sandi</button></form>
                    @unless ($user->isAdmin())
                        <form method="POST" action="{{ route('admin.users.force', $user) }}">@csrf<button class="btn-outline" type="submit">{{ $user->must_change_password ? 'Cabut wajib ganti sandi' : 'Wajib ganti sandi' }}</button></form>
                    @endunless
                    @unless ($user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('admin.users.verify', $user) }}">@csrf<button class="btn-outline" type="submit">Tandai email terverifikasi</button></form>
                    @endunless
                    @if ($twoFactor && ! $self)
                        <form method="POST" action="{{ route('admin.users.2fa.off', $user) }}" data-confirm="Verifikasi 2 langkah {{ $user->name }} dimatikan. Lakukan ini hanya bila pemiliknya kehilangan akses ke autentikator." data-confirm-title="Matikan 2 langkah?" data-confirm-ok="Ya, matikan">@csrf<button class="btn-outline" type="submit">Matikan 2 langkah</button></form>
                    @endif
                    @if (! $user->isInternal() && ! $user->isSuspended() && ! $self)
                        <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">@csrf<button class="btn-outline" type="submit">Masuk sebagai pengguna</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.users.reset-usage', $user) }}" data-confirm="Pemakaian harian {{ $user->name }} (Asisten AI, Wawancara AI, Analisis CV) dikosongkan sehingga jatahnya penuh lagi." data-confirm-title="Reset pemakaian hari ini?" data-confirm-ok="Ya, reset">@csrf<button class="btn-outline" type="submit">Reset pemakaian hari ini</button></form>
                    @unless ($self)
                        <form method="POST" action="{{ route('admin.users.logout', $user) }}" data-confirm="{{ $user->name }} keluar dari semua perangkat.">@csrf<button class="btn-outline" type="submit">Keluarkan dari semua perangkat</button></form>
                    @endunless
                    @if ($user->isSuspended())
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="is-wide">@csrf<button class="btn-dark" type="submit">Cabut penangguhan</button></form>
                    @endif
                </div>
                @if ($user->must_change_password)<p class="ad-act-note">Pengguna ini akan diminta mengganti sandi saat berikutnya membuka akunnya.</p>@endif
                @if ($devices !== null)
                    <p class="ad-act-note">Perangkat aktif sekarang: <b>{{ $devices->count() }}</b>@if ($devices->isNotEmpty()) ({{ $devices->map(fn ($d) => $d['browser'].' di '.$d['os'])->unique()->take(3)->implode(', ') }})@endif</p>
                @endif
            </div>
        </div>

        <div class="pn-split is-even">
            <div class="pn-card">
                <x-detail-head icon="sparkle" title="Pemakaian hari ini" />
                <div class="ad-d-info">
                    <x-detail-item icon="sparkle" label="Pesan Asisten AI">{{ $usage['chat'] }}</x-detail-item>
                    @foreach ($aiLabels as $key => $label)
                        <x-detail-item icon="file" label="{{ $label }}">{{ $usage['ai'][$key] ?? 0 }}x</x-detail-item>
                    @endforeach
                    <x-detail-item icon="file" label="Analisis CV">{{ $usage['hasCv'] ? 'Ada'.($usage['cvScore'] !== null ? ', skor '.$usage['cvScore'].'/100' : '') : 'Belum ada' }}</x-detail-item>
                </div>
            </div>

            <div class="pn-card">
                <x-detail-head icon="cart" title="Pesanan terakhir" />
                @forelse ($orders as $o)
                    <p class="ad-d-sub" style="margin:.25rem 0"><a href="{{ route('admin.orders.show', $o) }}"><b>{{ $o->invoice_no }}</b></a> &middot; {{ $o->status }} &middot; {{ $o->created_at->translatedFormat('d M Y') }}</p>
                @empty
                    <p class="ad-d-sub">Belum ada pesanan.</p>
                @endforelse
            </div>
        </div>

        <div class="ui-table-wrap">
            <div class="ui-table-bar"><span class="ad-d-bar-t"><span class="ad-d-h-ic" aria-hidden="true"><x-icon name="history" /></span>Ujian terakhir</span><span class="ui-table-bar-end">5 terbaru</span></div>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Ujian</th><th>Mode</th><th class="is-num">Skor</th><th>Selesai</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($attempts as $a)
                            <tr>
                                <td class="is-no">{{ $loop->iteration }}</td>
                                <td><b class="text-graphite-800">{{ $a->type->name }}</b></td>
                                <td><span @class(['ui-badge', 'is-accent' => $a->mode === 'full'])>{{ $a->mode === 'full' ? 'Penuh' : 'Coba' }}</span></td>
                                <td class="is-num"><b>{{ $a->total_score }}</b></td>
                                <td class="is-nowrap text-graphite-500">{{ $a->finished_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="is-aksi"><x-row-actions :show="route('admin.attempts.show', $a)" :label="$a->type->name" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ui-table-empty">Belum ada ujian selesai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ui-table-wrap">
            <div class="ui-table-bar"><span class="ad-d-bar-t"><span class="ad-d-h-ic" aria-hidden="true"><x-icon name="shield" /></span>Aktivitas akun</span><span class="ui-table-bar-end"><a href="{{ route('admin.audit.index', ['cari' => $user->email]) }}">Lihat di Log aktivitas</a></span></div>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th>Waktu</th><th>Aktivitas</th><th>Oleh</th><th>IP</th></tr></thead>
                    <tbody>
                        @forelse ($logs as $l)
                            <tr>
                                <td class="is-nowrap text-graphite-500">{{ $l->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $l->label() }}</td>
                                <td>{{ $l->actor_name ?? 'Sistem' }}</td>
                                <td class="text-graphite-500">{{ $l->ip }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="ui-table-empty">Belum ada aktivitas tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
