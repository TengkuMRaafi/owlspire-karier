@extends('layouts.dashboard')

@section('title', 'Asisten AI')
@section('heading', 'Asisten AI')
@section('subheading', 'Tanya soal belajar, tes seleksi, dan arah kariermu.')

@section('actions')
    <a href="{{ route('account.chat.days') }}" class="btn-outline btn-sm">Riwayat per hari</a>
    @if ($messages->isNotEmpty())
        <form method="POST" action="{{ route('account.chat.clear') }}" data-confirm="Hapus percakapan hari ini? Riwayat hari-hari lain tidak ikut terhapus.">
            @csrf @method('DELETE')
            <button type="submit" class="btn-outline btn-sm">Hapus hari ini</button>
        </form>
    @endif
@endsection

@section('content')
    @php
        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1));
        $first = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');
        $pct = $limit > 0 ? max(0, min(100, round($remaining / $limit * 100))) : 0;
        $locked = ! $enabled || $remaining <= 0;
    @endphp

    {{-- Sumber ikon untuk JS: maskot untuk jawaban asisten, ikon kirim untuk tombol --}}
    <template data-chat-owl><x-owl /></template>

    <div class="cb" data-chat data-url="{{ route('account.chat.send') }}" data-max="{{ $maxChars }}" data-limit="{{ $limit }}" data-initial="{{ $initial }}">
        <div class="cb-quota">
            <div class="cb-quota-l">
                <span>Sisa pesan hari ini</span>
                <b><span data-chat-left>{{ $remaining }}</span> <small>dari {{ $limit }}</small></b>
            </div>
            @if ($premium)
                <span class="cb-plan"><x-icon name="crown" /> Premium</span>
            @else
                <a href="{{ route('account.subscription') }}" class="cb-plan">Gratis · Naik ke Premium</a>
            @endif
            <span class="cb-bar" aria-hidden="true"><i data-chat-bar style="width: {{ $pct }}%"></i></span>
        </div>

        @unless ($enabled)
            <div class="cb-off">Asisten AI belum diaktifkan. Admin perlu mengisi <code>GROQ_API_KEY</code> di <code>.env</code>.</div>
        @endunless

        <x-quota-lock :locked="$enabled && $remaining <= 0" :used="$limit - $remaining" :limit="$limit" :premium="$premium" radius="1.4rem"
            title="Jatah pesan hari ini habis"
            :lead="$premium ? 'Kamu sudah memakai seluruh '.$limit.' pesan paket Premium hari ini. Jatah terisi lagi otomatis saat hari berganti.' : 'Kamu sudah memakai '.$limit.' pesan gratis hari ini. Jatah terisi lagi otomatis, atau kirim lebih banyak pesan tiap hari dengan Premium.'"
            keep="Percakapan hari ini tetap tersimpan dan riwayat per hari tetap bisa dibuka.">
        <div class="cb-card">
            <div class="cb-head {{ $enabled ? '' : 'is-off' }}">
                <span class="cb-head-av" aria-hidden="true"><x-owl /></span>
                <div><b>Owlspire</b><small>{{ $enabled ? 'Asisten belajar dan karier' : 'Belum aktif' }}</small></div>
            </div>
            <div class="cb-log" data-chat-log aria-live="polite">
                @forelse ($messages as $m)
                    <div class="cb-row {{ $m->role === 'user' ? 'is-user' : 'is-bot' }}">
                        <span class="cb-av" aria-hidden="true">@if ($m->role === 'user'){{ $initial }}@else<x-owl />@endif</span>
                        <div class="cb-msg">{{ $m->content }}</div>
                    </div>
                @empty
                    <div class="cb-empty" data-chat-empty>
                        <span class="cb-hero" aria-hidden="true"><x-owl /></span>
                        <h2 data-chat-greet data-name="{{ $first }}">Halo, {{ $first }}!</h2>
                        <p>Aku bisa bantu soal strategi belajar, tes CPNS, BUMN, Polri/TNI, dan arah kariermu.</p>
                        <div class="cb-chips">
                            @foreach (['Bagaimana cara belajar TWK untuk SKD CPNS?', 'Skill apa yang perlu aku kuasai untuk jadi Data Analyst?', 'Bagaimana menyusun jadwal belajar 2 minggu?'] as $s)
                                <button type="button" class="cb-chip" data-chat-suggest><x-icon name="sparkle" />{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforelse
            </div>

            <form class="cb-form" data-chat-form autocomplete="off">
                <div class="cb-box">
                    <textarea data-chat-input maxlength="{{ $maxChars }}" rows="1" placeholder="Tulis pertanyaanmu..." aria-label="Pesan" @disabled($locked)></textarea>
                    <button type="submit" class="cb-send" data-chat-send aria-label="Kirim" @disabled($locked)><x-icon name="arrow-right" /></button>
                </div>
                <div class="cb-meta"><span>Enter kirim · Shift+Enter baris baru</span><span data-chat-count>0/{{ $maxChars }}</span></div>
            </form>
        </div>
        </x-quota-lock>

        <p class="cb-note">Percakapan dimulai baru tiap hari; chat hari sebelumnya ada di <a href="{{ route('account.chat.days') }}">Riwayat per hari</a>. Jawaban AI bisa keliru, untuk jadwal dan formasi seleksi cek sumber resmi.</p>
    </div>

    <script>
        (function () {
            const root = document.querySelector('[data-chat]');
            if (!root) return;
            const log = root.querySelector('[data-chat-log]');
            const form = root.querySelector('[data-chat-form]');
            const input = root.querySelector('[data-chat-input]');
            const send = root.querySelector('[data-chat-send]');
            const left = root.querySelector('[data-chat-left]');
            const bar = root.querySelector('[data-chat-bar]');
            const count = root.querySelector('[data-chat-count]');
            const owl = document.querySelector('[data-chat-owl]');
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const limit = parseInt(root.dataset.limit, 10) || 0;
            const lockBox = root.querySelector('[data-quota-lock]');
            const greet = root.querySelector('[data-chat-greet]');
            let busy = false;

            // Sapaan sesuai jam di perangkat pengguna (zona waktu server UTC, jadi jangan dihitung di server).
            if (greet) {
                const h = new Date().getHours();
                const part = h >= 5 && h < 11 ? 'pagi' : h >= 11 && h < 15 ? 'siang' : h >= 15 && h < 18 ? 'sore' : 'malam';
                greet.textContent = 'Selamat ' + part + ', ' + greet.dataset.name + '!';
            }

            // Jatah habis: bagian chat dikunci langsung (tanpa muat ulang) oleh komponen quota-lock.
            const lockChat = () => lockBox?.dispatchEvent(new CustomEvent('quota:lock', { detail: { used: limit, limit } }));

            const toBottom = () => { log.scrollTop = log.scrollHeight; };
            const fit = () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 128) + 'px'; };
            const setLeft = (n) => {
                left.textContent = n;
                if (bar && limit > 0) bar.style.width = Math.max(0, Math.min(100, (n / limit) * 100)) + '%';
            };

            // Satu baris pesan: avatar (maskot untuk asisten, inisial untuk pengguna) + gelembung.
            const add = (role, text, extra) => {
                root.querySelector('[data-chat-empty]')?.remove();
                const row = document.createElement('div');
                row.className = 'cb-row ' + (role === 'user' ? 'is-user' : 'is-bot');
                const av = document.createElement('span');
                av.className = 'cb-av';
                av.setAttribute('aria-hidden', 'true');
                if (role === 'user') av.textContent = root.dataset.initial;
                else av.appendChild(owl.content.cloneNode(true));
                const bubble = document.createElement('div');
                bubble.className = 'cb-msg' + (extra ? ' ' + extra : '');
                if (text) bubble.textContent = text; // textContent: jawaban model tidak pernah dianggap HTML
                row.append(av, bubble);
                log.appendChild(row);
                toBottom();
                return bubble;
            };
            const lock = (off) => { input.disabled = off; send.disabled = off; };

            input.addEventListener('input', () => { count.textContent = input.value.length + '/' + root.dataset.max; fit(); });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
            });
            root.querySelectorAll('[data-chat-suggest]').forEach((b) => b.addEventListener('click', () => {
                input.value = b.textContent.trim();
                form.requestSubmit();
            }));

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = input.value.trim();
                if (!text || busy || input.disabled) return;
                busy = true;
                add('user', text);
                input.value = '';
                fit();
                count.textContent = '0/' + root.dataset.max;
                lock(true);

                const wait = add('bot', '');
                wait.innerHTML = '<span class="cb-dots" aria-label="Asisten sedang mengetik"><i></i><i></i><i></i></span>';

                try {
                    const res = await fetch(root.dataset.url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ message: text }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (typeof data.remaining === 'number') setLeft(data.remaining);
                    if (res.ok) {
                        wait.textContent = data.answer;
                    } else {
                        wait.classList.add('is-err');
                        wait.textContent = data.message || (res.status === 422 ? 'Pesan tidak valid atau terlalu panjang.' : 'Terjadi kesalahan. Coba lagi.');
                        if (res.status !== 429) { input.value = text; fit(); } // kembalikan teks agar bisa dikirim ulang
                    }
                } catch (err) {
                    wait.classList.add('is-err');
                    wait.textContent = 'Koneksi bermasalah. Coba kirim ulang.';
                    input.value = text;
                    fit();
                }

                busy = false;
                const out = (parseInt(left.textContent, 10) || 0) <= 0;
                lock(out);
                if (!out) input.focus(); else lockChat();
                toBottom();
            });

            toBottom();
        })();
    </script>
@endsection