@extends('layouts.admin')

@section('title', 'Pengguna')
@section('heading', 'Pengguna')
@section('subheading', $users->total().' akun terdaftar')
@section('actions')
    {{-- Hanya admin yang bisa membuat akun (area `users`); staf tidak melihat menu ini. --}}
    <x-export-button :href="route('admin.users.export', request()->query())" />
    <a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tambah pengguna</a>
@endsection

@section('content')
    @php $allRoles = collect(config('roles.roles'))->map(fn ($r) => $r['label'])->all(); @endphp

    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar is-one-row" role="search">
            <x-table-search name="cari" placeholder="Cari nama atau email" :filters="['cari', 'paket', 'status']">
                <x-dropdown name="paket" aria="Paket" all="Semua paket" :options="['premium' => 'Premium', 'gratis' => 'Gratis']" :value="request('paket')" submit />
                <x-dropdown name="status" aria="Status akun" all="Semua status" :options="['aktif' => 'Aktif', 'ditangguhkan' => 'Ditangguhkan']" :value="request('status')" submit />
            </x-table-search>
        </form>

        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Pengguna</th><th>Role</th><th>Paket</th><th>Status</th><th>Login terakhir</th><th class="is-num">Ujian</th><th class="is-num">Tiket</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td class="is-no">{{ $users->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $u->name }}{{ $u->id === auth()->id() ? ' (kamu)' : '' }}</b><small>{{ $u->email }} &middot; sejak {{ $u->created_at->translatedFormat('d M Y') }}</small></span></td>
                            <td>
                                @if ($u->isAdmin())
                                    <span class="ui-badge is-accent"><x-icon name="lock" class="size-3" /> Admin</span>
                                @elseif ($u->isStaff())
                                    <span class="ui-badge is-dark">Staf</span>
                                @else
                                    <span class="ui-badge">Pengguna</span>
                                @endif
                            </td>
                            <td class="is-nowrap">
                                @if ($u->isInternal())
                                    <span class="text-graphite-500">Tanpa paket</span>
                                @elseif ($premium->has($u->id))
                                    <span class="ui-inline">
                                        <span class="ui-badge is-premium">Premium</span>
                                        <small>{{ $premium[$u->id] ? 'sampai '.\Illuminate\Support\Carbon::parse($premium[$u->id])->translatedFormat('d M Y') : 'tanpa batas' }}</small>
                                    </span>
                                @else
                                    <span class="ui-badge">Gratis</span>
                                @endif
                            </td>
                            <td class="is-nowrap">
                                @if ($u->isSuspended())
                                    <span class="ui-badge is-warning" title="{{ $u->suspended_reason }}">Ditangguhkan</span>
                                @else
                                    <span class="ui-badge is-success">Aktif</span>
                                @endif
                                @unless ($u->hasVerifiedEmail())<span class="ui-badge" title="Email belum diverifikasi">Belum verifikasi</span>@endunless
                                @if ($u->hasTwoFactor())<span class="ui-badge is-dark" title="Verifikasi 2 langkah aktif">2FA</span>@endif
                            </td>
                            <td class="is-nowrap text-graphite-500">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Belum pernah' }}</td>
                            <td class="is-num">{{ $attempts[$u->id] ?? 0 }}</td>
                            <td class="is-num"><b>{{ $tickets[$u->id] ?? 0 }}</b></td>
                            <td class="is-aksi">
                                <x-row-actions :show="route('admin.users.show', $u)" :edit="route('admin.users.edit', $u)"
                                    :delete="$u->isAdmin() || $u->id === auth()->id() ? null : route('admin.users.destroy', $u)"
                                    :confirm="'Hapus akun '.$u->name.'? Riwayat ujian akan tetap ada tanpa nama.'" :label="$u->name" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="ui-table-empty">Tidak ada pengguna yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$users" label="pengguna" />
    </div>

@endsection
