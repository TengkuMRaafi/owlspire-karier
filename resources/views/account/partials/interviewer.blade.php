{{-- Karakter pewawancara beranimasi, tiap orang punya tampilan sendiri.
     Param: $gender ('f' | 'm'), $mood (listening | thinking | smile | talking), $emo (neutral | happy | doubt | annoyed | angry),
     $crop (true = potong ke wajah untuk avatar kecil), $look (opsional, tampilan khusus orang itu):
       skin, hair (warna), style (f: bob | long | bun | hijab; m: short | cepak | side | curly), outfit (suit | sweater | tni | polri | pns),
       suit, suit2 (warna baju dan kerah), accent (dasi atau lencana), hijab (warna), glasses (warna bingkai), beard (none | mustache | full), jewel.
     JS mengganti kelas is-* dan atribut data-emo pada .ivh. --}}
@php
    $m = ($gender ?? 'f') === 'm';
    $L = array_merge([
        'skin' => $m ? '#e2b08c' : '#f3c8a8', 'hair' => $m ? '#1f1b24' : '#2a1d22', 'style' => $m ? 'short' : 'bob', 'outfit' => 'suit',
        'suit' => $m ? '#2b3a4a' : '#7a2e4d', 'suit2' => $m ? '#1b2638' : '#5c2039', 'accent' => $m ? '#2f58b3' : '#f5c542',
        'hijab' => null, 'glasses' => null, 'beard' => 'none', 'jewel' => ! $m,
    ], $look ?? []);
    $shade = function (string $hex, float $k): string {
        $c = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        return sprintf('#%02x%02x%02x', ...array_map(fn ($v) => max(0, min(255, (int) round($v * $k))), $c));
    };
    $skin = $L['skin']; $hair = $L['hair']; $neck = $shade($skin, .93); $cheek = $shade($skin, .93);
    $lip = $m ? '#a5524a' : '#c2485a';
    $st = $L['style']; $fit = $L['outfit']; $uni = in_array($fit, ['tni', 'polri', 'pns'], true);
    $emo = $emo ?? 'neutral';
