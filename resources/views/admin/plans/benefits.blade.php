@extends('layouts.admin')

@section('title', 'Keuntungan Premium')
@section('heading', 'Keuntungan Premium')
@section('subheading', 'Teks di halaman Harga, tabel Gratis vs Premium, dan halaman Langganan pengguna.')
@section('actions')
    <a href="{{ route('admin.settings') }}#pembayaran" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.plans.benefits.update') }}" class="pn-card ad-block ad-form" autocomplete="off">
        @csrf @method('PUT')
        <div class="ui-form-grid">
            <div class="ui-field is-wide"><label for="features">Keuntungan Premium (kartu Premium di halaman Harga)</label><textarea id="features" name="features" rows="7" class="ui-input" placeholder="satu keuntungan per baris">{{ old('features', $features) }}</textarea></div>
            <div class="ui-field is-wide"><label for="free_features">Yang didapat paket Gratis (halaman Harga)</label><textarea id="free_features" name="free_features" rows="5" class="ui-input" placeholder="satu poin per baris">{{ old('free_features', $freeFeatures) }}</textarea></div>
            <div class="ui-field is-wide"><label for="access_free">Tabel: fitur Gratis</label><textarea id="access_free" name="access_free" rows="7" class="ui-input" placeholder="Judul | Penjelasan singkat">{{ old('access_free', $accessFree) }}</textarea></div>
            <div class="ui-field is-wide"><label for="access_premium">Tabel: fitur Premium</label><textarea id="access_premium" name="access_premium" rows="7" class="ui-input" placeholder="Judul | Penjelasan singkat">{{ old('access_premium', $accessPremium) }}</textarea></div>
        </div>
        <p class="ad-hint">Satu baris satu item. Untuk tabel, tulis <code>Judul | Penjelasan</code>; ikon dan tautan baris lama dipertahankan bila judulnya sama. Kosongkan semua untuk kembali ke teks bawaan.</p>
        <div class="ad-form-actions"><button type="submit" class="btn-primary">Simpan keuntungan</button><a href="{{ route('admin.settings') }}#pembayaran" class="btn-outline">Batal</a></div>
    </form>
@endsection
