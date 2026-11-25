@extends('layouts.dashboard')

@section('title', 'Langganan')
@section('heading', 'Langganan')
@section('subheading', 'Status paket, tiket, dan riwayat pembayaran.')

@section('content')
    @php
        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $badge = ['paid' => ['success', 'Lunas'], 'pending' => ['warning', 'Menunggu'], 'failed' => ['danger', 'Ditolak'], 'expired' => ['danger', 'Kedaluwarsa']];
    @endphp
    <div class="pn-stack">
        @include('account.partials.plan-banner', ['premium' => $premium, 'premiumUntil' => $premiumUntil, 'tickets' => $tickets, 'cta' => false])

        <section class="pn-card">
            <div class="pn-card-h"><div><h2>Tiket ujian</h2><p class="ad-hint">Tanpa langganan. Satu tiket untuk {{ (int) config('platform.exam.pass_attempts', 2) }}x ujian penuh di satu simulasi, seleksi resmi maupun simulasi karier. Setelah habis, simulasi terkunci lagi.</p></div></div>
            @if (! empty($ticketOptions))
                <form method="POST" action="{{ route('billing.buy') }}" class="ac-buy">
                    @csrf
                    <x-dropdown name="exam_type_id" :options="$ticketOptions" :value="old('exam_type_id')" aria="Pilih ujian" block />
                    <button type="submit" class="btn-dark py-2.5">Beli tiket</button>
                </form>
                @error('exam_type_id')
                    <p class="ad-hint" role="alert" style="color:#b91c1c">{{ $message }}</p>
                @enderror
            @endif
        </section>

        <section id="paket" class="pn-card">
            <div class="pn-card-h"><div><h2>{{ $premium ? 'Perpanjang Premium' : 'Naik ke Premium' }}</h2><p class="ad-hint">Dibayar sekali per periode lewat QRIS. Tidak diperpanjang otomatis.@if ($plans->contains(fn ($p) => $p->isDiscounted())) Harga di bawah sudah termasuk diskon yang sedang berjalan.@endif</p></div></div>
            <form method="POST" action="{{ route('billing.plan') }}" class="ad-block">
                @csrf
                <fieldset class="ac-plans">
                    <legend class="sr-only">Pilih paket Premium</legend>
                    @forelse ($plans as $plan)
                        <label class="ac-plan-opt">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" @checked((int) old('plan_id', $plans->first()?->id) === $plan->id) required>
                            <b>{{ $plan->name }}</b>
                            @if ($plan->isDiscounted())<span class="ac-plan-save">{{ $plan->discount_label ?: 'Diskon Premium' }}: hemat {{ $plan->discountPercent() }}%</span>@endif
                            <strong>@if ($plan->isDiscounted())<s class="pr-old">{{ $rp($plan->price) }}</s> @endif{{ $rp($plan->finalPrice()) }} <small>{{ $periods[$plan->interval] ?? '' }}</small></strong>
                        </label>
                    @empty
                        <p class="ad-hint">Paket belum disiapkan. Jalankan <code>php artisan db:seed</code>.</p>
                    @endforelse
                </fieldset>
                <div><button type="submit" class="btn-primary" @disabled($plans->isEmpty())>Bayar dengan QRIS</button></div>
            </form>
        </section>

        <section>
            <div class="pn-sec"><div><h2>Riwayat pesanan</h2></div></div>
            <div class="ui-table-wrap">
                <div class="ui-table-scroll">
                    <table class="ui-table">
                        <thead><tr><th class="is-no">No</th><th>Tagihan</th><th>Item</th><th class="is-num">Jumlah</th><th>Status</th><th class="is-aksi">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($orders as $o)
                                <tr>
                                    <td class="is-no">{{ $orders->firstItem() + $loop->index }}</td>
                                    <td><span class="ui-cell is-mono"><b>{{ $o->invoice_no }}</b><small>{{ $o->created_at->translatedFormat('d M Y H:i') }}</small></span></td>
                                    <td>{{ $o->meta['item_name'] ?? $o->item_type }}</td>
                                    <td class="is-num"><b>{{ $rp($o->amount) }}</b></td>
                                    <td><span class="ui-badge has-dot is-{{ $badge[$o->status][0] ?? '' }}">{{ $badge[$o->status][1] ?? $o->status }}</span></td>
                                    <td class="is-aksi"><x-row-actions :show="route('billing.invoice', $o)" :label="$o->invoice_no" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="ui-table-empty">Belum ada pesanan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-foot :paginator="$orders" label="pesanan" />
            </div>
        </section>
    </div>
@endsection
