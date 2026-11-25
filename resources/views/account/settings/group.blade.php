{{-- Panel preferensi satu menu; isi field dibaca dari config/preferences.php. --}}
@once
    <style>
        @media (max-width: 639px) {
            /* Mobile: pilihan segmen melebar sesuai panjang teks (bukan sama lebar), jadi teks panjang tidak patah jadi 3 baris */
            body .ac-seg label { flex: 1 1 auto; }
            body .ac-seg span { min-height: 2.5rem; padding: .25rem .7rem; font-size: .8rem; line-height: 1.25; }
        }
    </style>
@endonce
<section id="{{ $id }}" class="pn-card ac-set-sec" aria-labelledby="h-{{ $id }}">
    <header class="ac-set-h">
        <span class="ac-set-ic" aria-hidden="true"><x-icon :name="$group['icon']" /></span>
        <div>
            <h2 id="h-{{ $id }}">{{ $group['title'] }}</h2>
            <p>{{ $group['desc'] }}</p>
        </div>
    </header>

    <form method="POST" action="{{ route('account.settings.preferences', $id) }}" class="ac-set-form">
        @csrf @method('PUT')
        <div class="ac-opts">
            @foreach ($group['fields'] as $key => $field)
                @if ($field['type'] === 'bool')
                    <label class="ac-opt is-sw">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <span class="ac-opt-t"><b>{{ $field['label'] }}</b>@isset($field['hint'])<small>{{ $field['hint'] }}</small>@endisset</span>
                        <span class="ui-check ac-sw"><input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key])><i aria-hidden="true"></i></span>
                    </label>
                @elseif (count($field['options']) <= 3)
                    <div class="ac-opt" role="group" aria-labelledby="l-{{ $id }}-{{ $key }}">
                        <span class="ac-opt-t" id="l-{{ $id }}-{{ $key }}"><b>{{ $field['label'] }}</b>@isset($field['hint'])<small>{{ $field['hint'] }}</small>@endisset</span>
                        <div class="ac-seg">
                            @foreach ($field['options'] as $value => $text)
                                <label>
                                    <input type="radio" name="{{ $key }}" value="{{ $value }}" @checked((string) $values[$key] === (string) $value)>
                                    <span>{{ $text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="ac-opt">
                        <span class="ac-opt-t"><b>{{ $field['label'] }}</b>@isset($field['hint'])<small>{{ $field['hint'] }}</small>@endisset</span>
                        <x-dropdown :name="$key" :options="$field['options']" :value="$values[$key]" :aria="$field['label']" />
                    </div>
                @endif
            @endforeach
        </div>

        <div class="ac-set-foot">
            <button class="btn-primary" type="submit">Simpan</button>
            <button class="btn-outline" type="submit" name="reset" value="1" formnovalidate>Kembalikan bawaan</button>
        </div>
    </form>
</section>
