{{-- Banner paket: Gratis (terang) atau Premium (gelap-oranye). --}}
@php $days = $premiumUntil ? max(0, (int) now()->diffInDays($premiumUntil, false)) : null; @endphp
@php $internal = $internal ?? false; @endphp
<div @class(['ac-plan', 'is-premium' => $premium || $internal, 'is-compact' => $compact ?? false])>
    <div>
        <p class="ac-plan-eyebrow">{{ $internal ? 'Peran kamu' : 'Paket kamu' }}</p>
        <p class="ac-plan-name"><x-icon :name="$internal ? 'shield' : ($premium ? 'crown' : 'sparkle')" /> {{ $internal ? auth()->user()->roleLabel() : ($premium ? 'Premium' : 'Gratis') }}</p>
        <p class="ac-plan-desc">
            @if ($internal)
                Akses penuh ke semua fitur pengguna. Akun {{ mb_strtolower(auth()->user()->roleLabel()) }} tidak memakai paket Gratis atau Premium.
            @elseif ($compact ?? false)
                @if ($premium)
                    {{ $premiumUntil ? 'Aktif sampai '.$premiumUntil->translatedFormat('d M Y').'.' : 'Aktif tanpa batas waktu.' }}
                @else
                    Simulasi terbuka lewat tiket: <b>{{ $tickets }}</b>. Ujian penuh dan analisis CV terbuka di Premium.
                @endif
            @elseif ($premium)
                {{ $premiumUntil ? 'Aktif sampai '.$premiumUntil->translatedFormat('d M Y').'.' : 'Aktif tanpa batas waktu.' }} Ujian penuh tanpa batas, analisis CV, dan semua topik belajar terbuka.
            @else
                Simulasi karier mode coba, tes arah karier, skor kesiapan, dan dua topik pertama tiap jalur belajar. Simulasi terbuka lewat tiket: <b>{{ $tickets }}</b>.
            @endif
        </p>
        @if ($cta ?? true)
            <div class="ac-plan-cta" @unless ($compact ?? false) style="margin-top:1rem" @endunless>
                @if ($internal)
                    <a href="{{ route('admin.dashboard') }}" class="btn-primary">Buka panel</a>
                @elseif ($premium)
                    <a href="{{ route('cv.index') }}" class="btn-primary">Analisis CV</a>
                    <a href="{{ route('account.subscription') }}" class="btn-outline-light">Kelola langganan</a>
                @else
                    <a href="{{ route('account.subscription') }}" class="btn-primary">Naik ke Premium</a>
                    <a href="{{ route('account.exams') }}" class="btn-outline">Coba simulasi gratis</a>
                @endif
            </div>
        @endif
    </div>
    @if ($premium && $days !== null && ! ($compact ?? false))
        <div class="ac-ring" style="--p: {{ min(100, round($days / 30 * 100)) }}" role="img" aria-label="Sisa {{ $days }} hari"><b>{{ $days }}</b><small>hari lagi</small></div>
    @endif
</div>
