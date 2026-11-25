{{-- BANNER BERJALAN: dua pita miring yang saling silang, berjalan berlawanan arah. --}}
@php
    $mqNames = $careers->take(8)->pluck('name')->values();
    if ($mqNames->isEmpty()) {
        $mqNames = collect(['Data Analyst', 'UI/UX Designer', 'Software Engineer', 'Digital Marketer', 'Akuntan', 'HR Specialist']);
    }
    // Pita atas (oranye): ajakan. Pita bawah (gelap): nama karier.
    $mqTop = ['Kenali dirimu', 'Pilih kariermu', 'Uji lewat simulasi', 'Tutup celah skill', 'Naik level', 'Siap melamar'];
@endphp

<div class="mq" aria-hidden="true">
    <div class="mq-band mq-a">
        <div class="mq-track">
            @for ($r = 0; $r < 2; $r++)
                <div class="mq-set">
                    @foreach ($mqTop as $t)
                        <span class="mq-item">{{ $t }}</span><svg class="mq-star" viewBox="0 0 24 24"><path d="M12 2l2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4z"/></svg>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>

    <div class="mq-band mq-b">
        <div class="mq-track">
            @for ($r = 0; $r < 2; $r++)
                <div class="mq-set">
                    @foreach ($mqNames as $n)
                        <span class="mq-item mq-pill">{{ $n }}</span><span class="mq-dot"></span>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</div>
