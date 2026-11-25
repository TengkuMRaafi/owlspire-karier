@extends('layouts.admin')

@php
    $editing = $broadcast->exists;
    $readonly = $editing && ! $broadcast->isDraft();
    $kind = old('kind', $broadcast->kind ?: 'jadwal');
    $audience = old('audience', $broadcast->audience ?: 'all');
    $dailyLimit = (int) config('broadcast.daily_limit');
@endphp
@section('title', $readonly ? 'Detail broadcast' : ($editing ? 'Ubah broadcast' : 'Buat broadcast'))
@section('heading', $readonly ? 'Detail broadcast' : ($editing ? 'Ubah broadcast' : 'Buat broadcast'))
@section('subheading', $readonly ? 'Broadcast ini sudah dikirim, jadi isinya tidak bisa diubah.' : 'Tulis isinya sekali, cek pratinjau di sebelah, kirim tes ke emailmu, lalu kirim ke penerima.')
@section('actions')
    <a href="{{ route('admin.broadcast.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
@endsection

@section('content')
<style>
    .bc-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
    @media (min-width: 1100px) { .bc-grid { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); } .bc-side { position: sticky; top: 16px; } }
    .bc-fs { border: 0; padding: 0; margin: 0; min-width: 0; display: grid; gap: 18px; }
    .bc-fs > * { min-width: 0; }
    .bc-fs [hidden] { display: none !important; }
    .bc-legend { font-size: 13px; font-weight: 600; margin: 0 0 8px; }
    .bc-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    @media (min-width: 640px) { .bc-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .bc-tile, .bc-opt { position: relative; display: block; min-width: 0; }
    .bc-tile input, .bc-opt input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
    .bc-tile > span { display: flex; align-items: center; justify-content: center; text-align: center; min-height: 46px; padding: 8px 10px; border: 1px solid var(--bc-line, #d8dee8); border-radius: 12px; font-size: 14px; background: #fff; transition: border-color .15s, background .15s; }
    .bc-tile input:checked + span { border-color: #2f58b3; background: #e6efff; color: #1f3f8a; font-weight: 600; box-shadow: 0 0 0 1px #2f58b3 inset; }
    .bc-opts { display: grid; gap: 8px; }
    .bc-opt > span { display: flex; align-items: center; gap: 12px; min-height: 52px; padding: 10px 14px; border: 1px solid var(--bc-line, #d8dee8); border-radius: 12px; background: #fff; transition: border-color .15s, background .15s; }
    .bc-dot { flex: none; box-sizing: border-box; width: 20px; height: 20px; border-radius: 50%; border: 2px solid #9aa7bb; background: #fff; }
    .bc-opt-t { flex: 1 1 auto; min-width: 0; font-size: 14px; line-height: 1.3; }
    .bc-opt-n { flex: none; font-size: 12px; opacity: .7; white-space: nowrap; }
    .bc-opt input:checked + span { border-color: #2f58b3; background: #e6efff; box-shadow: 0 0 0 1px #2f58b3 inset; }
    .bc-opt input:checked + span .bc-dot { border-color: #2f58b3; background: #2f58b3; box-shadow: inset 0 0 0 3px #e6efff; }
    .bc-opt input:checked + span .bc-opt-t { color: #1f3f8a; font-weight: 600; }
    .bc-tile input:focus-visible + span, .bc-opt input:focus-visible + span { outline: 2px solid #2f58b3; outline-offset: 2px; }
    .bc-tile input:disabled, .bc-opt input:disabled { cursor: default; }
    .bc-2 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }
    @media (min-width: 640px) { .bc-2 { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .bc-note { font-size: 12px; opacity: .7; margin: 4px 0 0; }
    .bc-warn { font-size: 12px; color: #9a5b00; margin: 6px 0 0; display: none; }
    .bc-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--bc-line, #d8dee8); }
    @media (max-width: 639px) { .bc-actions { flex-direction: column-reverse; } .bc-actions > * { width: 100%; justify-content: center; } }
    .bc-sum { display: grid; gap: 6px; padding: 14px 16px; border-radius: 10px; background: #f3f6fb; font-size: 14px; margin-bottom: 18px; }
    .bc-sum b { font-weight: 600; }
    .bc-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .bc-head h2 { font-size: 15px; font-weight: 600; margin: 0; }
    .bc-dev { display: inline-flex; border: 1px solid var(--bc-line, #d8dee8); border-radius: 8px; overflow: hidden; }
    .bc-dev button { padding: 6px 12px; font-size: 13px; background: transparent; border: 0; cursor: pointer; color: inherit; }
    .bc-dev button[aria-pressed="true"] { background: #2f58b3; color: #fff; }
    .bc-stage { background: #eef2f8; border-radius: 10px; padding: 12px; display: flex; justify-content: center; overflow: hidden; }
    .bc-frame { width: 100%; max-width: 100%; border: 0; background: #eef2f8; border-radius: 6px; transition: width .2s; min-height: 420px; }
    .bc-frame.is-phone { width: 375px; }
    .bc-count { margin-top: 12px; font-size: 14px; }
</style>

<div class="bc-grid">
    <form id="bc-form" method="POST" action="{{ $editing ? route('admin.broadcast.update', $broadcast) : route('admin.broadcast.store') }}" class="pn-card ad-block ad-form"
          data-preview="{{ route('admin.broadcast.preview') }}" data-cta='@json($ctaDefaults)' data-limit="{{ $dailyLimit }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        @if ($readonly)
            <div class="bc-sum">
                <div><b>Status:</b> {{ $broadcast->statusLabel() }}</div>
                <div><b>Dikirim oleh:</b> {{ $broadcast->sender?->name ?? 'Admin' }} @if ($broadcast->sent_at) pada {{ $broadcast->sent_at->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB @endif</div>
                <div><b>Penerima:</b> {{ $broadcast->audienceLabel() }}, {{ number_format($broadcast->recipients_count) }} email</div>
                <div><b>Hasil:</b> {{ number_format($broadcast->sent_count) }} terkirim, {{ number_format($broadcast->failed_count) }} gagal</div>
            </div>
        @endif

        <fieldset class="bc-fs" @disabled($readonly)>
            <div>
                <p class="bc-legend" id="bc-kind-label">Jenis informasi</p>
                <div class="bc-tiles" role="radiogroup" aria-labelledby="bc-kind-label">
                    @foreach (\App\Models\EmailBroadcast::KINDS as $key => $label)
                        <label class="bc-tile"><input type="radio" name="kind" value="{{ $key }}" @checked($kind === $key)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('kind')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>

            <div>
                <p class="bc-legend" id="bc-aud-label">Kirim ke</p>
                <div class="bc-opts" role="radiogroup" aria-labelledby="bc-aud-label">
                    @foreach (\App\Models\EmailBroadcast::AUDIENCES as $key => $label)
                        <label class="bc-opt"><input type="radio" name="audience" value="{{ $key }}" data-label="{{ $label }}" data-count="{{ $counts[$key] ?? 0 }}" @checked($audience === $key)><span><i class="bc-dot"></i><b class="bc-opt-t" style="font-weight:inherit">{{ $label }}</b><em class="bc-opt-n" style="font-style:normal">{{ number_format($counts[$key] ?? 0) }} penerima</em></span></label>
                    @endforeach
                </div>
                <p class="bc-note">Hanya pengguna dengan email terverifikasi yang belum berhenti menerima info.</p>
                @error('audience')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>

            <div class="ui-field">
                <label for="subject">Subjek email</label>
                <input id="subject" name="subject" value="{{ old('subject', $broadcast->subject) }}" class="ui-input" maxlength="160" placeholder="mis. Jadwal tes CPNS gelombang 2 sudah keluar" required>
                <small>Yang terlihat pertama di inbox.</small>
                @error('subject')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="title">Judul di dalam email</label>
                <input id="title" name="title" value="{{ old('title', $broadcast->title) }}" class="ui-input" maxlength="160" placeholder="mis. Tes CPNS gelombang 2 dibuka 12 Desember" required>
                @error('title')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field">
                <label for="body">Isi</label>
                <textarea id="body" name="body" rows="5" maxlength="5000" class="ui-input" required>{{ old('body', $broadcast->body) }}</textarea>
                <small>Salam "Halo, nama" ditambahkan otomatis. Pisahkan paragraf dengan satu baris kosong.</small>
                @error('body')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>

            <div class="ui-field" data-kinds="diskon">
                <label for="highlight">Kode promo</label>
                <input id="highlight" name="highlight" value="{{ old('highlight', $broadcast->highlight) }}" class="ui-input" maxlength="60" placeholder="mis. HEMAT30">
                @error('highlight')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>
            <div class="ui-field" data-kinds="jadwal,diskon,karier">
                <label for="detail" id="detail-label">Detail</label>
                <textarea id="detail" name="detail" rows="4" maxlength="1500" class="ui-input">{{ old('detail', $broadcast->detail) }}</textarea>
                <small id="detail-hint"></small>
                @error('detail')<small class="text-red-700">{{ $message }}</small>@enderror
            </div>

            <div class="bc-2">
                <div class="ui-field">
                    <label for="cta_label">Tulisan tombol</label>
                    <input id="cta_label" name="cta_label" value="{{ old('cta_label', $broadcast->cta_label) }}" class="ui-input" maxlength="40" placeholder="mis. Lihat jadwal seleksi">
                    @error('cta_label')<small class="text-red-700">{{ $message }}</small>@enderror
                </div>
                <div class="ui-field">
                    <label for="cta_url">Alamat tombol</label>
                    <input id="cta_url" type="url" name="cta_url" value="{{ old('cta_url', $broadcast->cta_url) }}" class="ui-input" maxlength="500" placeholder="https://">
                    @error('cta_url')<small class="text-red-700">{{ $message }}</small>@enderror
                </div>
            </div>
            <small class="bc-note" style="margin-top:-8px">Kosongkan tulisan dan alamat tombol bila tidak perlu tombol.</small>
        </fieldset>

        @unless ($readonly)
            <div class="bc-actions">
                <button type="submit" name="action" value="draft" class="btn-outline">Simpan draf</button>
                <button type="submit" name="action" value="test" class="btn-outline">Kirim tes ke saya</button>
                <button type="submit" name="action" value="send" class="btn-primary" id="bc-send">Kirim ke semua</button>
            </div>
        @endunless
    </form>

    <aside class="pn-card ad-block bc-side" aria-label="Pratinjau email">
        <div class="bc-head">
            <h2>Pratinjau email</h2>
            <div class="bc-dev" role="group" aria-label="Ukuran layar">
                <button type="button" data-dev="laptop" aria-pressed="true">Laptop</button>
                <button type="button" data-dev="phone" aria-pressed="false">HP</button>
            </div>
        </div>
        <div class="bc-stage"><iframe id="bc-frame" class="bc-frame" title="Pratinjau email" sandbox="allow-same-origin"></iframe></div>
        <p class="bc-count">Penerima: <b id="bc-count">0</b> pengguna <span id="bc-aud"></span></p>
        <p class="bc-warn" id="bc-warn">Melebihi batas kiriman harian SMTP ({{ number_format($dailyLimit) }} email). Email di atas batas bisa gagal terkirim.</p>
    </aside>
</div>

<script>
(() => {
    const form = document.getElementById('bc-form');
    const frame = document.getElementById('bc-frame');
    const cta = JSON.parse(form.dataset.cta || '{}');
    const limit = parseInt(form.dataset.limit || '0', 10);
    const readonly = @json($readonly);
    const detailText = {
        jadwal: ['Baris tanggal', 'Satu baris satu jadwal, format "Nama: tanggal". Contoh: Pendaftaran: 1 - 10 Desember 2026', 'Pendaftaran: 1 - 10 Desember 2026\nTes SKD: 12 - 20 Desember 2026'],
        diskon: ['Keterangan promo (opsional)', 'Satu baris satu keterangan. Contoh: Berlaku sampai 22 November 2026', 'Berlaku sampai 22 November 2026'],
        karier: ['Daftar lowongan', 'Satu baris satu lowongan, format "Judul | Perusahaan, Lokasi".', 'Software Engineer | PT Contoh Teknologi, Pekanbaru'],
    };
    const val = (n) => form.querySelector(`[name="${n}"]:checked`)?.value;
    let lastKind = val('kind');

    function syncKind() {
        const kind = val('kind');
        form.querySelectorAll('[data-kinds]').forEach((el) => { el.hidden = !el.dataset.kinds.split(',').includes(kind); });
        const t = detailText[kind];
        if (t) {
            document.getElementById('detail-label').textContent = t[0];
            document.getElementById('detail-hint').textContent = t[1];
            document.getElementById('detail').placeholder = t[2];
        }
        // Isi awal tombol ikut jenis, tapi tidak menimpa tulisan yang sudah diubah admin.
        const label = form.elements.cta_label, url = form.elements.cta_url;
        const prev = cta[lastKind] || {}, next = cta[kind] || {};
        if (!readonly && kind !== lastKind && (!label.value || label.value === prev.label) && (!url.value || url.value === prev.url)) {
            label.value = next.label || ''; url.value = next.url || '';
        }
        lastKind = kind;
    }

    function syncAudience() {
        const r = form.querySelector('[name="audience"]:checked');
        const n = parseInt(r?.dataset.count || '0', 10);
        document.getElementById('bc-count').textContent = n.toLocaleString('id-ID');
        document.getElementById('bc-aud').textContent = r ? `(${r.dataset.label})` : '';
        document.getElementById('bc-warn').style.display = limit && n > limit ? 'block' : 'none';
    }

    let timer, seq = 0;
    async function preview() {
        const mine = ++seq;
        const data = new FormData(form);
        data.delete('_method'); // pratinjau selalu POST, walau form sedang mode ubah (PUT)
        try {
            const res = await fetch(form.dataset.preview, {
                method: 'POST', body: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin',
            });
            if (!res.ok || mine !== seq) return;
            frame.srcdoc = await res.text();
        } catch (e) { /* pratinjau gagal dimuat: biarkan yang lama */ }
    }
    const later = () => { clearTimeout(timer); timer = setTimeout(preview, 300); };
    const fit = () => { try { frame.style.height = Math.max(420, frame.contentDocument.documentElement.scrollHeight + 4) + 'px'; } catch (e) {} };
    frame.addEventListener('load', fit);

    form.addEventListener('input', later);
    form.addEventListener('change', (e) => { if (e.target.name === 'kind') syncKind(); if (e.target.name === 'audience') syncAudience(); later(); });

    document.querySelectorAll('[data-dev]').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('[data-dev]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        frame.classList.toggle('is-phone', b.dataset.dev === 'phone');
        setTimeout(fit, 250);
    }));

    // Kirim ke semua: dialog konfirmasi yang menyebut jumlah penerima.
    const send = document.getElementById('bc-send');
    send?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!form.reportValidity()) return;
        const r = form.querySelector('[name="audience"]:checked');
        const n = parseInt(r?.dataset.count || '0', 10).toLocaleString('id-ID');
        const message = `Email akan dikirim ke ${n} pengguna (${r?.dataset.label || ''}). Setelah dikirim, broadcast tidak bisa dibatalkan. Sudah cek pratinjau dan kirim tes ke dirimu sendiri?`;
        const ok = window.OwlUI?.confirm
            ? await window.OwlUI.confirm({ title: 'Kirim broadcast ke semua?', message, ok: 'Ya, kirim sekarang', tone: 'warning' })
            : window.confirm(message);
        if (ok) form.requestSubmit(send);
    });

    syncKind(); syncAudience(); preview();
})();
</script>
@endsection
