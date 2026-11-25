{{-- Tab bersama untuk Analisis CV dan Perbaikan CV/Surat lamaran, supaya terasa satu menu. --}}
@php $on = request()->routeIs('account.cvtools*') ? 'ai' : 'analisis'; @endphp
<nav class="cvx-tabs" aria-label="Menu CV">
    <a href="{{ route('cv.index') }}" @class(['is-on' => $on === 'analisis']) @if ($on === 'analisis') aria-current="page" @endif>Analisis CV</a>
    <a href="{{ route('account.cvtools') }}" @class(['is-on' => $on === 'ai']) @if ($on === 'ai') aria-current="page" @endif><span class="cvx-long">Perbaikan CV dan surat lamaran (AI)</span><span class="cvx-short">Perbaikan CV (AI)</span></a>
</nav>
<style>
    .cvx-tabs{display:flex;gap:.25rem;margin:0 0 1.25rem;border-bottom:1px solid rgba(127,127,127,.25);flex-wrap:wrap}
    .cvx-tabs a{padding:.6rem .95rem;font-weight:600;font-size:.92rem;border-bottom:2px solid transparent;margin-bottom:-1px;color:inherit;opacity:.7;text-decoration:none}
    .cvx-tabs a.is-on{opacity:1;border-bottom-color:currentColor}
    .cvx-short{display:none}
    @media (max-width:639px){
        /* Mobile: dua tab sebaris, lebar sama, teks di tengah (tidak menumpuk atas-bawah) */
        .cvx-tabs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;margin-bottom:1rem;flex-wrap:nowrap}
        .cvx-tabs a{padding:.65rem .35rem;font-size:.86rem;line-height:1.25;text-align:center;overflow-wrap:anywhere}
        .cvx-long{display:none}
        .cvx-short{display:inline}
    }
</style>
