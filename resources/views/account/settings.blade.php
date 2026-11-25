@extends('layouts.dashboard')

@section('title', 'Pengaturan')
@section('heading', 'Pengaturan')
@section('subheading', 'Atur akunmu dan cara tiap menu bekerja untukmu.')

@php
    $nav = [
        'Akun' => [['profil', 'Profil', 'user'], ['karier', 'Karier target', 'target'], ['keamanan', 'Keamanan', 'lock']],
        'Menu' => collect($groups)->only(['exam', 'learning', 'assistant'])->map(fn ($g, $id) => [$id, $g['title'], $g['icon']])->values()->all(),
        'Tampilan' => collect($groups)->only(['notifications', 'dashboard', 'privacy'])->map(fn ($g, $id) => [$id, $g['title'], $g['icon']])->values()->all(),
        'Data' => [['data', 'Data dan akun', 'file']],
    ];
    $sections = ['profil', 'karier', 'exam', 'learning', 'assistant', 'notifications', 'dashboard', 'privacy', 'keamanan', 'data'];
@endphp

@section('content')
    {{-- Satu bagian tampil sekaligus (tab). Tanpa JavaScript semua bagian tetap tampil berurutan. --}}
    <div class="ac-set" data-set>
        <aside class="ac-set-side">
            <div class="ac-set-me">
                <span class="ac-set-av" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div>
                    <b>{{ $user->name }}</b>
                    <small>{{ $user->email }}</small>
                </div>
                <span @class(['ui-badge', 'is-premium' => $premium, 'is-dark' => $user->isInternal()])>{{ $user->isInternal() ? $user->roleLabel() : ($premium ? 'Premium' : 'Gratis') }}</span>
            </div>

            <nav class="ac-set-nav" aria-label="Bagian pengaturan">
                @foreach ($nav as $label => $items)
                    @if (count($items) === 1)
                        @foreach ($items as [$id, $text, $icon])
                            <div class="ac-set-col is-single"><a href="#{{ $id }}" data-set-tab="{{ $id }}" class="ac-set-colbtn"><x-icon :name="$icon" /><span class="ac-set-colt"><small>{{ $label }}</small><b>{{ $text }}</b></span></a></div>
                        @endforeach
                    @else
                        <div class="ac-set-col" data-set-col>
                            <button type="button" class="ac-set-colbtn" aria-haspopup="true" aria-expanded="false" data-set-colbtn>
                                <x-icon :name="$items[0][2]" />
                                <span class="ac-set-colt"><small>{{ $label }}</small><b data-set-cur data-set-def="Pilih bagian">Pilih bagian</b></span>
                                <svg class="ac-set-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div class="ac-set-menu" hidden>
                                @foreach ($items as [$id, $text, $icon])
                                    <a href="#{{ $id }}" data-set-tab="{{ $id }}"><x-icon :name="$icon" /><span>{{ $text }}</span></a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="ac-set-body">
            @foreach ($sections as $section)
                @if (isset($groups[$section]))
                    @include('account.settings.group', ['id' => $section, 'group' => $groups[$section], 'values' => $values[$section]])
                @else
                    @include('account.settings.'.$section)
                @endif
            @endforeach
        </div>
    </div>
    {{-- Pilih bagian dari #hash sebelum halaman digambar, supaya tidak berkedip. --}}
    <script>(function (s) { var r = s.previousElementSibling, e = null; try { e = document.getElementById(decodeURIComponent(location.hash.slice(1))); } catch (x) {} if (!e || !e.classList.contains('ac-set-sec')) e = r.querySelector('.ac-set-sec'); r.classList.add('is-tabs'); e.classList.add('is-on'); })(document.currentScript);</script>
@endsection
