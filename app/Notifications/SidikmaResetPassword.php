<?php

namespace App\Notifications;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SidikmaResetPassword extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan Reset Password SIDIKMA')
            ->greeting('SIDIKMA')
            ->line('Kami menerima permintaan untuk mengubah password akun SIDIKMA Anda.')
            ->action('Reset Password', $url)
            ->line('Link reset password ini berlaku selama '.config('auth.passwords.users.expire').' menit.')
            ->line('Jika Anda tidak meminta perubahan password, abaikan email ini.')
            ->salutation("SIDIKMA\nLembaga Pendidikan Ma'arif NU PCNU Kabupaten Gunungkidul");
    }
}
