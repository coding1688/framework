<?php

namespace App\Notifications;

use App\Libs\Settings\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplatedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $subject, private readonly string $content, private readonly ?string $actionUrl = null)
    {
        $this->onQueue('notifications');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->subject)->line($this->content);
        if ($this->actionUrl) {
            $message->action(__('notification.view'), $this->actionUrl);
        }

        return app(MailConfiguration::class)->apply($message);
    }
}
