<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)

            ->subject('Reset Password Akun')

            ->greeting('Halo, ' . $notifiable->nama_lengkap . '!')

            ->line('Kami menerima permintaan untuk mereset password akun Anda pada SIG-PALA.')

            ->line('Klik tombol di bawah ini untuk membuat password baru.')

            ->action('Reset Password', $this->url)

            ->line('Link reset password hanya berlaku selama 60 menit.')

            ->line('Jika Anda tidak merasa melakukan permintaan reset password, abaikan email ini dan jangan bagikan tautan kepada siapa pun.')

            ->salutation('Hormat kami,')
            
            ->salutation('Sistem Informasi Dinas Pertanian');
    }
}