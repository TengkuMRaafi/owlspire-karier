@extends('layouts.admin')

@section('title', $career->name)
@section('heading', $career->name)
@section('subheading', 'Detail karier')
@section('actions')
    <a href="{{ route('admin.careers.index') }}" class="btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
    <a href="{{ route('admin.careers.edit', $career) }}" class="btn-primary"><x-icon name="edit" class="size-4" /> Ubah</a>
@endsection

@section('content')
    @php $tags = (array) $career->detail('tags', []); $type = $career->examType; @endphp
    <div class="pn-stack">
        <x-detail-hero :title="$career->name" :image="$theme['image']" icon="briefcase">
            <span @class(['ui-badge has-dot', 'is-success' => $career->is_active])>{{ $career->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            <span class="ad-d-chip"><x-icon name="layers" /> {{ $career->division }}</span>
            <span class="ad-d-chip"><x-icon name="compass" /> {{ $theme['label'] }}</span>
        </x-detail-hero>

        <div class="ui-stats is-compact">
            <x-detail-stat icon="star" label="Urutan" :value="$career->rank" hint="posisi di katalog" />
            <x-detail-stat icon="list" label="Soal di bank" :value="$questions" hint="untuk simulasi ini" :accent="$questions > 0" />
            <x-detail-stat icon="layers" label="Bagian" :value="$type?->segments->count() ?? 0" hint="segmen simulasi" />
            <x-detail-stat icon="clock" label="Durasi penuh" :value="$type ? (int) ($type->duration_seconds / 60) : '-'" hint="menit" />
        </div>

        <div class="pn-split is-even">
            <div class="pn-card">
                <x-detail-head icon="info" title="Informasi">
                    <span class="ad-hint">/{{ $career->slug }}</span>
                </x-detail-head>
                <div class="ad-d-info">
                    <x-detail-item icon="briefcase" label="Nama">{{ $career->name }}</x-detail-item>
                    <x-detail-item icon="layers" label="Divisi">{{ $career->division }}</x-detail-item>
                    <x-detail-item icon="compass" label="Tag" :wide="true">
                        @if ($tags)
                            <span class="ad-d-tags">@foreach ($tags as $tag)<span>{{ $tag }}</span>@endforeach</span>
                        @else - @endif
                    </x-detail-item>
                </div>
                @if ($career->summary)
                    <div class="ad-d-note"><b>Ringkasan</b>{{ $career->summary }}</div>
                @endif
                @if ($career->detail('tren'))
                    <div class="ad-d-note"><b>Tren</b>{{ $career->detail('tren') }}</div>
                @endif
            </div>

            <div class="pn-card">
                <x-detail-head icon="play" title="Simulasi">
                    @if ($type)<a href="{{ route('admin.setup.show', $type) }}" class="ui-link">Buka jenis ujian</a>@endif
                </x-detail-head>
                @if ($type)
                    <div class="ad-d-info">
                        <x-detail-item icon="play" label="Nama simulasi" :wide="true">{{ $type->name }}</x-detail-item>
                        <x-detail-item icon="clock" label="Durasi penuh">{{ (int) ($type->duration_seconds / 60) }} menit</x-detail-item>
                        <x-detail-item icon="layers" label="Bagian">{{ $type->segments->count() }}</x-detail-item>
                        <x-detail-item icon="list" label="Soal di bank">{{ $questions }}</x-detail-item>
                    </div>
                    <div class="ad-d-acts"><a href="{{ route('admin.questions.index', ['jenis' => $type->code]) }}" class="btn-outline btn-sm">Lihat soal karier ini</a></div>
                @else
                    <p class="ad-hint">Karier ini belum punya simulasi.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
