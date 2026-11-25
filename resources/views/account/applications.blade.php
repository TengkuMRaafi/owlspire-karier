@extends('layouts.dashboard')

@section('title', 'Pelacak lamaran')
@section('heading', 'Pelacak lamaran')
@section('subheading', 'Catat ke mana saja kamu melamar, pantau tahapnya, dan terima alert lowongan baru.')
@section('actions')
    <a href="{{ route('jobs.index') }}" class="btn-outline"><x-icon name="search" class="size-4" /> Cari lowongan</a>
@endsection

@section('content')
    <style>
        .ap-follow { display: grid; gap: .45rem; margin: 0; padding: 0; list-style: none; }
        .ap-follow li { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .3rem 1rem; font-size: .88rem; }
        .ap-board { display: grid; grid-template-columns: repeat(5, minmax(15rem, 1fr)); gap: .8rem; overflow-x: auto; padding-bottom: .4rem; }
        .ap-col { min-width: 0; padding: .8rem; border-radius: 1rem; background: var(--color-graphite-100, #edf0f5); }
        html[data-theme='dark'] .ap-col { background: rgb(255 255 255 / .04); }
        .ap-col h3 { display: flex; justify-content: space-between; margin-bottom: .6rem; font-size: .82rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--color-graphite-600, #4a5a73); }
        .ap-card { margin-bottom: .6rem; padding: .8rem .9rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: .9rem; background: var(--pn-card, #fff); }
        .ap-card b { display: block; font-size: .92rem; line-height: 1.3; color: var(--color-graphite-800, #1f2a3d); }
        .ap-card small { display: block; margin-top: .15rem; font-size: .8rem; color: var(--color-graphite-500, #667794); }
        .ap-card p { margin-top: .4rem; font-size: .82rem; line-height: 1.5; color: var(--color-graphite-600, #4a5a73); }
        .ap-acts { display: flex; align-items: center; gap: .5rem; margin-top: .6rem; }
        .ap-acts .ui-dd { flex: 1; }
        .ap-empty { padding: .8rem; border: 1.5px dashed var(--color-graphite-300, #c5cfde); border-radius: .8rem; font-size: .82rem; text-align: center; color: var(--color-graphite-500, #667794); }
        .ap-grid { display: grid; gap: .9rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); align-items: end; }
        .ap-alert { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem; padding: .7rem .9rem; border: 1px solid var(--color-graphite-200, #e2e6ec); border-radius: .9rem; }
        .ap-alert + .ap-alert { margin-top: .5rem; }
        .ap-alert b { font-size: .9rem; }
        .ap-alert small { display: block; font-size: .8rem; color: var(--color-graphite-500, #667794); }
        .ap-row { display: flex; gap: .5rem; align-items: center; }
        @media (max-width: 639px) {
            /* Mobile: tahap disusun ke bawah (tidak digeser ke samping) supaya tidak ada kolom yang terpotong */
            body .ap-board { grid-template-columns: minmax(0, 1fr); gap: .55rem; overflow-x: visible; scroll-snap-type: none; margin-inline: 0; padding: 0; }
            body .ap-col { scroll-snap-align: none; }
            body .ap-col h3 { margin-bottom: .5rem; }
            /* Tahap yang masih kosong dipadatkan jadi satu baris */
            body .ap-col:has(> .ap-empty) { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
            body .ap-col:has(> .ap-empty) h3 { flex: 1 1 auto; margin-bottom: 0; }
            body .ap-col:has(> .ap-empty) .ap-empty { flex: none; padding: 0; border: 0; font-size: .74rem; }
        }
    </style>

    <div class="pn-stack">
        @if ($followUps->isNotEmpty())
            <x-alert type="warning" title="Perlu ditindaklanjuti">
                <ul class="ap-follow">
                    @foreach ($followUps as $a)
                        <li><span><b>{{ $a->title }}</b> &middot; {{ $a->company }}</span><span>{{ $a->follow_up_on->isPast() && ! $a->follow_up_on->isToday() ? 'Lewat '.$a->follow_up_on->translatedFormat('d M') : ($a->follow_up_on->isToday() ? 'Hari ini' : $a->follow_up_on->translatedFormat('d M')) }}</span></li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <section class="pn-card" aria-label="Tambah lamaran">
            <details @if ($errors->has('title') || $errors->has('company')) open @endif>
                <summary class="pn-card-h" style="cursor:pointer;margin:0"><h2>Tambah lamaran manual</h2></summary>
                <form method="POST" action="{{ route('account.applications.store') }}" class="ap-grid" style="margin-top:1rem">
                    @csrf
                    <div class="ui-field"><label for="a-title">Posisi</label><input id="a-title" name="title" class="ui-input" maxlength="160" value="{{ old('title') }}" required>@error('title')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    <div class="ui-field"><label for="a-company">Perusahaan</label><input id="a-company" name="company" class="ui-input" maxlength="120" value="{{ old('company') }}" required></div>
                    <div class="ui-field"><label for="a-url">Tautan lowongan</label><input id="a-url" type="url" name="url" class="ui-input" placeholder="https://" value="{{ old('url') }}">@error('url')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    <div class="ui-field"><label for="dd-a-status">Tahap</label><x-dropdown name="status" id="dd-a-status" aria="Tahap" :options="$statuses" :value="old('status', 'dilamar')" block /></div>
                    <div class="ui-field"><label for="a-follow">Tindak lanjut</label><input id="a-follow" type="date" name="follow_up_on" class="ui-input" value="{{ old('follow_up_on') }}"></div>
                    <div class="ui-field"><label for="a-notes">Catatan</label><input id="a-notes" name="notes" class="ui-input" maxlength="1000" value="{{ old('notes') }}"></div>
                    <div><button class="btn-primary" type="submit">Tambah</button></div>
                </form>
            </details>
        </section>

        <div class="ap-board" aria-label="Papan lamaran">
            @foreach ($statuses as $key => $label)
                @php $items = $columns[$key] ?? collect(); @endphp
                <section class="ap-col" aria-label="{{ $label }}">
                    <h3><span>{{ $label }}</span><span>{{ $items->count() }}</span></h3>
                    @forelse ($items as $a)
                        <article class="ap-card">
                            <b>{{ $a->title }}</b>
                            <small>{{ $a->company }}@if ($a->location) &middot; {{ $a->location }}@endif</small>
                            @if ($a->notes)<p>{{ $a->notes }}</p>@endif
                            @if ($a->follow_up_on)<small>Tindak lanjut {{ $a->follow_up_on->translatedFormat('d M Y') }}</small>@endif
                            <div class="ap-row" style="margin-top:.5rem">
                                @if ($a->url)<a href="{{ $a->url }}" class="ui-link" target="_blank" rel="noopener noreferrer nofollow">Buka lowongan</a>@endif
                            </div>
                            <div class="ap-acts">
                                <form method="POST" action="{{ route('account.applications.update', $a) }}" style="flex:1">
                                    @csrf @method('PATCH')
                                    <x-dropdown name="status" :id="'dd-st-'.$a->id" aria="Pindahkan ke tahap" :options="$statuses" :value="$a->status" size="sm" block submit />
                                </form>
                                <form method="POST" action="{{ route('account.applications.destroy', $a) }}" data-confirm="Hapus lamaran ini dari pelacak?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui-act is-danger" title="Hapus" aria-label="Hapus lamaran {{ $a->title }}"><x-icon name="trash" /></button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="ap-empty">Kosong</p>
                    @endforelse
                </section>
            @endforeach
        </div>

        <section class="pn-card" aria-label="Alert lowongan">
            <div class="pn-card-h"><h2>Alert lowongan</h2><span class="text-sm text-graphite-500">{{ $alerts->count() }}/{{ $alertLimit }} alert</span></div>
            <p class="text-sm text-graphite-500" style="margin-bottom:1rem">Tiap pagi (08:00) kami cari lowongan 24 jam terakhir untuk kata kuncimu dan kirim yang paling cocok. Kosong bila tidak ada yang baru.</p>

            @foreach ($alerts as $al)
                <div class="ap-alert">
                    <div>
                        <b>{{ implode(', ', $al->keywords) }}</b>
                        <small>{{ $al->kind === 'magang' ? 'Magang' : 'Kerja' }} &middot; {{ $al->locations ? implode(', ', $al->locations) : 'Indonesia' }} &middot; via {{ $al->channel === 'telegram' ? 'Telegram' : 'Email' }}@if ($al->last_sent_at) &middot; terakhir {{ $al->last_sent_at->diffForHumans() }}@endif</small>
                    </div>
                    <div class="ap-row">
                        <form method="POST" action="{{ route('account.alerts.toggle', $al) }}">@csrf @method('PATCH')<button type="submit" @class(['ui-badge has-dot', 'is-success' => $al->is_active]) style="border:0;cursor:pointer">{{ $al->is_active ? 'Aktif' : 'Dijeda' }}</button></form>
                        <form method="POST" action="{{ route('account.alerts.destroy', $al) }}" data-confirm="Hapus alert ini?">@csrf @method('DELETE')<button type="submit" class="ui-act is-danger" title="Hapus" aria-label="Hapus alert"><x-icon name="trash" /></button></form>
                    </div>
                </div>
            @endforeach

            @if ($alerts->count() < $alertLimit)
                <form method="POST" action="{{ route('account.alerts.store') }}" class="ap-grid is-alert" style="margin-top:1rem">
                    @csrf
                    <div class="ui-field"><label for="al-kw">Kata kunci (pisahkan koma)</label><input id="al-kw" name="keywords" class="ui-input" maxlength="300" placeholder="mis. backend developer, laravel" value="{{ old('keywords') }}" required>@error('keywords')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    <div class="ui-field"><label for="al-loc">Lokasi (opsional)</label><input id="al-loc" name="locations" class="ui-input" maxlength="200" placeholder="mis. Pekanbaru, Remote" value="{{ old('locations') }}"></div>
                    <div class="ui-field"><label for="dd-al-kind">Jenis</label><x-dropdown name="kind" id="dd-al-kind" aria="Jenis" :options="['kerja' => 'Kerja', 'magang' => 'Magang']" :value="old('kind', 'kerja')" block /></div>
                    <div class="ui-field"><label for="dd-al-ch">Kirim lewat</label><x-dropdown name="channel" id="dd-al-ch" aria="Saluran" :options="['email' => 'Email']" :value="old('channel', 'email')" block />@error('channel')<small class="text-red-700">{{ $message }}</small>@enderror</div>
                    <div><button class="btn-primary" type="submit">Buat alert</button></div>
                </form>
                @unless ($telegramLinked)<p class="text-sm text-graphite-500" style="margin-top:.6rem">@if ($telegramOpen)Mau lewat Telegram? Hubungkan dulu di <a href="{{ route('account.plan') }}" class="ui-link">Rencana belajar</a>.@else Alert lewat Telegram tersedia untuk pengguna Premium.@endif</p>@endunless
            @endif
        </section>
    </div>
@endsection
