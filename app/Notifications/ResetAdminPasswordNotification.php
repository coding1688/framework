<?php

namespace App\Notifications;

use App\Libs\Settings\MailConfiguration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetAdminPasswordNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('backend.login', ['mode' => 'reset', 'token' => $this->token, 'email' => $notifiable->email]);
        $message = (new MailMessage)
            ->subject(__('auth.password_reset_subject'))
            ->line(__('auth.password_reset_intro'))
            ->action(__('auth.password_reset_action'), $url)
            ->line(__('auth.password_reset_expire', ['count' => config('auth.passwords.backend.expire')]));

        return app(MailConfiguration::class)->apply($message);
    }
}