@endphp
@once
    <style>
        .ivh { width: 100%; height: 100%; overflow: visible; }
        .ivh .head { transform-box: fill-box; transform-origin: 50% 100%; animation: ivh-idle 5s ease-in-out infinite; }
        .ivh .lid { transform-box: fill-box; transform-origin: 50% 0; transform: scaleY(0); animation: ivh-blink 4.2s infinite; }
        .ivh .mo, .ivh .br-a1, .ivh .br-a2, .ivh .ang { display: none; } .ivh .mo-n { display: block; }
        .ivh.is-smile .mo-n, .ivh.is-talking .mo-n, .ivh.is-thinking .mo-n { display: none; }
        .ivh.is-smile .mo-s, .ivh.is-thinking .mo-k { display: block; }
        .ivh.is-talking .mo-t { display: block; transform-box: fill-box; transform-origin: 50% 50%; animation: ivh-talk .36s ease-in-out infinite alternate; }
        .ivh .pupil { transition: transform .4s; } .ivh.is-thinking .pupil { transform: translate(4px, -4px); }
        .ivh .br { transition: transform .4s; transform-box: fill-box; } .ivh.is-thinking .br { transform: translateY(-5px) rotate(-5deg); }
        .ivh .chk { opacity: 0; transition: opacity .4s; } .ivh.is-smile .chk { opacity: .6; }
        .ivh.is-listening .head { animation: ivh-nod 2.4s ease-in-out infinite; }
        .ivh.is-smile .head { animation: ivh-nod 1.1s ease-in-out 2; }
        .ivh .th { opacity: 0; transition: opacity .3s; } .ivh.is-thinking .th { opacity: 1; }
        .ivh.is-thinking .th circle { animation: ivh-pop 1.1s infinite; }
        .ivh.is-thinking .th circle:nth-child(2) { animation-delay: .2s; } .ivh.is-thinking .th circle:nth-child(3) { animation-delay: .4s; }

        /* Emosi: alis, kelopak, pipi, dan mulut berubah. Mulut bicara (is-talking) tetap dipakai saat berbicara. */
        .ivh[data-emo='doubt'] .br { transform: translateY(-4px) rotate(-8deg); }
        .ivh[data-emo='annoyed'] .br, .ivh[data-emo='angry'] .br { display: none; }
        .ivh[data-emo='annoyed'] .br-a1, .ivh[data-emo='angry'] .br-a2 { display: block; }
        .ivh .sq1, .ivh .sq2 { display: none; }
        .ivh[data-emo='annoyed'] .lid, .ivh[data-emo='angry'] .lid { animation: none; }
        .ivh[data-emo='annoyed'] .sq1, .ivh[data-emo='angry'] .sq2 { display: block; }
        .ivh[data-emo='annoyed'] .chk { opacity: .35; } .ivh[data-emo='angry'] .chk { opacity: .8; }
        .ivh[data-emo='angry'] .ang { display: block; transform-box: fill-box; transform-origin: 50% 50%; animation: ivh-pop .8s infinite; }
        .ivh[data-emo='annoyed'] .head { animation: none; transform: rotate(-3deg); }
        .ivh[data-emo='angry'] .head { animation: ivh-shake .45s ease-in-out infinite; }
        .ivh[data-emo='doubt']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-n,
        .ivh[data-emo='annoyed']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-n,
        .ivh[data-emo='angry']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-n { display: none; }
        .ivh[data-emo='doubt']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-d,
        .ivh[data-emo='annoyed']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-f,
        .ivh[data-emo='angry']:not(.is-talking):not(.is-thinking):not(.is-smile) .mo-g { display: block; }

        @keyframes ivh-idle { 50% { transform: translateY(-3px) rotate(.8deg); } }
        @keyframes ivh-nod { 40% { transform: rotate(-2.5deg) translateY(2px); } }
        @keyframes ivh-blink { 0%, 94%, 100% { transform: scaleY(0); } 97% { transform: scaleY(1); } }
        @keyframes ivh-talk { to { transform: scaleY(.3); } }
        @keyframes ivh-pop { 50% { transform: translateY(-5px); opacity: .5; } }
        @keyframes ivh-shake { 25% { transform: translateX(-2px) rotate(-1deg); } 75% { transform: translateX(2px) rotate(1deg); } }
        @media (prefers-reduced-motion: reduce) { .ivh * { animation: none !important; } }
    </style>
