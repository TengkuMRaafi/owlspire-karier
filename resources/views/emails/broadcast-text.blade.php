{!! $broadcast->title !!}

Halo, {!! $recipientName !!},

{!! implode("\n\n", $broadcast->paragraphs()) !!}
@if ($broadcast->kind === 'diskon' && filled($broadcast->highlight))

Kode promo: {!! $broadcast->highlight !!}
@endif
@if ($broadcast->kind !== 'umum' && $broadcast->detailLines())

@foreach ($broadcast->detailLines() as $line)
- {!! $line !!}
@endforeach
@endif
@if (filled($broadcast->cta_url) && filled($broadcast->cta_label))

{!! $broadcast->cta_label !!}: {!! $broadcast->cta_url !!}
@endif

--
{!! config('platform.brand.name') !!} - {!! config('platform.brand.tagline') !!}
Berhenti menerima info: {!! $unsubscribeUrl !!}
