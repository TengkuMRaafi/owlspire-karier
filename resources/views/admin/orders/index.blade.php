@extends('layouts.admin')

@section('title', 'Pesanan')
@section('heading', 'Pesanan')
@section('subheading', 'Verifikasi pembayaran QRIS, lalu tiket pembeli aktif otomatis.')

@section('content')
    @php
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $current = request('status');
        $badge = ['paid' => ['success', 'Lunas'], 'pending' => ['warning', 'Menunggu'], 'failed' => ['danger', 'Ditolak'], 'expired' => ['danger', 'Kedaluwarsa']];
    @endphp
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari tagihan atau pembeli" :filters="['cari', 'status']">
                <x-dropdown name="status" aria="Status" all="Semua status ({{ $counts->sum() }})" :options="['pending' => 'Menunggu ('.($counts['pending'] ?? 0).')', 'paid' => 'Lunas ('.($counts['paid'] ?? 0).')', 'failed' => 'Ditolak ('.($counts['failed'] ?? 0).')', 'expired' => 'Kedaluwarsa ('.($counts['expired'] ?? 0).')']" :value="$current" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Tagihan</th><th>Pembeli</th><th>Item</th><th class="is-num">Jumlah</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($orders as $o)
                        @php $claimed = ! empty($o->meta['claimed_at']); @endphp
                        <tr>
                            <td class="is-no">{{ $orders->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell is-mono"><b>{{ $o->invoice_no }}</b><small>{{ $o->created_at->translatedFormat('d M Y H:i') }}</small></span></td>
                            <td><span class="ui-cell"><b>{{ $o->user?->name ?? '-' }}</b><small>{{ $o->user?->email }}</small></span></td>
                            <td>{{ $o->meta['item_name'] ?? $o->item_type }}</td>
                            <td class="is-num"><b>{{ $rp($o->amount) }}</b></td>
                            <td>
                                <span class="ui-inline">
                                    <span class="ui-badge has-dot is-{{ $badge[$o->status][0] ?? '' }}">{{ $badge[$o->status][1] ?? $o->status }}</span>
                                    @if ($o->status === 'pending' && $claimed)<small class="text-amber-700" title="Pembeli sudah konfirmasi bayar">Sudah konfirmasi</small>@endif
                                    @if ($o->status === 'pending' && ! empty($o->meta['attempt_failed_at']))<small class="text-amber-700" title="QR sudah dipindai tetapi pembayaran belum berhasil; tagihan ditutup otomatis saat waktunya habis">Percobaan bayar gagal</small>@endif
                                </span>
                            </td>
                            <td class="is-aksi">
                                <x-row-actions :show="route('admin.orders.show', $o)" :label="$o->invoice_no">
                                    @if ($manual && $o->status === 'pending')
                                        <form method="POST" action="{{ route('admin.orders.paid', $o) }}" data-confirm="Pastikan dana {{ $rp($o->amount) }} sudah masuk. Tandai lunas?">@csrf<button class="ui-act is-ok" type="submit" title="Tandai lunas"><x-icon name="check" /><span>Lunas</span></button></form>
                                        <form method="POST" action="{{ route('admin.orders.reject', $o) }}" data-confirm="Tolak pesanan ini?">@csrf<button class="ui-act is-danger" type="submit" title="Tolak"><x-icon name="x" /><span>Tolak</span></button></form>
                                    @endif
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ui-table-empty">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$orders" label="pesanan" />
    </div>
@endsection
