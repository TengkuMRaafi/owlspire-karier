<?php

namespace App\Mail;

use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/** Email broadcast: satu templat (header logo Owlspire, label jenis, judul, salam, isi, satu tombol, footer). */
class BroadcastMail extends Mailable
{
    public function __construct(
        public EmailBroadcast $broadcast,
        public string $recipientName,
        public string $unsubscribeUrl,
        public bool $isTest = false,
    ) {
    }

    /** Tautan "Berhenti menerima info" bertanda tangan (tidak bisa ditebak) untuk satu pengguna. */
    public static function unsubscribeUrlFor(User $user): string
    {
        return URL::signedRoute('broadcast.unsubscribe', ['user' => $user->id]);
    }

    public static function firstName(?string $name): string
    {
        $first = trim(explode(' ', trim((string) $name))[0] ?? '');

        return $first !== '' ? $first : 'kamu';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->isTest ? '[TES] ' : '').$this->broadcast->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.broadcast', text: 'emails.broadcast-text');
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>']);
    }
}
