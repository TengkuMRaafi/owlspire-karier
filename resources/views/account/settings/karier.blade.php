<section id="karier" class="pn-card ac-set-sec" aria-labelledby="h-karier">
    <header class="ac-set-h">
        <span class="ac-set-ic" aria-hidden="true"><x-icon name="target" /></span>
        <div><h2 id="h-karier">Karier target</h2><p>Dasar skor kesiapan di Dashboard dan konteks untuk Asisten AI.</p></div>
    </header>

    <form method="POST" action="{{ route('account.settings.career') }}" class="ac-set-form">
        @csrf @method('PUT')
        <div class="ui-field">
            <span class="ui-label" id="l-career">Karier yang kamu tuju</span>
            <x-dropdown name="career_id" :options="$careers" :value="old('career_id', $user->target_career_id)" all="Belum dipilih" aria="Karier yang kamu tuju" block />
            <small>Kamu juga bisa memilihnya langsung dari katalog di menu Karier saya.</small>
        </div>
        <div class="ac-set-foot"><button class="btn-primary" type="submit">Simpan karier</button></div>
    </form>
</section>
