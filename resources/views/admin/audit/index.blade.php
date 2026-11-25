@extends('layouts.admin')

@section('title', 'Log aktivitas')
@section('heading', 'Log aktivitas')
@section('subheading', 'Jejak tindakan penting: login admin, perubahan pengguna, Premium, pesanan, dan pengaturan.')

@php
    // Nama kolom yang lebih ramah untuk isi `meta`; kunci lain dipakai apa adanya (diubah jadi judul).
    $fieldNames = ['price' => 'Harga', 'amount' => 'Nominal', 'active' => 'Status', 'name' => 'Nama', 'role' => 'Role', 'email' => 'Email', 'status' => 'Status'];
    $label = fn ($k) => $fieldNames[$k] ?? \Illuminate\Support\Str::headline((string) $k);
    $fmt = function ($k, $v) {
        if (is_bool($v)) {
            return $k === 'active' ? ($v ? 'Aktif' : 'Nonaktif') : ($v ? 'Ya' : 'Tidak');
        }
        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        if ($v === null || $v === '') {
            return '–';
        }
        if (in_array($k, ['price', 'amount'], true) && is_numeric($v)) {
            return 'Rp '.number_format((float) $v, 0, ',', '.');
        }

        return (string) $v;
    };
@endphp

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar ad-lg-bar" role="search">
            <x-table-search name="cari" placeholder="Cari nama, email, atau IP" :filters="['cari', 'grup', 'tanggal']">
                <x-dropdown name="grup" aria="Kelompok" all="Semua kelompok" :options="$groups" :value="request('grup')" submit />
                <div class="ad-lg-date">
                    <label class="sr-only" for="tanggal">Tanggal</label><input id="tanggal" type="date" name="tanggal" value="{{ request('tanggal') }}" class="ui-input" aria-label="Tanggal">
                </div>
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table ad-lg">
                <thead><tr><th>Waktu</th><th>Aktivitas</th><th>Oleh</th><th>Target</th><th>Detail</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $l)
                        @php
                            $t = $l->created_at->copy()->timezone('Asia/Jakarta');
                            $name = $l->actor_name ?? 'Sistem';
                            $meta = collect($l->meta ?? [])->except('impersonator_id');
                            $before = is_array($meta->get('before')) ? $meta->get('before') : null;
                            $after = is_array($meta->get('after')) ? $meta->get('after') : null;
                            $rest = $meta->except(['before', 'after']);
                            $changes = [];
                            if ($before !== null || $after !== null) {
                                foreach (array_unique(array_merge(array_keys($before ?? []), array_keys($after ?? []))) as $k) {
                                    $b = $before[$k] ?? null;
                                    $a = $after[$k] ?? null;
                                    if (array_key_exists($k, $before ?? []) && array_key_exists($k, $after ?? []) && $b === $a) {
                                        continue;
                                    }
                                    $changes[$k] = [$b, $a, array_key_exists($k, $before ?? [])];
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="ad-lg-when"><b>{{ $t->translatedFormat('d M Y') }}</b><small>{{ $t->format('H:i:s') }} WIB</small></div>
                            </td>
                            <td><span class="ad-lg-act is-{{ $l->group() }}">{{ $l->label() }}</span></td>
                            <td>
                                <div class="ad-lg-who">
                                    <span class="ad-lg-av" aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                                    <span><b>{{ $name }}</b>@if (! empty($l->meta['impersonator_id']))<small>lewat “masuk sebagai”</small>@endif</span>
                                </div>
                            </td>
                            <td class="ad-lg-target">{{ $l->subject_label ?? '–' }}</td>
                            <td>
                                @if ($changes || $rest->isNotEmpty() || ($before !== null || $after !== null))
                                    <div class="ad-lg-detail">
                                        @foreach ($changes as $k => [$b, $a, $had])
                                            <span class="ad-lg-chip">
                                                <i>{{ $label($k) }}</i>
                                                @if ($had)<s>{{ $fmt($k, $b) }}</s><em aria-hidden="true">→</em>@endif
                                                <b>{{ $fmt($k, $a) }}</b>
                                            </span>
                                        @endforeach
                                        @if (! $changes && ($before !== null || $after !== null))
                                            <span class="ad-lg-chip is-muted">Tidak ada nilai yang berubah</span>
                                        @endif
                                        @foreach ($rest as $k => $v)
                                            <span class="ad-lg-chip"><i>{{ $label($k) }}</i><b>{{ $fmt($k, $v) }}</b></span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="ad-lg-dash">–</span>
                                @endif
                            </td>
                            <td class="ad-lg-ip">{{ $l->ip ?: '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ui-table-empty">Belum ada aktivitas yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$logs" label="catatan" />
    </div>
@endsection
