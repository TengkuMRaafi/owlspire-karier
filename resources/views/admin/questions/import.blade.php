@extends('layouts.admin')

@section('title', 'Impor soal')
@section('heading', 'Impor soal')
@section('subheading', 'Tambah banyak soal sekaligus dari berkas JSON atau CSV.')

@section('actions')
    <a href="{{ route('admin.questions.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
    <div class="pn-stack">
        <div class="pn-split is-even im-top">
            <form method="POST" action="{{ route('admin.questions.import.store') }}" enctype="multipart/form-data" class="pn-card ad-block im-card">
                @csrf
                <div>
                    <h2 class="im-h">Unggah berkas</h2>
                    <p class="im-sub">JSON atau CSV, boleh banyak berkas sekaligus, maks 10 MB per berkas.</p>
                </div>
                <div class="ui-field">
                    <label for="files" class="sr-only">Berkas soal</label>
                    <input id="files" type="file" name="files[]" accept=".json,.csv,.txt" multiple required class="ui-input">
                </div>
                <ul class="im-notes">
                    <li>Soal yang sama di segmen yang sama <strong>diperbarui</strong>, tidak digandakan.</li>
                    <li>Jenis ujian dan segmen harus sudah ada di menu <a href="{{ route('admin.setup.index') }}" class="ui-link">Jenis ujian</a>.</li>
                </ul>
                <div class="im-act"><button type="submit" class="btn-primary">Impor sekarang</button></div>
            </form>

            <div class="pn-card ad-block im-card">
                <div>
                    <h2 class="im-h">Format berkas</h2>
                    <p class="im-sub">Unduh templat, isi, lalu unggah di samping.</p>
                </div>
                <div class="im-btns">
                    <a href="{{ route('admin.questions.template', 'json') }}" class="btn-outline">Templat JSON</a>
                    <a href="{{ route('admin.questions.template', 'csv') }}" class="btn-outline">Templat CSV</a>
                </div>
                <div class="im-cols">
                    <p class="im-k">Kolom CSV</p>
                    <div class="im-chips">
                        @foreach (['exam_type', 'segment', 'question', 'option_a ... option_e', 'answer', 'explanation', 'is_trial'] as $c)<code>{{ $c }}</code>@endforeach
                    </div>
                    <p class="im-hint">is_trial diisi 1 atau 0. Kode jenis dan segmen huruf kecil, mis. <code>cpns</code> dan <code>twk</code>.</p>
                </div>
                <details class="im-more">
                    <summary>Format JSON yang dikenali</summary>
                    <ul>
                        <li>Ekspor bank soal CI: <code>exam_type</code> + <code>segments</code>.</li>
                        <li>Daftar soal: <code>examType</code>, <code>segmentId</code>, <code>q</code>, <code>opts</code>, <code>ans</code>.</li>
                        <li>Opsional: <code>topic</code>, <code>difficulty</code>.</li>
                    </ul>
                </details>
            </div>
        </div>

        <div class="pn-card ad-block" id="stok">
            <div>
                <h2 class="im-h">Stok soal per segmen</h2>
                <p class="im-sub">Ujian penuh mengambil soal acak dari stok sebanyak kolom &ldquo;Soal ujian penuh&rdquo;. Angka merah berarti stok masih kurang.</p>
            </div>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead><tr><th>Jenis ujian</th><th>Segmen</th><th>Stok</th><th>Soal gratis</th><th>Soal ujian penuh</th></tr></thead>
                    <tbody>
                        @foreach ($stock as $r)
                            <tr>
                                <td>{{ $r['type'] }}</td><td>{{ $r['segment'] }}</td>
                                <td><b @style(['color:#b91c1c' => $r['total'] < $r['full']])>{{ $r['total'] }}</b></td>
                                <td>{{ $r['trial'] }}</td><td>{{ $r['full'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-foot :paginator="$stock" label="segmen" />
        </div>

        @if (session('import_errors'))
            <x-alert type="warning" title="Baris yang dilewati">
                <ul>@foreach (session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
            </x-alert>
        @endif
    </div>
@endsection
