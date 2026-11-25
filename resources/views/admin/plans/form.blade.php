@extends('layouts.admin')

@section('title', 'Ubah paket')
@section('heading', 'Ubah paket '.$plan->name)
@section('subheading', 'Harga dan diskon baru berlaku untuk pesanan berikutnya; langganan yang sudah berjalan tidak berubah.')
@section('actions')
    <a href="{{ route('admin.settings') }}#pembayaran" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="pn-card ad-block ad-form" autocomplete="off">
        @csrf @method('PUT')
        @php $fmtAt = fn ($d) => $d?->copy()->timezone($tz)->format('Y-m-d\TH:i'); @endphp
        <div class="ui-form-grid" data-disc-form>
            <div class="ui-field"><label for="name">Nama paket</label><input id="name" name="name" value="{{ old('name', $plan->name) }}" class="ui-input" required maxlength="80"></div>
            <div class="ui-field"><label for="price">Harga normal (rupiah)</label><input id="price" name="price" type="number" min="0" value="{{ old('price', $plan->price) }}" class="ui-input" required data-disc-price></div>
            <div class="ui-field"><label for="interval-btn">Masa berlaku</label><x-dropdown name="interval" id="interval-btn" block aria="Masa berlaku" :options="$intervals" :value="old('interval', $plan->interval)" /></div>
            <div class="ui-field"><label>&nbsp;</label><label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))><i></i> Paket aktif dan bisa dibeli</label></div>
            @if ($plan->code !== \App\Models\Plan::QUARTER_CODE && in_array($plan->interval, ['half', 'year'], true))
                <div class="ui-field is-wide"><label>&nbsp;</label><label class="ui-check"><input type="checkbox" name="follow_quarter" value="1" @checked(old('follow_quarter', $plan->follow_quarter))><i></i> Ikuti harga 3 bulan (otomatis {{ $plan->interval === 'year' ? 4 : 2 }}x harga dan diskon paket Premium 3 Bulan)</label></div>
            @endif

            {{-- Diskon berjangka: harga di halaman Harga, Langganan, dan tagihan mengikuti otomatis selama rentang waktunya. --}}
            <fieldset class="ad-disc is-wide">
                <legend><x-icon name="ticket" /> Diskon berjangka</legend>
                <p class="ad-hint">Pilih jenis diskon untuk mengaktifkan. Selama rentang waktunya, harga coret dan harga baru tampil di halaman Harga, dan spanduk promo muncul di seluruh situs. Kosongkan jenis diskon untuk menghapusnya.</p>
                <div class="ui-form-grid">
                    <div class="ui-field">
                        <label for="discount_type-btn">Jenis diskon</label>
                        <x-dropdown name="discount_type" id="discount_type-btn" block all="Tanpa diskon" aria="Jenis diskon" :options="$discountTypes" :value="old('discount_type', $plan->discount_type)" data-disc-type />
                        @error('discount_type')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                    <div class="ui-field">
                        <label for="discount_value">Besar diskon</label>
                        <input id="discount_value" name="discount_value" type="number" min="1" inputmode="numeric" value="{{ old('discount_value', $plan->discount_value) }}" class="ui-input" placeholder="mis. 30" data-disc-value>
                        @error('discount_value')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                    <div class="ui-field is-wide">
                        <label for="discount_label">Nama promo (opsional)</label>
                        <input id="discount_label" name="discount_label" maxlength="60" value="{{ old('discount_label', $plan->discount_label) }}" class="ui-input" placeholder="mis. Promo Akhir Tahun">
                        <small>Tampil di spanduk dan kartu paket. Kosong = &ldquo;Diskon Premium&rdquo;.</small>
                        @error('discount_label')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                    <div class="ui-field">
                        <label for="discount_starts_at">Mulai</label>
                        <input id="discount_starts_at" name="discount_starts_at" type="datetime-local" value="{{ old('discount_starts_at', $fmtAt($plan->discount_starts_at)) }}" class="ui-input">
                        <small>Kosong = langsung berlaku. Zona waktu {{ $tz }}.</small>
                        @error('discount_starts_at')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                    <div class="ui-field">
                        <label for="discount_ends_at">Berakhir</label>
                        <input id="discount_ends_at" name="discount_ends_at" type="datetime-local" value="{{ old('discount_ends_at', $fmtAt($plan->discount_ends_at)) }}" class="ui-input">
                        <small>Kosong = tanpa batas waktu.</small>
                        @error('discount_ends_at')<small class="text-red-700">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="ad-disc-sum" data-disc-preview aria-live="polite" hidden>
                    <span>Harga normal <s data-disc-from></s></span>
                    <b data-disc-to></b>
                    <em data-disc-save></em>
                </div>
                @if ($plan->isDiscounted())
                    <p class="ad-disc-state is-on">Diskon sedang berjalan{{ $plan->discount_ends_at ? ' sampai '.$plan->discount_ends_at->copy()->timezone($tz)->translatedFormat('d M Y, H:i') : '' }}.</p>
                @elseif ($plan->isDiscountScheduled())
                    <p class="ad-disc-state">Terjadwal mulai {{ $plan->discount_starts_at->copy()->timezone($tz)->translatedFormat('d M Y, H:i') }}.</p>
                @elseif ($plan->discount_type)
                    <p class="ad-disc-state is-off">Diskon sudah berakhir. Ubah tanggal untuk menyalakannya lagi.</p>
                @endif
            </fieldset>

            <div class="ui-field" style="grid-column:1/-1"><label for="features">Fitur yang ditampilkan</label><textarea id="features" name="features" rows="6" class="ui-input" placeholder="satu fitur per baris">{{ old('features', collect($plan->features)->implode("\n")) }}</textarea></div>
        </div>
        <div class="ad-form-actions">
            <button type="submit" class="btn-primary">Simpan paket</button>
            <a href="{{ route('admin.settings') }}#pembayaran" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
