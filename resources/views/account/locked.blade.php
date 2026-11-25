@extends('layouts.dashboard')

@section('title', $info['title'])
@section('heading', $info['title'])
@section('subheading', 'Fitur ini tersedia untuk pengguna Premium.')

@section('content')
    <style>
        .lk { max-width: 40rem; margin-inline: auto; text-align: center; padding: 2.4rem 1.6rem; }
        .lk-ic { display: grid; place-items: center; width: 4rem; height: 4rem; margin: 0 auto 1.1rem; border-radius: 1.2rem; background: var(--color-accent-50, #eef4fd); color: var(--color-accent-700, #2f58b3); }
        .lk-ic svg { width: 1.8rem; height: 1.8rem; }
        .lk h2 { font-size: 1.3rem; font-weight: 800; color: var(--color-graphite-800, #1f2a3d); }
        .lk > p { margin-top: .5rem; line-height: 1.6; color: var(--color-graphite-600, #4a5a73); }
        .lk ul { display: grid; gap: .6rem; margin: 1.4rem auto 0; padding: 0; max-width: 28rem; list-style: none; text-align: left; }
        .lk li { display: flex; gap: .6rem; align-items: flex-start; font-size: .92rem; line-height: 1.5; }
        .lk li svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .15rem; color: #1f9d68; }
        .lk .btn-primary { margin-top: 1.6rem; }
    </style>
    <section class="pn-card lk">
        <span class="lk-ic"><x-icon name="crown" /></span>
        <h2>{{ $info['title'] }} khusus Premium</h2>
        <p>{{ $info['lead'] }}</p>
        <ul>
            @foreach ($info['points'] as $p)<li><x-icon name="check-circle" /><span>{{ $p }}</span></li>@endforeach
        </ul>
        <a href="{{ route('account.subscription') }}" class="btn-primary">Lihat paket Premium</a>
    </section>
@endsection
