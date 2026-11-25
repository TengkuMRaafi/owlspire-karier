@extends('layouts.admin')

@php $rules = $type->passing_rules ?? []; @endphp
@section('title', $type->name)
@section('heading', $type->name)
@section('subheading', 'Detail jenis ujian ('.$type->code.')')
@section('actions')
    <a href="{{ route('admin.setup.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
    <a href="{{ route('admin.setup.edit', $type) }}" class="btn-primary"><x-icon name="edit" class="size-4" /> Ubah</a>
@endsection

@section('content')
    @php $totalBank = $type->segments->sum('questions_count'); @endphp
    <div class="pn-stack">
        <x-detail-hero :title="$type->name" :sub="$type->description ?: null" icon="layers">
            <span @class(['ui-badge has-dot', 'is-success' => $type->is_active])>{{ $type->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            <span class="ad-d-chip"><x-icon name="compass" /> {{ $type->kind === 'career' ? 'Simulasi karier' : 'Seleksi resmi' }}</span>
            <span class="ad-d-chip"><x-icon name="file" /> {{ $type->code }}</span>
        </x-detail-hero>

        <div class="ui-stats is-compact">
            <x-detail-stat icon="clock" label="Durasi penuh" :value="(int) ($type->duration_seconds / 60)" hint="menit" />
            <x-detail-stat icon="play" label="Durasi coba" :value="(int) ($type->trial_duration_seconds / 60)" hint="menit" />
            <x-detail-stat icon="layers" label="Segmen" :value="$type->segments->count()" />
            <x-detail-stat icon="list" label="Soal di bank" :value="$totalBank" hint="semua segmen" :accent="$totalBank > 0" />
        </div>

        <div class="pn-card">
            <x-detail-head icon="info" title="Informasi" />
            <div class="ad-d-info is-list">
                <x-detail-item icon="compass" label="Kategori">{{ $type->kind === 'career' ? 'Simulasi karier' : 'Seleksi resmi' }}@if ($career) ({{ $career->name }})@endif</x-detail-item>
                @php
                    $pts = (int) config('platform.exam.points_per_correct', 5);
                    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
                    $perSegment = ! empty($rules['segments']);
                @endphp
                <x-detail-item icon="trophy" label="Passing grade" :wide="$perSegment">
                    @if ($perSegment)
                        <ul class="ad-pg">
                            @foreach ($rules['segments'] as $code => $pct)
                                @php $seg = $type->segments->firstWhere('code', $code); $maks = $seg ? $seg->question_count_full * $pts : 0; @endphp
                                <li>
                                    <span>{{ $seg?->name ?? strtoupper($code) }}</span>
                                    <em>@if ($maks > 0)Minimal {{ (int) round($pct / 100 * $maks) }} dari {{ $maks }} poin @else Minimal {{ $fmt($pct) }}% @endif</em>
                                </li>
                            @endforeach
                        </ul>
                        <span class="ad-pg-note">Peserta lulus bila setiap segmen mencapai nilai minimalnya.</span>
                    @else
                        Minimal {{ $fmt($rules['min_percent'] ?? 60) }}% dari skor total
                    @endif
                </x-detail-item>
                <x-detail-item icon="file" label="Deskripsi" :wide="true">{{ $type->description ?: '-' }}</x-detail-item>
            </div>
        </div>

        <div class="ui-table-wrap">
            <div class="ui-table-bar">
                <span class="ad-d-bar-t"><span class="ad-d-h-ic" aria-hidden="true"><x-icon name="layers" /></span>Segmen ({{ $type->segments->count() }})</span>
                <span class="ui-table-bar-end"><a href="{{ route('admin.setup.segments.create', $type) }}" class="btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Tambah segmen</a></span>
            </div>
            <div class="ui-table-scroll">
                <table class="ui-table">
                    <thead><tr><th class="is-no">No</th><th>Kode</th><th>Nama</th><th class="is-num">Soal coba</th><th class="is-num">Soal penuh</th><th class="is-num">Di bank</th><th class="is-aksi">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($type->segments as $s)
                            <tr>
                                <td class="is-no">{{ $loop->iteration }}</td>
                                <td class="is-nowrap">{{ $s->code }}</td>
                                <td><span class="ui-cell"><b>{{ $s->name }}</b>@if ($s->description)<small>{{ $s->description }}</small>@endif</span></td>
                                <td class="is-num">{{ $s->question_count_trial }}</td>
                                <td class="is-num">{{ $s->question_count_full }}</td>
                                <td class="is-num">{{ $s->questions_count }}</td>
                                <td class="is-aksi"><x-row-actions :edit="route('admin.setup.segments.edit', $s)" :delete="route('admin.setup.segments.destroy', $s)" :confirm="'Hapus segmen '.$s->name.'?'" :label="$s->name" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="ui-table-empty">Belum ada segmen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
