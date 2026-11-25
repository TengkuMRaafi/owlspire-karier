<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Pembuat email sistem bertema Owlspire (HTML + versi teks biasa; versi teks membantu email tidak dianggap spam). */
class BrandedMail
{
    /**
     * @param  string[]  $lines  paragraf sebelum tombol
     * @param  string[]  $outro  paragraf setelah tombol
     * @param  array<string,string>  $details  baris rincian (label => nilai), opsional
     */
    public static function make(string $subject, string $greeting, array $lines, ?string $actionText = null, ?string $actionUrl = null, array $outro = [], array $details = []): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view(['emails.transactional', 'emails.transactional-text'], [
                'subject' => $subject,
                'greeting' => $greeting,
                'lines' => $lines,
                'details' => $details,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
                'outro' => $outro,
                'salutation' => 'Salam, tim '.config('platform.brand.name'),
            ]);
    }
}
