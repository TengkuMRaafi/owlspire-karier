@extends('layouts.dashboard')

@section('title', 'Rencana belajar')
@section('heading', 'Rencana belajar')
@section('subheading', 'Target harian, streak, dan jadwal belajar yang menyesuaikan hasil simulasimu.')

@section('content')
    <style>
        .pl-wrap { max-width: 56rem; margin-inline: auto; }
        .pl-hero { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 1.6rem 2.4rem; align-items: end; padding: 1.8rem 1.9rem; border-radius: 1.5rem; background: radial-gradient(26rem 14rem at 100% 0%, rgb(79 130 230 / .45), transparent 65%), #1b2638; color: #fff; }
        .pl-eyebrow { font-size: .74rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #9fb2d3; }
        .pl-big { margin-top: .5rem; font-family: var(--font-display, inherit); font-size: 4.6rem; font-weight: 700; line-height: 1; letter-spacing: -.02em; }
        .pl-sub { margin-top: .6rem; font-size: .92rem; line-height: 1.55; color: #b9c9e6; } .pl-sub b { color: #fff; }
        .pl-sk { display: flex; align-items: baseline; gap: .5rem; font-family: var(--font-display, inherit); font-size: 1.4rem; font-weight: 700; }
        .pl-sk small { font-family: inherit; font-size: .8rem; font-weight: 600; color: #9fb2d3; }
        .pl-week { display: flex; gap: .4rem; margin-top: .9rem; }
        .pl-day { flex: 1; text-align: center; font-size: .66rem; font-weight: 800; text-transform: uppercase; color: #9fb2d3; }
        .pl-day i { display: block; height: .6rem; margin-bottom: .3rem; border-radius: 99px; background: rgb(255 255 255 / .16); }
        .pl-day.is-on i { background: #ff9a4d; } .pl-day.is-today { color: #fff; }
        .pl-meter { height: .5rem; border-radius: 99px; background: var(--color-graphite-200, #e2e6ec); overflow: hidden; }
        .pl-hero .pl-meter { margin-top: .5rem; background: rgb(255 255 255 / .16); }
        .pl-meter i { display: block; height: 100%; border-radius: inherit; background: var(--color-accent-600, #3b6cd4); }
        .pl-hero .pl-meter i { background: #7fb0ff; }
        .pl-goal { margin-top: .9rem; font-size: .8rem; color: #b9c9e6; }
        .pl-tasks { display: grid; gap: .6rem; margin-top: 1rem; list-style: none; padding: 0; }
        .pl-task { display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: 1rem; }
        .pl-chk { flex: none; display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border: 2px solid var(--color-graphite-300, #c5cfde); border-radius: 99px; color: transparent; }
        .pl-chk svg { width: .9rem; height: .9rem; }
        .pl-task.is-done .pl-chk { border-color: #1f9d68; background: #1f9d68; color: #fff; }
        .pl-task.is-done b { text-decoration: line-through; opacity: .6; }
        .pl-task b { display: block; color: var(--color-graphite-800, #1f2a3d); }
        .pl-task p { margin-top: .15rem; font-size: .84rem; line-height: 1.5; color: var(--color-graphite-500, #667794); }
        .pl-task .btn-outline { margin-left: auto; flex: none; }
        .pl-weeks { display: grid; grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr)); gap: .55rem; margin-top: .9rem; }
        .pl-wk { padding: .7rem .75rem; border-radius: .9rem; background: var(--color-graphite-100, #edf0f5); font-size: .8rem; line-height: 1.45; }
        .pl-wk b { display: block; margin-bottom: .25rem; font-size: .76rem; color: var(--color-graphite-800, #1f2a3d); }
        .pl-wk ul { margin: 0; padding-left: 1rem; list-style: disc; color: var(--color-graphite-600, #4a5a73); }
        .pl-weak { display: grid; gap: .6rem; margin-top: .8rem; }
        .pl-weak div { display: grid; grid-template-columns: 8rem minmax(0, 1fr) 2.6rem; align-items: center; gap: .7rem; font-size: .86rem; }
        .pl-weak .pl-meter { margin: 0; }
        .pl-form { display: grid; gap: .9rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); align-items: end; }
        /* Isian dan tombol dibuat setinggi sama dan label sejajar, supaya baris Pengingat/Atur target tidak terlihat miring */
        .pl-form .ui-field > label { line-height: 1.25; }
        .pl-form .ui-input, .pl-form .ui-dd-btn, .pl-form .ui-pk-btn { height: 3rem; }
        .pl-form .btn-primary { width: 100%; height: 3rem; }
        @media (min-width: 721px) { .pl-form .btn-primary { width: auto; } }
        .pl-note { margin-top: .6rem; font-size: .82rem; line-height: 1.55; color: var(--color-graphite-500, #667794); }
        .pl-tg { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: .8rem; }
        @media (max-width: 720px) {
            .pl-hero { grid-template-columns: minmax(0, 1fr); padding: 1.3rem 1.2rem; gap: 1.3rem; } .pl-big { font-size: 3.6rem; }
            .pl-task { flex-wrap: wrap; } .pl-task .btn-outline { width: 100%; margin-left: 0; text-align: center; }
            .pl-weak div { grid-template-columns: 5.5rem minmax(0, 1fr) 2.4rem; }
        }
    </style>

    <div class="pn-stack pl-wrap">
        @php
            $left = $plan['days_left'];
        @endphp
        <section class="pl-hero" aria-label="Ringkasan belajar">
            <div>
                <p class="pl-eyebrow">{{ $profile->examType?->name ?? 'Belum ada target ujian' }}@if ($profile->exam_date) &middot; {{ $profile->exam_date->translatedFormat('d M Y') }}@endif</p>
                <p class="pl-big">{{ $left === null || $left < 0 ? '–' : ($left === 0 ? 'Hari H' : 'H-'.$left) }}</p>
                <p class="pl-sub">@if ($left !== null && $left >= 0)Fase <b>{{ $plan['phase'] }}</b>. @if ($plan['weak']) Fokus ke bagian yang paling rendah.@endif @else Isi tanggal ujian di bawah supaya rencana punya tenggat.@endif</p>
            </div>
            <div>
                <p class="pl-sk">{{ $streak['current'] }} <small>{{ $streak['current'] === 0 ? 'hari. Belum ada streak' : 'hari berturut-turut' }}</small></p>
                <div class="pl-week" aria-label="7 hari terakhir">
                    @foreach ($streak['week'] as $d)
                        <span @class(['pl-day', 'is-on' => $d['active'], 'is-today' => $d['today']])><i></i>{{ $d['label'] }}</span>
                    @endforeach
                </div>
                <p class="pl-goal">{{ $streak['active_today'] ? 'Hari ini sudah belajar.' : 'Belajar hari ini untuk '.($streak['current'] > 0 ? 'menjaga streak.' : 'memulai streak.') }} Terbaik {{ $streak['best'] }} hari &middot; simulasi hari ini {{ min($counts['exams'], 999) }}</p>
            </div>
        </section>

        <section class="pn-card" aria-label="Rencana hari ini">
            <div class="pn-card-h"><h2>Rencana hari ini</h2>@if ($plan['today'])<span class="ui-badge is-accent">{{ collect($plan['today'])->where('done', true)->count() }} dari {{ count($plan['today']) }} selesai</span>@endif</div>
            @if ($plan['today'])
                <ul class="pl-tasks">
                    @foreach ($plan['today'] as $t)
                        <li @class(['pl-task', 'is-done' => $t['done']])>
                            <span class="pl-chk"><x-icon name="check" /></span>
                            <div><b>{{ $t['title'] }}</b><p>{{ $t['detail'] }}</p></div>
                            @if ($t['url'] && ! $t['done'])<a href="{{ $t['url'] }}" class="btn-outline btn-sm">Mulai</a>@endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="pl-note">Tidak ada tugas. Tanggal ujianmu sudah lewat; perbarui di bawah untuk target berikutnya.</p>
            @endif
        </section>

        @if ($plan['weak'])
            <section class="pn-card" aria-label="Bagian yang perlu diperkuat">
                <div class="pn-card-h"><h2>Bagian yang perlu diperkuat</h2></div>
                <div class="pl-weak">
                    @foreach ($plan['weak'] as $w)
                        <div><span>{{ $w['name'] }}</span><span class="pl-meter" role="img" aria-label="{{ $w['pct'] }} persen"><i style="width: {{ $w['pct'] }}%"></i></span><b>{{ $w['pct'] }}%</b></div>
                    @endforeach
                </div>
                <p class="pl-note">Dari rata-rata hasil lima simulasi terakhirmu. Rencana memutar latihan fokus mulai dari bagian paling bawah.</p>
            </section>
        @endif

        @if (count($plan['week']) > 1)
            <section class="pn-card" aria-label="Tujuh hari ke depan">
                <div class="pn-card-h"><h2>Tujuh hari ke depan</h2></div>
                <div class="pl-weeks">
                    @foreach (array_slice($plan['week'], 1) as $day)
                        <div class="pl-wk"><b>{{ $day['label'] }}</b><ul>@foreach ($day['tasks'] as $t)<li>{{ $t['title'] }}</li>@endforeach</ul></div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="pn-card" aria-label="Atur target">
            <div class="pn-card-h"><h2>Atur target</h2></div>
            <form method="POST" action="{{ route('account.plan.update') }}" class="pl-form is-one">
                @csrf @method('PUT')
                <div class="ui-field"><label for="dd-exam_type_id">Simulasi yang kamu tuju</label>
                    <x-dropdown name="exam_type_id" id="dd-exam_type_id" aria="Simulasi tujuan" all="Belum ditentukan" :options="$types->pluck('name', 'id')->all()" :value="old('exam_type_id', $profile->exam_type_id)" block />
                </div>
                <div class="ui-field"><label for="exam_date">Tanggal ujian</label>
                    <input id="exam_date" type="date" name="exam_date" class="ui-input" min="{{ today()->toDateString() }}" value="{{ old('exam_date', $profile->exam_date?->toDateString()) }}">
                    @error('exam_date')<small class="text-red-700">{{ $message }}</small>@enderror
                </div>
                <div><button class="btn-primary" type="submit">Simpan target</button></div>
            </form>
        </section>

        <section class="pn-card" aria-label="Pengingat">
            <div class="pn-card-h"><h2>Pengingat</h2></div>
            <form method="POST" action="{{ route('account.plan.reminder') }}" class="pl-form">
                @csrf @method('PUT')
                <div class="ui-field"><label for="dd-remind_channel">Kirim lewat</label>
                    <x-dropdown name="remind_channel" id="dd-remind_channel" aria="Saluran pengingat" :options="['none' => 'Tidak usah', 'email' => 'Email']" :value="old('remind_channel', $profile->remind_channel)" block />
                    @error('remind_channel')<small class="text-red-700">{{ $message }}</small>@enderror
                </div>
                <div class="ui-field"><label for="remind_time">Jam ({{ config('platform.study.timezone') }})</label>
                    <input id="remind_time" type="time" name="remind_time" class="ui-input" value="{{ old('remind_time', $profile->remind_time) }}" required>
                </div>
                <div><button class="btn-primary" type="submit">Simpan pengingat</button></div>
            </form>
            <p class="pl-note">Pengingat hanya dikirim bila hari itu kamu belum belajar, paling banyak sekali sehari.</p>
        </section>
    </div>
@endsection
