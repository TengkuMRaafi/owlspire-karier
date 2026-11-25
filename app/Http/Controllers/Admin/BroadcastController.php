<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBroadcast;
use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Services\Audit\Audit;
use App\Services\Broadcast\BroadcastAudience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Broadcast email ke pengguna (jadwal seleksi, diskon, karier baru, umum). Hanya admin (area `broadcast`). */
class BroadcastController extends Controller
{
    public function __construct(private BroadcastAudience $audience)
    {
    }

    public function index()
    {
        return view('admin.broadcast.index', [
            'broadcasts' => EmailBroadcast::with('sender:id,name')->latest('id')->paginate(10),
        ]);
    }

    public function create()
    {
        $defaults = $this->ctaDefaults();

        return view('admin.broadcast.form', $this->formData(new EmailBroadcast([
            'kind' => 'jadwal',
            'audience' => 'all',
            'cta_label' => $defaults['jadwal']['label'],
            'cta_url' => $defaults['jadwal']['url'],
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $broadcast = EmailBroadcast::create($this->validated($request) + ['user_id' => $request->user()->id, 'status' => 'draft']);

        return $this->afterSave($request, $broadcast);
    }

    /** Draf bisa diubah; yang sudah dikirim tampil sebagai ringkasan baca-saja. */
    public function edit(EmailBroadcast $broadcast)
    {
        return view('admin.broadcast.form', $this->formData($broadcast));
    }

    public function update(Request $request, EmailBroadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->isDraft()) {
            return redirect()->route('admin.broadcast.edit', $broadcast)->with('error', 'Broadcast yang sudah dikirim tidak bisa diubah.');
        }

        $broadcast->update($this->validated($request));

        return $this->afterSave($request, $broadcast);
    }

    public function destroy(EmailBroadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->isDraft()) {
            return redirect()->route('admin.broadcast.index')->with('error', 'Hanya draf yang bisa dihapus. Riwayat pengiriman disimpan.');
        }

        $broadcast->delete();

        return redirect()->route('admin.broadcast.index')->with('status', 'Draf dihapus.');
    }

    /** Pratinjau memakai templat email yang sama persis dengan yang dikirim. Isi form belum perlu disimpan. */
    public function preview(Request $request): Response
    {
        $broadcast = new EmailBroadcast($request->only(['kind', 'subject', 'title', 'body', 'highlight', 'detail', 'cta_label', 'cta_url']));
        if (! array_key_exists((string) $broadcast->kind, EmailBroadcast::KINDS)) {
            $broadcast->kind = 'umum';
        }
        if (! filter_var((string) $broadcast->cta_url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', (string) $broadcast->cta_url)) {
            $broadcast->cta_url = null;
        }

        $html = view('emails.broadcast', [
            'broadcast' => $broadcast,
            'recipientName' => BroadcastMail::firstName($request->user()->name),
            'unsubscribeUrl' => '#',
            'isTest' => false,
        ])->render();

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function afterSave(Request $request, EmailBroadcast $broadcast): RedirectResponse
    {
        return match ($request->input('action')) {
            'test' => $this->sendTest($request, $broadcast),
            'send' => $this->send($request, $broadcast),
            default => redirect()->route('admin.broadcast.edit', $broadcast)->with('status', 'Draf disimpan.'),
        };
    }

    /** Satu email contoh ke email admin sendiri, langsung (tidak lewat antrean) supaya hasilnya bisa dicek di inbox. */
    private function sendTest(Request $request, EmailBroadcast $broadcast): RedirectResponse
    {
        $user = $request->user();

        try {
            Mail::to($user->email)->send(new BroadcastMail($broadcast, BroadcastMail::firstName($user->name), BroadcastMail::unsubscribeUrlFor($user), true));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.broadcast.edit', $broadcast)
                ->with('error', 'Email tes gagal dikirim: '.Str::limit($e->getMessage(), 160).' Cek pengaturan SMTP di .env.');
        }

        Audit::log('broadcast.test', $broadcast, ['to' => $user->email]);

        return redirect()->route('admin.broadcast.edit', $broadcast)->with('status', "Email tes dikirim ke {$user->email}. Cek inbox (dan folder spam).");
    }

    private function send(Request $request, EmailBroadcast $broadcast): RedirectResponse
    {
        $count = $this->audience->count($broadcast->audience);
        if ($count === 0) {
            return redirect()->route('admin.broadcast.edit', $broadcast)->with('error', 'Belum ada penerima untuk pilihan ini (hanya akun dengan email terverifikasi yang dihitung).');
        }

        // Atomik: hanya draf yang bisa diantrekan, jadi klik ganda tidak mengirim dua kali.
        $claimed = EmailBroadcast::whereKey($broadcast->id)->where('status', 'draft')->update([
            'status' => 'queued', 'recipients_count' => $count, 'sent_count' => 0, 'failed_count' => 0, 'user_id' => $request->user()->id,
        ]);
        if (! $claimed) {
            return redirect()->route('admin.broadcast.index')->with('error', 'Broadcast ini sudah dikirim.');
        }

        Audit::log('broadcast.sent', $broadcast, ['audience' => $broadcast->audience, 'recipients' => $count, 'kind' => $broadcast->kind]);
        ProcessBroadcast::dispatch($broadcast->id);

        return redirect()->route('admin.broadcast.index')->with('status', "Broadcast masuk antrean: {$count} email dikirim bertahap.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(EmailBroadcast::KINDS))],
            'audience' => ['required', Rule::in(array_keys(EmailBroadcast::AUDIENCES))],
            'subject' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'highlight' => ['nullable', 'string', 'max:60'],
            'detail' => ['nullable', 'string', 'max:1500'],
            'cta_label' => ['nullable', 'string', 'max:40', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'url:http,https', 'max:500', 'required_with:cta_label'],
        ]);

        // Kode promo hanya untuk diskon; detail tidak dipakai pada jenis Umum.
        if ($data['kind'] !== 'diskon') {
            $data['highlight'] = null;
        }
        if ($data['kind'] === 'umum') {
            $data['detail'] = null;
        }

        return $data;
    }

    private function formData(EmailBroadcast $broadcast): array
    {
        return [
            'broadcast' => $broadcast,
            'counts' => $this->audience->counts(),
            'ctaDefaults' => $this->ctaDefaults(),
        ];
    }

    /** Isi awal tombol per jenis; admin tinggal mengubah bila perlu. */
    private function ctaDefaults(): array
    {
        return [
            'jadwal' => ['label' => 'Lihat jadwal seleksi', 'url' => route('selection.index')],
            'diskon' => ['label' => 'Lihat paket Premium', 'url' => route('billing.index')],
            'karier' => ['label' => 'Lihat lowongan', 'url' => route('jobs.index')],
            'umum' => ['label' => 'Buka '.config('platform.brand.name'), 'url' => route('home')],
        ];
    }
}
