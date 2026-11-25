{{-- Chip perubahan peringkat. Butuh: $d (positif = naik). --}}
@if ($d > 0)
    <span class="tb-d up" title="Naik {{ $d }} peringkat">&#9650; {{ $d }}</span>
@elseif ($d < 0)
    <span class="tb-d down" title="Turun {{ abs($d) }} peringkat">&#9660; {{ abs($d) }}</span>
@else
    <span class="tb-d same" title="Tidak berubah">&ndash;</span>
@endif
