{{-- Tombol mulai simulasi untuk satu jenis ujian: coba gratis, dan ujian penuh bila tiket/Premium aktif (atau tautan beli tiket sekali bayar). --}}
@php
    $trialCount = $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count ?? $s->question_count_trial));
    $dark = ($tone ?? 'light') === 'dark';
    $avail = ($tickets ?? [])[$type->id] ?? 0;
    $fullCount = \App\Services\Exam\FullExamPlan::total($type->segments);
    $price = (int) config('platform.exam.pass_price');
    $perPass = (int) config('platform.exam.pass_attempts', 2);
    $limited = $avail > 0 && $avail < 100000; // tiket (bukan Premium/staf): jatah terbatas
    $confirmMsg = 'Tiket ini punya jatah '.$perPass.'x ujian penuh per simulasi. Memulai sekarang memakai 1 kesempatan (sisa '.$avail.', jadi '.($avail - 1).' setelahnya). Kerjakan dengan sungguh-sungguh di tempat yang tenang dan siapkan waktunya sampai selesai. Kalau jatah habis, simulasi ini terkunci lagi.';
@endphp

@if (auth()->check() && auth()->user()->isAdmin())
    <a href="{{ route('admin.setup.index') }}" class="btn-outline w-full">Kelola jenis ujian</a>
@else
<form method="POST" action="{{ route('exam.start', $type) }}">
    @csrf
    <input type="hidden" name="mode" value="trial">
    <button type="submit" @disabled($trialCount === 0) class="btn-primary w-full justify-between py-3 disabled:opacity-50">
        <span>Mulai simulasi</span>
        <span class="text-xs font-semibold opacity-90">{{ $trialCount }} soal &middot; {{ (int) ($type->trial_duration_seconds / 60) }} menit</span>
    </button>
</form>
@if ($avail > 0)
    <form method="POST" action="{{ route('exam.start', $type) }}" class="mt-2" @if ($limited) data-confirm="{{ $confirmMsg }}" data-confirm-title="Pakai 1 kesempatan tiket?" data-confirm-ok="Ya, mulai" data-confirm-tone="warning" @endif>
        @csrf
        <input type="hidden" name="mode" value="full">
        <button type="submit" @disabled($fullCount === 0) class="btn-dark w-full justify-between py-3 disabled:opacity-50">
            <span>{{ $type->kind === 'career' ? 'Ujian lanjutan' : 'Ujian penuh' }}</span>
            <span class="text-xs font-semibold opacity-90">{{ $fullCount }} soal &middot; {{ (int) ($type->duration_seconds / 60) }} menit</span>
        </button>
    </form>
    @if ($limited)
        <p class="mt-1.5 text-center text-xs font-semibold opacity-80">Sisa {{ $avail }} dari {{ $perPass }}x kesempatan tiket</p>
    @endif
@else
    <a href="{{ route('billing.index', ['jenis' => $type->code]) }}#tiket" class="btn-outline mt-2 w-full justify-between py-3">
        <span>{{ $type->kind === 'career' ? 'Ujian lanjutan' : 'Ujian penuh' }}</span>
        <span class="text-xs font-semibold opacity-90">Rp{{ number_format($price, 0, ',', '.') }} untuk {{ $perPass }}x ujian</span>
    </a>
@endif
@endif
