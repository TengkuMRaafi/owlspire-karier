@extends('layouts.admin')

@section('title', $lesson->title)
@section('heading', 'Detail video')
@section('subheading', $lesson->title)
@section('actions')
    <a href="{{ route('admin.learning.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
    <a href="{{ route('admin.learning.edit', $lesson) }}" class="btn-primary"><x-icon name="edit" class="size-4" /> Ubah</a>
@endsection

@section('content')
    <div class="pn-stack">
        <x-detail-hero :title="$lesson->title" :sub="$lesson->section->course->title ?? null" icon="play">
            <span @class(['ui-badge has-dot', 'is-success' => $lesson->is_approved, 'is-warning' => ! $lesson->is_approved])>{{ $lesson->is_approved ? 'Tayang' : 'Menunggu persetujuan' }}</span>
            <span class="ad-d-chip"><x-icon name="user" /> {{ $lesson->credit_name }}</span>
            <span class="ad-d-chip"><x-icon name="file" /> {{ $lesson->license }}</span>
        </x-detail-hero>

        <div class="pn-split is-even">
            <div class="ad-video">
                <div class="ratio"><iframe loading="lazy" src="https://www.youtube-nocookie.com/embed/{{ $lesson->video_id }}" title="{{ $lesson->title }}" allowfullscreen></iframe></div>
            </div>
            <div class="pn-card">
                <x-detail-head icon="info" title="Informasi" />
                <div class="ad-d-info">
                    <x-detail-item icon="play" label="Judul" :wide="true">{{ $lesson->title }}</x-detail-item>
                    <x-detail-item icon="book" label="Jalur">{{ $lesson->section->course->title ?? '-' }}</x-detail-item>
                    <x-detail-item icon="user" label="Kreator">{{ $lesson->credit_name }}</x-detail-item>
                    <x-detail-item icon="shield" label="Lisensi" :wide="true">{{ $lesson->license }}</x-detail-item>
                </div>
                @unless ($lesson->is_approved)
                    <div class="ad-d-acts">
                        <form method="POST" action="{{ route('admin.learning.approve', $lesson) }}">@csrf<button class="btn-primary btn-sm" type="submit">Setujui dan tayangkan</button></form>
                    </div>
                @endunless
            </div>
        </div>
    </div>
@endsection
