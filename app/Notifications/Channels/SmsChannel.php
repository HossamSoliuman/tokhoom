<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Text messages to Saudi mobiles. Only the "log" driver exists until an SMS
 * provider is chosen: it writes each message to the application log.
 */
class SmsChannel
{
    /**
     * Whether messages reach phones. The log driver only counts outside
     * production, where developers read the codes from the log.
     */
    public static function delivers(): bool
    {
        return config('services.sms.driver') !== 'log' || ! app()->isProduction();
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $mobile = $notifiable->routeNotificationFor('sms', $notification);

        if (! $mobile) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $driver = config('services.sms.driver');

        if ($driver !== 'log') {
            throw new RuntimeException('Unsupported SMS driver ['.$driver.'].');
        }

        Log::info('SMS to '.$mobile.': '.$message);
    }
}
