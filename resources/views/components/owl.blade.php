@props(['animated' => false, 'draw' => false])

{{-- Maskot Owlspire (vektor). animated: kedip, mata melirik, telinga bergerak. draw: garis tergambar saat muncul. --}}
<svg {{ $attributes->class(['ow', 'ow-live' => $animated, 'ow-draw' => $draw]) }} viewBox="0 0 120 140" role="img" aria-label="Owlspire">
    <g class="ow-body">
        <path class="ow-shape" d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136 14 114Q8 108 8 100V72Q8 60 18 54L4 22 36 32Z" fill="#4a7fdc"/>
        <path d="M60 4 84 32 60 52 36 32Z" fill="#3b6cd4"/>
        <path d="M60 4 84 32 116 22 102 54Q112 60 112 72V100Q112 108 106 114L60 136Z" fill="#a9c4f2" opacity=".55"/>
        <path class="ow-tuft ow-tuft-l" d="M4 22 36 32 22 52Q14 54 14 60Z" fill="#2f58b3"/>
        <path class="ow-tuft ow-tuft-r" d="M116 22 84 32 98 52Q106 54 106 60Z" fill="#2f58b3"/>
        <path class="ow-arrow" d="M60 46V16M50 26 60 14 70 26" fill="none" stroke="#2f58b3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M14 56Q36 52 54 64V70Q34 62 14 62Z" fill="#2f58b3"/>
        <path d="M106 56Q84 52 66 64V70Q86 62 106 62Z" fill="#2f58b3"/>
        <g class="ow-eye"><circle cx="36" cy="76" r="17" fill="#fff"/><circle cx="84" cy="76" r="17" fill="#fff"/></g>
        <g class="ow-iris"><circle cx="36" cy="76" r="10.5" fill="#f0b429"/><circle cx="84" cy="76" r="10.5" fill="#f0b429"/>
            <g class="ow-pupil"><circle cx="36" cy="76" r="5.8" fill="#1f2a3d"/><circle cx="84" cy="76" r="5.8" fill="#1f2a3d"/><circle cx="38.4" cy="73.4" r="2" fill="#fff"/><circle cx="86.4" cy="73.4" r="2" fill="#fff"/></g>
        </g>
        <path class="ow-beak" d="M54 88H66L60 100Z" fill="#f5b942"/>
        <path class="ow-chest" d="M48 112 60 102 72 112M48 120 60 110 72 120" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
    </g>
</svg>
