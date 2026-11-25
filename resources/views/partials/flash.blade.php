{{-- Pesan sesi (status, error, validasi). Default tampil sebagai toast; 'inline' => true untuk kartu di dalam halaman (mis. Masuk). --}}
@php $inline = $inline ?? false; $fbErr = ($showErrors ?? false) && ($errors ?? null)?->any(); @endphp
@if (session('status') || session('error') || $fbErr || ($inline && ($errors ?? null)?->any()))
    @if ($inline)
        <div class="{{ $class ?? 'ui-flash' }}">
            @if (session('status'))<x-alert type="success" dismiss autohide="7000">{{ session('status') }}</x-alert>@endif
            @if (session('error'))<x-alert type="error" dismiss>{{ session('error') }}</x-alert>@endif
        </div>
    @else
        <div class="fb-toasts" data-toasts>
            @if (session('status'))
                <div class="fb-toast is-success" role="status" data-toast-ms="6000"><span class="fb-toast-ic"><x-icon name="check" /></span><div class="fb-toast-body"><b>Berhasil</b><p>{{ session('status') }}</p></div><button type="button" class="fb-toast-x" data-toast-x aria-label="Tutup pesan"><x-icon name="x" /></button><i class="fb-toast-bar"></i></div>
            @endif
            @if (session('error'))
                <div class="fb-toast is-error" role="alert" data-toast-ms="9000"><span class="fb-toast-ic"><x-icon name="x" /></span><div class="fb-toast-body"><b>Gagal</b><p>{{ session('error') }}</p></div><button type="button" class="fb-toast-x" data-toast-x aria-label="Tutup pesan"><x-icon name="x" /></button><i class="fb-toast-bar"></i></div>
            @endif
            @if ($fbErr)
                <div class="fb-toast is-warning" role="alert" data-toast-ms="10000"><span class="fb-toast-ic"><x-icon name="alert" /></span><div class="fb-toast-body"><b>Ada yang perlu diperbaiki</b><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button type="button" class="fb-toast-x" data-toast-x aria-label="Tutup pesan"><x-icon name="x" /></button><i class="fb-toast-bar"></i></div>
            @endif
        </div>
    @endif
@endif
