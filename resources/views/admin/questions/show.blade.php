@extends('layouts.admin')

@section('title', 'Detail soal')
@section('heading', 'Detail soal')
@section('subheading', ($question->segment?->type?->name ?? '').' - '.($question->segment?->name ?? ''))
@section('actions')
    <a href="{{ route('admin.questions.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
    <a href="{{ route('admin.questions.edit', $question) }}" class="btn-primary"><x-icon name="edit" class="size-4" /> Ubah</a>
@endsection

@section('content')
    <div class="pn-stack ad-form">
        <x-detail-hero :title="$question->segment?->type?->name ?? 'Soal'" :sub="$question->segment?->name" icon="list">
            <span @class(['ui-badge', 'is-accent' => $question->is_trial, 'is-dark' => ! $question->is_trial])>{{ $question->is_trial ? 'Coba gratis' : 'Premium' }}</span>
            <span class="ad-d-chip"><x-icon name="check-circle" /> Kunci jawaban {{ $question->answer }}</span>
        </x-detail-hero>

        <div class="pn-card ad-block">
            <x-detail-head icon="list" title="Teks soal" />
            <p class="ad-prose">{{ $question->body }}</p>
            @if ($question->image_url)
                <figure class="ad-pv-img"><img src="{{ $question->image_url }}" alt="Gambar soal" loading="lazy"></figure>
            @endif

            <ul class="ad-opts">
                @foreach (($question->options ?? []) as $key => $text)
                    @continue(blank($text))
                    <li @class(['is-key' => $key === $question->answer])><b>{{ $key }}</b><span>{{ $text }}</span>@if ($key === $question->answer)<em>Kunci jawaban</em>@endif</li>
                @endforeach
            </ul>
        </div>

        @if ($question->explanation)
            <div class="pn-card">
                <x-detail-head icon="book" title="Pembahasan" />
                <p class="ad-prose">{{ $question->explanation }}</p>
            </div>
        @endif
    </div>
@endsection
