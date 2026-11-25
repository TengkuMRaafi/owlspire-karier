@extends('layouts.admin')

@section('title', 'Detail pesanan')
@section('heading', 'Pesanan '.$order->invoice_no)
@section('subheading', 'Detail pesanan')
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    @php
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $badge = ['paid' => ['success', 'Lunas'], 'pending' => ['warning', 'Menunggu'], 'failed' => ['danger', 'Ditolak'], 'expired' => ['danger', 'Kedaluwarsa']];
        $claimedAt = $order->meta['claimed_at'] ?? null;
        $ended = in_array($order->status, ['failed', 'expired'], true);
        $itemName = $order->meta['item_name'] ?? $order->item_type;
        $buyer = $order->user;
    @endphp
    <div class="pn-stack">
        <x-detail-hero :title="$order->invoice_no" :sub="$itemName" icon="cart">
            <span class="ui-badge has-dot is-{{ $badge[$order->status][0] ?? '' }}">{{ $badge[$order->status][1] ?? $order->status }}</span>
            <span class="ad-d-chip"><x-icon name="calendar" /> {{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
            <x-slot:side>
                <small>Total tagihan</small>
                <span class="ad-d-fig">{{ $rp($order->amount) }}</span>
            </x-slot:side>
        </x-detail-hero>

        <div class="pn-card">
            <x-detail-head icon="clock" title="Alur pesanan" />
            <ol class="ad-d-steps">
                <li class="ad-d-step is-done">
                    <span class="ad-d-dot"><x-icon name="check" /></span>
                    <b>Pesanan dibuat</b><small>{{ $order->created_at->translatedFormat('d M Y, H:i') }}</small>
                </li>
                <li @class(['ad-d-step', 'is-done' => (bool) $claimedAt || $order->status === 'paid'])>
                    <span class="ad-d-dot"><x-icon :name="$claimedAt || $order->status === 'paid' ? 'check' : 'clock'" /></span>
                    <b>Pembeli konfirmasi</b><small>{{ $claimedAt ? \Illuminate\Support\Carbon::parse($claimedAt)->translatedFormat('d M Y, H:i') : 'Belum ada' }}</small>
                </li>
                <li @class(['ad-d-step', 'is-done' => $order->status === 'paid', 'is-bad' => $ended])>
                    <span class="ad-d-dot"><x-icon :name="$order->status === 'paid' ? 'check' : ($ended ? 'x' : 'clock')" /></span>
                    <b>{{ $ended ? ($badge[$order->status][1] ?? 'Selesai') : 'Lunas' }}</b>
                    <small>{{ $order->paid_at?->translatedFormat('d M Y, H:i') ?? ($ended ? 'Tidak dilanjutkan' : 'Menunggu verifikasi') }}</small>
                </li>
            </ol>
        </div>

        <div class="pn-split is-even">
            <div class="pn-card">
                <x-detail-head icon="file" title="Rincian pesanan" />
                <div class="ad-d-info">
                    <x-detail-item icon="file" label="No. tagihan">{{ $order->invoice_no }}</x-detail-item>
                    <x-detail-item icon="cart" label="Item">{{ $itemName }}</x-detail-item>
                    <x-detail-item icon="card" label="Jumlah">{{ $rp($order->amount) }}</x-detail-item>
                    <x-detail-item icon="check-circle" label="Konfirmasi pembeli">{{ $claimedAt ? 'Sudah' : 'Belum' }}</x-detail-item>
                    <x-detail-item icon="calendar" label="Dibuat">{{ $order->created_at->translatedFormat('d M Y H:i') }}</x-detail-item>
                    <x-detail-item icon="calendar" label="Dibayar">{{ $order->paid_at?->translatedFormat('d M Y H:i') ?? '-' }}</x-detail-item>
                </div>
            </div>

            <div class="pn-card">
                <x-detail-head icon="user" title="Pembeli" />
                @if ($buyer)
                    <div class="ad-d-who">
                        <span class="pn-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($buyer->name, 0, 1)) }}</span>
                        <div><b>{{ $buyer->name }}</b><small>{{ $buyer->email }}</small></div>
                    </div>
                @else
                    <p class="ad-hint">Akun pembeli sudah dihapus.</p>
                @endif
                @if ($manual && $order->status === 'pending')
                    <div class="ad-d-acts">
                        <form method="POST" action="{{ route('admin.orders.paid', $order) }}" data-confirm="Pastikan dana {{ $rp($order->amount) }} sudah masuk. Tandai lunas?">@csrf<button class="btn-primary btn-sm" type="submit">Tandai lunas</button></form>
                        <form method="POST" action="{{ route('admin.orders.reject', $order) }}" data-confirm="Tolak pesanan ini?">@csrf<button class="btn-outline btn-sm" type="submit">Tolak</button></form>
                    </div>
                @elseif ($order->status === 'paid')
                    <p class="ad-hint">Pesanan sudah lunas, status terkunci dan tidak perlu diubah lagi.</p>
                @elseif ($order->status === 'pending')
                    <p class="ad-hint">Status diperbarui otomatis oleh Midtrans setelah pembeli membayar.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
