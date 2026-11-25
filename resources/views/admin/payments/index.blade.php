@extends('layouts.admin')

@section('title', 'Log pembayaran')
@section('heading', 'Log pembayaran')
@section('subheading', 'Notifikasi mentah dari gateway pembayaran. Berguna bila sebuah pesanan tidak otomatis lunas.')
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Pesanan</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <form method="GET" class="ui-table-bar" role="search">
            <x-table-search name="cari" placeholder="Cari nomor invoice atau jenis event" :filters="['cari', 'gateway', 'status']">
                <x-dropdown name="gateway" aria="Gateway" all="Semua gateway" :options="$gateways" :value="request('gateway')" submit />
                <x-dropdown name="status" aria="Status" all="Semua status" :options="['diproses' => 'Diproses', 'gagal' => 'Belum diproses']" :value="request('status')" submit />
            </x-table-search>
        </form>
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th>Waktu</th><th>Gateway</th><th>Event</th><th>Pesanan</th><th>Ringkasan</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($events as $e)
                        @php
                            $p = (array) $e->payload;
                            $sum = collect(['transaction_status', 'payment_type', 'gross_amount', 'fraud_status', 'status_code'])->filter(fn ($k) => isset($p[$k]))->map(fn ($k) => $k.': '.$p[$k])->implode(' | ');
                        @endphp
                        <tr>
                            <td class="is-nowrap text-graphite-500">{{ $e->created_at->translatedFormat('d M Y H:i:s') }}</td>
                            <td><span class="ui-badge">{{ $e->gateway }}</span></td>
                            <td>{{ $e->event_type ?? '-' }}</td>
                            <td>@if ($e->order)<a href="{{ route('admin.orders.show', $e->order) }}"><b>{{ $e->order->invoice_no }}</b></a>@else - @endif</td>
                            <td class="text-graphite-500" style="max-width:24rem;overflow-wrap:anywhere">{{ $sum ?: '-' }}</td>
                            <td><span @class(['ui-badge has-dot', 'is-success' => $e->processed_at, 'is-warning' => ! $e->processed_at])>{{ $e->processed_at ? 'Diproses' : 'Belum diproses' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ui-table-empty">Belum ada notifikasi pembayaran yang tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$events" label="event" />
    </div>
@endsection
