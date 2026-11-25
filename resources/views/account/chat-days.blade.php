@extends('layouts.dashboard')

@section('title', 'Riwayat Asisten AI')
@section('heading', 'Riwayat Asisten AI')
@section('subheading', 'Percakapan direset tiap hari. Chat hari-hari sebelumnya tersimpan di sini.')

@section('actions')
    <a href="{{ route('account.chat') }}" class="btn-outline btn-sm">Kembali ke chat</a>
@endsection

@section('content')
    <div class="cd">
        @forelse ($days as $d)
            @php $date = \Carbon\Carbon::createFromFormat('!Y-m-d', $d->day); @endphp
            <a href="{{ route('account.chat.day', $d->day) }}" class="cd-item">
                <span class="cd-date" aria-hidden="true"><b>{{ $date->translatedFormat('d') }}</b><small>{{ $date->translatedFormat('M') }}</small></span>
                <div class="cd-body">
                    <b>{{ $date->translatedFormat('l, d F Y') }}</b>
                    @if ($d->preview)
                        <ul>
                            @foreach ($d->preview as $q)
                                <li>{{ \Illuminate\Support\Str::limit($q, 90) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <span class="cd-count">{{ $d->questions }} pertanyaan</span>
            </a>
        @empty
            <div class="cd-empty">
                <span class="cb-hero" aria-hidden="true"><x-owl /></span>
                <b>Belum ada riwayat</b>
                <p>Percakapan hari ini akan masuk ke sini mulai besok.</p>
            </div>
        @endforelse
    </div>
@endsection
