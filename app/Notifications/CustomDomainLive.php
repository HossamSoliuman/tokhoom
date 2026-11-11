<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Notifications\Messages\MailMessage;

class CustomDomainLive extends DashboardNotification
{
    public function __construct(public Workspace $workspace) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('نطاقك '.$this->workspace->custom_domain.' يعمل الآن')
            ->greeting('مرحباً '.$notifiable->name)
            ->line('أصدرنا شهادة الأمان لنطاقك، وأصبح موقعك يفتح على '.$this->workspace->siteUrl().'. نجدد الشهادة تلقائياً قبل انتهائها.')
            ->action('فتح الموقع', $this->workspace->siteUrl());
    }

    protected function icon(): string
    {
        return 'ti-world-check';
    }

    protected function tone(): string
    {
        return 'brand';
    }

    protected function text(): string
    {
        return 'نطاقك '.$this->workspace->custom_domain.' يعمل الآن بشهادة أمان';
    }

    protected function url(): string
    {
        return route('billing');
    }
}
