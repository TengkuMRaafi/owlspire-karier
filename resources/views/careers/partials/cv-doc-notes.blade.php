@foreach ($notes as $note)
    <aside class="cvd-note is-{{ $note['t'] }}" data-note>
        <span class="cvp-ic"><x-icon :name="$icons[$note['t']]" /></span>
        <div class="cvp-nb">
            <b>{{ $note['h'] }}</b>
            <p>{{ $note['p'] }}</p>
            @if (! empty($note['r']))
                <div class="cvp-rw">
                    <div class="cvp-rw-h"><small>Contoh tulisan ulang</small><button type="button" class="cvp-copy" data-cv-copy>Salin</button></div>
                    <pre class="cvp-rw-t">{{ $note['r'] }}</pre>
                </div>
            @endif
        </div>
    </aside>
@endforeach