@endonce
<svg class="ivh is-{{ $mood ?? 'listening' }}" data-emo="{{ $emo }}" data-gender="{{ $m ? 'm' : 'f' }}" viewBox="{{ ($crop ?? false) ? '34 8 172 186' : '0 0 240 260' }}" role="img" aria-label="{{ $label ?? 'Pewawancara' }}">
    <g>
        @if ($uni)
            {{-- seragam: kerah, saku, tanda pangkat di bahu --}}
            <path d="M18 260c2-56 44-76 102-76s100 20 102 76z" fill="{{ $L['suit'] }}"/>
            <path d="M92 185l28 36-24 10-22-28zM148 185l-28 36 24 10 22-28z" fill="{{ $L['suit2'] }}"/>
            <path d="M120 222v38" stroke="{{ $L['suit2'] }}" stroke-width="2.5"/>
            <rect x="56" y="228" width="38" height="26" rx="3" fill="{{ $L['suit2'] }}"/><rect x="146" y="228" width="38" height="26" rx="3" fill="{{ $L['suit2'] }}"/>
            <path d="M32 216l30-16 7 13-31 16z" fill="{{ $L['accent'] }}"/><path d="M208 216l-30-16-7 13 31 16z" fill="{{ $L['accent'] }}"/>
            <path d="M44 211l17-9M47 218l17-9M196 211l-17-9M193 218l-17-9" stroke="{{ $L['suit2'] }}" stroke-width="1.6" stroke-linecap="round"/>
            <rect x="152" y="234" width="26" height="6" rx="1.5" fill="#f2efe6"/>
            @if ($fit === 'tni')
                <path d="M64 236h22M64 241h22M64 246h22" stroke="#d8b24a" stroke-width="2.4" stroke-linecap="round"/><path d="M64 236h22" stroke="#c8312f" stroke-width="2.4" stroke-linecap="round"/>
            @elseif ($fit === 'polri')
                <circle cx="75" cy="241" r="7" fill="#e3c25a"/><path d="m75 235 2 4 4 .5-3 3 .8 4-3.8-2-3.8 2 .8-4-3-3 4-.5z" fill="#7a5636"/>
            @else
                <circle cx="75" cy="241" r="7" fill="#e3c25a"/><circle cx="75" cy="241" r="3.6" fill="#1e4fa0"/>
            @endif
        @elseif ($fit === 'sweater')
            <path d="M18 260c2-56 44-76 102-76s100 20 102 76z" fill="{{ $L['suit'] }}"/>
            <path d="M88 186q32 34 64 0l-6-6q-26 18-52 0z" fill="{{ $L['suit2'] }}"/>
            <path d="M52 214q-6 24-4 46M188 214q6 24 4 46" stroke="{{ $L['suit2'] }}" stroke-width="2" fill="none" opacity=".5"/>
        @else
            <path d="M18 260c2-56 44-76 102-76s100 20 102 76z" fill="{{ $L['suit'] }}"/>
            @if ($m)
                <path d="M96 186l24 36 24-36z" fill="#fff"/>
                <path d="M90 186l30 40-22 34H66c2-30 10-56 24-74zM150 186l-30 40 22 34h32c-2-30-10-56-24-74z" fill="{{ $L['suit2'] }}"/>
                <path d="M113 190h14l5 30-12 12-12-12z" fill="{{ $L['accent'] }}"/>
            @else
                <path d="M92 186q28 52 56 0z" fill="#f6e9ee"/>
                <path d="M90 186l30 52-26 22H64c2-30 10-56 26-74zM150 186l-30 52 26 22h30c-2-30-10-56-26-74z" fill="{{ $L['suit2'] }}"/>
                @if ($L['jewel'])<circle cx="120" cy="214" r="4" fill="{{ $L['accent'] }}"/><path d="M104 192q16 30 32 0" stroke="{{ $L['accent'] }}" stroke-width="2" fill="none"/>@endif
            @endif
        @endif
    </g>
    <g class="head">
        @if ($st === 'hijab')
            {{-- hijab: kain jatuh di leher dan dada, wajah di depannya --}}
            <path d="M44 156C36 76 72 28 120 28s84 48 76 128c-2 34-10 66-26 92H70c-16-26-24-58-26-92z" fill="{{ $L['hijab'] ?? '#5f7ea3' }}"/>
            <path d="M62 140c-2 46 12 100 58 100s60-54 58-100z" fill="{{ $L['hijab'] ?? '#5f7ea3' }}"/>
            <path d="M92 204q28 16 56 0M104 220q16 8 32 0" stroke="{{ $shade($L['hijab'] ?? '#5f7ea3', .8) }}" stroke-width="2.4" fill="none" stroke-linecap="round"/>
        @else
            <rect x="104" y="150" width="32" height="46" rx="12" fill="{{ $neck }}"/>
        @endif
        @if ($m)
            @if ($st === 'curly')
                <g fill="{{ $hair }}"><circle cx="80" cy="76" r="24"/><circle cx="106" cy="56" r="26"/><circle cx="136" cy="56" r="26"/><circle cx="162" cy="78" r="24"/><circle cx="68" cy="104" r="16"/><circle cx="172" cy="104" r="16"/></g>
            @endif
            <ellipse cx="63" cy="130" rx="7" ry="11" fill="{{ $skin }}"/><ellipse cx="177" cy="130" rx="7" ry="11" fill="{{ $skin }}"/>
            <path d="M62 118c0-38 24-60 58-60s58 22 58 60c0 20-4 36-14 48-10 12-26 18-44 18s-34-6-44-18c-10-12-14-28-14-48z" fill="{{ $skin }}"/>
            <path d="M68 142c6 28 24 40 52 40s46-12 52-40c-8 12-24 20-52 20s-44-8-52-20z" fill="#3b2f2a" opacity=".2"/>
            @if ($st === 'cepak')
                <path d="M64 116c0-40 22-62 56-62s56 22 56 62c-12-16-32-24-56-24s-44 8-56 24z" fill="{{ $hair }}"/>
            @elseif ($st === 'side')
                <path d="M60 120c-4-46 20-76 62-76 38 0 60 28 56 76-4-16-12-26-26-32-22 8-48 4-68 10-12 4-18 12-24 22z" fill="{{ $hair }}"/>
                <path d="M96 50c-14 6-26 16-32 32" stroke="#4a4152" stroke-width="3" fill="none" stroke-linecap="round" opacity=".6"/>
            @elseif ($st === 'curly')
                <path d="M66 112c10-14 30-22 54-22s44 8 54 22c-4-22-26-34-54-34s-50 12-54 34z" fill="{{ $hair }}"/>
            @else
                <path d="M62 116c0-42 24-68 58-68s58 26 58 68c-14-18-34-26-58-26s-44 8-58 26z" fill="{{ $hair }}"/>
                <path d="M104 52c-16 4-30 14-38 30" stroke="#3a3342" stroke-width="3" fill="none" stroke-linecap="round" opacity=".6"/>
            @endif
            @if ($L['beard'] === 'full')
                <path d="M64 140c4 34 24 52 56 52s52-18 56-52c-8 10-14 14-22 16-8-6-24-8-34-8s-26 2-34 8c-8-2-14-6-22-16z" fill="{{ $hair }}"/>
            @elseif ($L['beard'] === 'mustache')
                <path d="M102 152q8-6 18-2 10-4 18 2-6 9-18 5-12 4-18-5z" fill="{{ $hair }}"/>
            @endif
        @else
            @if ($st === 'long')
                <path d="M46 134C38 62 76 30 120 30s82 32 74 104c-2 44 2 94 16 126H30c14-32 18-82 16-126z" fill="{{ $hair }}"/>
            @elseif ($st === 'bun')
                <circle cx="120" cy="34" r="21" fill="{{ $hair }}"/>
            @elseif ($st === 'bob')
                <path d="M52 132C44 66 78 34 120 34s76 32 68 98c-2 30-8 62-22 84H74c-14-22-20-54-22-84z" fill="{{ $hair }}"/>
                <path d="M50 134c-6 34-2 70 14 92l16-8c-10-26-12-54-10-84zM190 134c6 34 2 70-14 92l-16-8c10-26 12-54 10-84z" fill="{{ $hair }}"/>
            @endif
            <path d="M68 120c0-36 22-60 52-60s52 24 52 60c0 34-20 62-52 62s-52-28-52-62z" fill="{{ $skin }}"/>
            @if ($st === 'hijab')
                <path d="M62 118c2-42 26-62 58-62s56 20 58 62c-14-20-32-30-58-30s-44 10-58 30z" fill="{{ $L['hijab'] ?? '#5f7ea3' }}"/>
                <path d="M66 122c12-18 28-26 54-26s42 8 54 26" stroke="{{ $shade($L['hijab'] ?? '#5f7ea3', .78) }}" stroke-width="2.2" fill="none" stroke-linecap="round"/>
            @elseif ($st === 'bun')
                <path d="M62 118c0-40 24-66 58-66s58 26 58 66c-10-22-30-34-58-34s-48 12-58 34z" fill="{{ $hair }}"/>
                <path d="M96 56c-12 6-22 16-28 30" stroke="#ffffff" stroke-width="2.4" fill="none" stroke-linecap="round" opacity=".18"/>
                @if ($L['jewel'])<circle cx="68" cy="152" r="3.6" fill="{{ $L['accent'] }}"/><circle cx="172" cy="152" r="3.6" fill="{{ $L['accent'] }}"/>@endif
            @else
                <path d="M62 114c8-36 30-54 58-54 28 0 50 18 58 54-22-8-38-26-46-40-10 24-34 38-70 40z" fill="{{ $hair }}"/>
                @if ($L['jewel'])<circle cx="68" cy="152" r="4" fill="{{ $L['accent'] }}"/><circle cx="172" cy="152" r="4" fill="{{ $L['accent'] }}"/>@endif
            @endif
        @endif
        <ellipse cx="98" cy="126" rx="11" ry="8" fill="#fff"/><ellipse cx="142" cy="126" rx="11" ry="8" fill="#fff"/>
        <circle class="pupil" cx="98" cy="126" r="5" fill="#2b2b36"/><circle class="pupil" cx="142" cy="126" r="5" fill="#2b2b36"/>
        @unless ($m)<path d="M87 122l-6-4M89 119l-3-5M109 121l3-4M153 122l6-4M151 119l3-5M131 121l-3-4" stroke="#2b2b36" stroke-width="2.4" stroke-linecap="round"/>@endunless
        <rect class="lid" x="86" y="116" width="24" height="20" fill="{{ $skin }}"/><rect class="lid" x="130" y="116" width="24" height="20" fill="{{ $skin }}"/>
        <path class="sq1" d="M86 116h24v8h-24zM130 116h24v8h-24z" fill="{{ $skin }}"/>
        <path class="sq2" d="M85 115h26v11zM155 115h-26v11z" fill="{{ $skin }}"/>
        <path class="br" d="{{ $m ? 'M86 111h24M130 111h24' : 'M86 112q12-9 24-2M130 110q12-7 24 2' }}" stroke="{{ $hair }}" stroke-width="{{ $m ? 6 : 3.2 }}" fill="none" stroke-linecap="round"/>
        <path class="br-a1" d="M85 106l25 6M155 106l-25 6" stroke="{{ $hair }}" stroke-width="{{ $m ? 6 : 3.6 }}" fill="none" stroke-linecap="round"/>
        <path class="br-a2" d="M83 101l27 12M157 101l-27 12" stroke="{{ $hair }}" stroke-width="{{ $m ? 6.5 : 4 }}" fill="none" stroke-linecap="round"/>
        @if ($L['glasses'])
            <g fill="none" stroke="{{ $L['glasses'] }}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"><rect x="82" y="112" width="32" height="25" rx="{{ $m ? 6 : 11 }}"/><rect x="126" y="112" width="32" height="25" rx="{{ $m ? 6 : 11 }}"/><path d="M114 123h12M82 120l-13-3M158 120l13-3"/></g>
        @endif
        <path d="M120 132q-5 12 0 15" stroke="{{ $shade($skin, .85) }}" stroke-width="3" fill="none" stroke-linecap="round"/>
        <circle class="chk" cx="84" cy="148" r="9" fill="#f08a8a"/><circle class="chk" cx="156" cy="148" r="9" fill="#f08a8a"/>
        <path class="mo mo-n" d="M108 163q12 4 24 0" stroke="{{ $lip }}" stroke-width="{{ $m ? 4 : 5 }}" fill="none" stroke-linecap="round"/>
        <path class="mo mo-s" d="M103 158q17 20 34 0z" fill="#fff" stroke="{{ $lip }}" stroke-width="4" stroke-linejoin="round"/>
        <ellipse class="mo mo-t" cx="120" cy="164" rx="9" ry="7" fill="#7a2f2f"/>
        <path class="mo mo-k" d="M112 165h16" stroke="{{ $lip }}" stroke-width="4" stroke-linecap="round"/>
        <path class="mo mo-d" d="M110 168l14-4" stroke="{{ $lip }}" stroke-width="4" stroke-linecap="round"/>
        <path class="mo mo-f" d="M108 170q12-8 24 0" stroke="{{ $lip }}" stroke-width="4" fill="none" stroke-linecap="round"/>
        <path class="mo mo-g" d="M105 172q15-14 30 0" stroke="{{ $lip }}" stroke-width="5" fill="none" stroke-linecap="round"/>
        <g class="ang" fill="none" stroke="#e5484d" stroke-width="4" stroke-linecap="round"><path d="M164 62q7 0 7 7M182 62q-7 0-7 7M164 82q7 0 7-7M182 82q-7 0-7-7"/></g>
    </g>
    <g class="th" fill="#fff"><circle cx="190" cy="60" r="5"/><circle cx="204" cy="44" r="7"/><circle cx="222" cy="24" r="9"/></g>
</svg>
