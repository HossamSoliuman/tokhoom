<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The email with a link to choose a new password.
 */
class ResetPasswordLink extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور في تخوم')
            ->line('طلبت إعادة تعيين كلمة المرور لحسابك في تخوم.')
            ->action('اختيار كلمة مرور جديدة', route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]))
            ->line('الرابط صالح لمدة '.$minutes.' دقيقة. إن لم تطلب ذلك فتجاهل هذه الرسالة.');
    }
}
