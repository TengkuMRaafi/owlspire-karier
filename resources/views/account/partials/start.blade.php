{{-- Tombol mulai satu jenis ujian di dalam akun: coba gratis + ujian penuh (tiket/Premium). --}}
@php
    $trialCount = $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count ?? $s->question_count_trial));
    $fullCount = \App\Services\Exam\FullExamPlan::total($type->segments);
    $avail = $tickets[$type->id] ?? 0;
    $isCareer = $type->kind === 'career';
    $perPass = (int) config('platform.exam.pass_attempts', 2);
    $limited = $avail > 0 && $avail < 100000;
    $confirmMsg = 'Tiket ini punya jatah '.$perPass.'x ujian penuh per simulasi. Memulai sekarang memakai 1 kesempatan (sisa '.$avail.', jadi '.($avail - 1).' setelahnya). Kerjakan dengan sungguh-sungguh di tempat yang tenang dan siapkan waktunya sampai selesai. Kalau jatah habis, simulasi ini terkunci lagi.';
@endphp
<div class="ac-item-foot">
    <form method="POST" action="{{ route('exam.start', $type) }}">
        @csrf
        <input type="hidden" name="mode" value="trial">
        <button type="submit" class="btn-outline btn-sm" @disabled($trialCount === 0)>Coba gratis &middot; {{ $trialCount }} soal</button>
    </form>
    @if ($avail > 0)
        <form method="POST" action="{{ route('exam.start', $type) }}" @if ($limited) data-confirm="{{ $confirmMsg }}" data-confirm-title="Pakai 1 kesempatan tiket?" data-confirm-ok="Ya, mulai" data-confirm-tone="warning" @endif>
            @csrf
            <input type="hidden" name="mode" value="full">
            <button type="submit" class="btn-primary btn-sm" @disabled($fullCount === 0)>{{ $isCareer ? 'Ujian lanjutan' : 'Ujian penuh' }} &middot; {{ $fullCount }} soal</button>
        </form>
    @else
        <a href="{{ route('billing.index', ['jenis' => $type->code]) }}#tiket" class="btn-dark btn-sm"><x-icon name="lock" class="size-3.5" /> {{ $isCareer ? 'Ujian lanjutan' : 'Ujian penuh' }}</a>
    @endif
</div>
@if ($avail > 0)
    <p class="ac-note"><x-icon name="check-circle" /> {{ $avail >= 100000 ? 'Premium aktif: tanpa batas.' : 'Tiket aktif: sisa '.$avail.' dari '.$perPass.'x kesempatan ujian penuh.' }}</p>
@endif
