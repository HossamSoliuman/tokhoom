<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A test email a platform admin sends to themselves after changing the mail settings.
 */
class MailSettingsCheck extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رسالة تجريبية من تخوم')
            ->line('وصلت هذه الرسالة، فإعدادات البريد في تخوم تعمل.');
    }
}
