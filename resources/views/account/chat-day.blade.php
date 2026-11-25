@extends('layouts.dashboard')

@section('title', 'Riwayat Asisten AI')
@section('heading', $date->translatedFormat('l, d F Y'))
@section('subheading', 'Percakapan pada hari ini (hanya baca).')

@section('actions')
    <a href="{{ route('account.chat.days') }}" class="btn-outline btn-sm">Semua hari</a>
    <form method="POST" action="{{ route('account.chat.day.clear', $day) }}" data-confirm="Hapus percakapan tanggal ini?">
        @csrf @method('DELETE')
        <button type="submit" class="btn-outline btn-sm">Hapus percakapan ini</button>
    </form>
@endsection

@section('content')
    <div class="cd">
        <div class="cd-log">
            @foreach ($messages as $m)
                <div class="cd-row {{ $m->role === 'user' ? 'is-user' : 'is-bot' }}">
                    <div class="cd-msg">{{ $m->content }}<span class="cd-time">{{ $m->created_at->format('H:i') }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
