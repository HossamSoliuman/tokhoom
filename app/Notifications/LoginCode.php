<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Notification;

/**
 * The one-time code for signing in with a mobile number.
 */
class LoginCode extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return 'رمز الدخول إلى تخوم: '.$this->code.'. لا تشاركه مع أحد.';
    }
}
