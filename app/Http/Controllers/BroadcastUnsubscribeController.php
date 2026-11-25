<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * "Berhenti menerima info" dari footer email broadcast. Tautannya bertanda tangan (signed), jadi hanya pemilik email
 * yang bisa memakainya. Halaman konfirmasi dulu supaya pemindai link di inbox tidak berhenti-langganan-kan pengguna.
 * Email penting akun (verifikasi, reset sandi) tidak terpengaruh.
 */
class BroadcastUnsubscribeController extends Controller
{
    public function show(User $user)
    {
        return view('broadcast.unsubscribe', [
            'name' => \App\Mail\BroadcastMail::firstName($user->name),
            'optedOut' => $user->broadcast_opt_out_at !== null,
            'action' => URL::signedRoute('broadcast.unsubscribe.update', ['user' => $user->id]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $resume = $request->input('aksi') === 'aktifkan';
        $user->forceFill(['broadcast_opt_out_at' => $resume ? null : now()])->save();

        return redirect(URL::signedRoute('broadcast.unsubscribe', ['user' => $user->id]))
            ->with('status', $resume ? 'Info Owlspire diaktifkan lagi.' : 'Kamu tidak akan menerima email info lagi.');
    }
}
