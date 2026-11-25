@extends('layouts.admin')

@section('title', 'Broadcast')
@section('heading', 'Broadcast')
@section('subheading', 'Kirim info ke pengguna lewat email: jadwal seleksi, diskon, karier baru, atau pengumuman umum. Riwayat tiap pengiriman tercatat di sini.')
@section('actions')
    <a href="{{ route('admin.broadcast.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Buat broadcast</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <div class="ui-table-scroll">
            <table class="ui-table">
                <thead><tr><th class="is-no">No</th><th>Broadcast</th><th>Jenis</th><th>Penerima</th><th>Status</th><th>Pengirim</th><th class="is-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($broadcasts as $b)
                        @php $when = ($b->sent_at ?? $b->created_at)->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i'); @endphp
                        <tr>
                            <td class="is-no">{{ $broadcasts->firstItem() + $loop->index }}</td>
                            <td><span class="ui-cell"><b>{{ $b->subject }}</b><small>{{ $b->title }}</small></span></td>
                            <td><span class="ui-badge is-accent">{{ $b->kindLabel() }}</span></td>
                            <td><span class="ui-cell"><b>{{ $b->audienceLabel() }}</b>@if (! $b->isDraft())<small>{{ number_format($b->sent_count) }} terkirim dari {{ number_format($b->recipients_count) }}@if ($b->failed_count), {{ number_format($b->failed_count) }} gagal @endif</small>@endif</span></td>
                            <td><span @class(['ui-badge has-dot', 'is-success' => $b->status === 'sent', 'is-warning' => in_array($b->status, ['queued', 'sending'], true)])>{{ $b->statusLabel() }}</span></td>
                            <td class="is-nowrap"><span class="ui-cell"><b>{{ $b->sender?->name ?? 'Admin' }}</b><small>{{ $b->isDraft() ? 'Dibuat' : 'Dikirim' }} {{ $when }}</small></span></td>
                            <td class="is-aksi">
                                @if ($b->isDraft())
                                    <x-row-actions :edit="route('admin.broadcast.edit', $b)" :delete="route('admin.broadcast.destroy', $b)" confirm="Hapus draf ini? Tindakan ini tidak bisa dibatalkan." label="draf" />
                                @else
                                    <x-row-actions :show="route('admin.broadcast.edit', $b)" label="broadcast" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="ui-table-empty">Belum ada broadcast. Klik Buat broadcast untuk mulai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-foot :paginator="$broadcasts" label="broadcast" />
    </div>
@endsection
