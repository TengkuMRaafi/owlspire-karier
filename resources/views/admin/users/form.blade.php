@extends('layouts.admin')

@php $editing = $user->exists; $locked = $editing && ($user->isAdmin() || $user->id === auth()->id()); @endphp
@section('title', $editing ? 'Ubah pengguna' : 'Tambah pengguna')
@section('heading', $editing ? 'Ubah pengguna' : 'Tambah pengguna')
@section('subheading', $editing ? $user->email : 'Akun baru dibuat oleh admin. Berikan kata sandi sementaranya langsung ke orangnya.')
@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <div class="ad-uf">
    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="pn-card ad-block ad-form" autocomplete="off">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="ui-form-grid">
            <div class="ui-field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="ui-input" required maxlength="80" autocomplete="off"></div>
            <div class="ui-field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="ui-input" required autocomplete="off"></div>
            <div class="ui-field">
                <label for="role-btn">Role</label>
                @if ($locked)
                    <input class="ui-input" value="{{ $user->roleLabel() }}" disabled>
                    <small>{{ $user->isAdmin() ? 'Role admin terkunci.' : 'Kamu tidak bisa mengubah role akunmu sendiri.' }}</small>
                @else
                    <x-dropdown name="role" id="role-btn" block aria="Role" :options="$roles" :value="old('role', $user->role)" />
                    <small>Staf mengelola soal, karier, video belajar, dan hasil ujian.</small>
                @endif
            </div>
            <div class="ui-field">
                <label for="password">{{ $editing ? 'Kata sandi baru' : 'Kata sandi sementara' }}</label>
                <input id="password" type="text" name="password" class="ui-input" @required(! $editing) minlength="8" autocomplete="new-password" placeholder="{{ $editing ? 'Kosongkan bila tidak diganti' : '' }}">
                <small>Minimal 8 karakter, campuran huruf dan angka.</small>
                @unless ($locked)
                    <label class="au-remember" style="margin-top:.5rem"><input type="checkbox" name="force_change" value="1" @checked(old('force_change', true))> Wajib ganti sandi saat masuk berikutnya</label>
                @endunless
            </div>
        </div>

        <div class="ad-form-actions">
            <button type="submit" class="btn-primary">{{ $editing ? 'Simpan perubahan' : 'Buat akun' }}</button>
            <a href="{{ route('admin.users.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>

    <aside class="pn-card ad-ov" data-ov aria-label="Ringkasan akun">
        <div class="ad-ov-cover is-{{ $user->role ?: 'member' }}" data-ov-cover><span class="ad-ov-k">Ringkasan akun</span></div>
        <div class="ad-ov-body">
            <span class="ad-ov-av" data-ov-av aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name ?: '?', 0, 1)) }}</span>
            <div class="ad-ov-who">
                <b data-ov-name>{{ $user->name ?: 'Nama pengguna' }}</b>
                <small data-ov-email>{{ $user->email ?: 'email@contoh.com' }}</small>
            </div>
            <div class="ad-ov-tags">
                <span class="ui-badge {{ $user->isAdmin() ? 'is-accent' : ($user->isStaff() ? 'is-dark' : '') }}" data-ov-role>{{ $user->roleLabel() }}</span>
                <span class="ui-badge" data-ov-plan>Paket mengikuti langganan</span>
            </div>
            <p class="ad-ov-desc" data-ov-desc>{{ config('roles.roles.'.($user->role ?: 'member').'.desc') }}</p>

            <dl class="ad-ov-rows">
                <div class="ad-ov-row"><dt>Email</dt><dd data-ov-mail-s>Belum diisi</dd></div>
                <div class="ad-ov-row"><dt>Kata sandi</dt><dd><span data-ov-pass-t>Belum diisi</span><span class="ad-ov-meter" data-ov-meter data-s="0" aria-hidden="true"><i></i><i></i><i></i></span></dd></div>
            </dl>

            <details class="ad-ov-det">
                <summary class="ad-ov-h">
                    <span>Akses di panel</span>
                    <em data-ov-count></em>
                    <svg class="ad-ov-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <span class="ad-ov-bar" aria-hidden="true"><i data-ov-bar></i></span>
                <ul class="ad-ov-acc">
                    @foreach (array_keys(config('roles.areas')) as $area)
                        <li data-roles="{{ implode(',', app(\App\Services\Settings\SiteSettings::class)->rolesFor($area)) }}">
                            <span class="ac-yes" aria-hidden="true"><x-icon name="check" /></span>
                            <span class="ac-no" aria-hidden="true"><x-icon name="x" /></span>
                            {{ config("roles.area_labels.{$area}") }}
                        </li>
                    @endforeach
                </ul>
            </details>
            <p class="ad-ov-feat"><x-icon name="sparkle" class="size-4" /> <span data-ov-feat></span></p>
            @if ($editing)
                <p class="ad-hint">Terdaftar sejak {{ $user->created_at?->translatedFormat('d M Y') }}.</p>
            @else
                <p class="ad-hint">Kartu ini ikut berubah saat kamu mengisi form di sebelah kiri.</p>
            @endif
        </div>
    </aside>
    </div>

    <script>
        (function () {
            var ov = document.querySelector('[data-ov]'); if (!ov) return;
            var form = document.querySelector('.ad-uf form');
            var roles = @json(collect(config('roles.roles'))->map(fn ($r) => ['label' => $r['label'], 'desc' => $r['desc']]));
            var $ = function (q) { return ov.querySelector(q); };
            var name = form.querySelector('[name=name]'), email = form.querySelector('[name=email]');
            var pass = form.querySelector('[name=password]');
            var role = form.querySelector('[name=role]');
            var fixedRole = @json($locked ? $user->role : null);
            var editing = @json($editing);
            var lastInitial = '';
            function strength(v) {
                var s = 0; if (v.length >= 8) s++; if (v.length >= 12 || (/[a-z]/.test(v) && /[A-Z]/.test(v))) s++; if (/\d/.test(v) && /[^A-Za-z0-9]|[A-Z]/.test(v)) s++;
                return v.length < 8 ? Math.min(s, 1) : Math.max(s, 1);
            }
            function render() {
                var n = (name.value || '').trim(), e = (email.value || '').trim();
                $('[data-ov-name]').textContent = n || 'Nama pengguna';
                $('[data-ov-email]').textContent = e || 'email@contoh.com';
                var ini = (n ? n.charAt(0) : '?').toUpperCase(), av = $('[data-ov-av]');
                av.textContent = ini;
                if (ini !== lastInitial) { av.classList.add('is-pop'); setTimeout(function () { av.classList.remove('is-pop'); }, 220); lastInitial = ini; }
                var key = fixedRole || (role ? role.value : 'member'), r = roles[key] || roles.member;
                var rb = $('[data-ov-role]'); rb.textContent = r.label;
                rb.classList.toggle('is-accent', key === 'admin'); rb.classList.toggle('is-dark', key === 'staff');
                $('[data-ov-cover]').className = 'ad-ov-cover is-' + key;
                $('[data-ov-plan]').textContent = key === 'member' ? 'Paket mengikuti langganan' : 'Tanpa paket';
                $('[data-ov-desc]').textContent = r.desc;
                var on = 0, all = 0;
                ov.querySelectorAll('.ad-ov-acc li').forEach(function (li) { var y = li.dataset.roles.split(',').indexOf(key) > -1; li.classList.toggle('is-on', y); all++; if (y) on++; });
                $('[data-ov-count]').textContent = on + ' dari ' + all + ' area';
                $('[data-ov-bar]').style.width = (all ? Math.round(on / all * 100) : 0) + '%';
                $('[data-ov-feat]').textContent = key === 'member' ? 'Fitur pengguna sesuai paket Gratis atau Premium.' : 'Fitur pengguna: akses penuh.';
                var ms = $('[data-ov-mail-s]'), okMail = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(e);
                ms.textContent = !e ? 'Belum diisi' : (okMail ? 'Format valid' : 'Format belum benar');
                ms.className = !e ? '' : (okMail ? 'is-ok' : 'is-bad');
                var pv = pass ? pass.value : '', st = pv ? strength(pv) : 0;
                $('[data-ov-meter]').dataset.s = st;
                $('[data-ov-pass-t]').textContent = !pv ? (editing ? 'Tidak diganti' : 'Belum diisi') : ['', 'Lemah', 'Sedang', 'Kuat'][st];
            }
            [name, email, pass].forEach(function (el) { if (el) el.addEventListener('input', render); });
            if (role) role.addEventListener('change', render);
            render();
        })();
    </script>
@endsection
