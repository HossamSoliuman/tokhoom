<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Notifications\Messages\MailMessage;

class CustomDomainFailed extends DashboardNotification
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
            ->subject('تعذّر تفعيل النطاق '.$this->workspace->custom_domain)
            ->greeting('مرحباً '.$notifiable->name)
            ->line('لم نتمكن من إصدار شهادة الأمان لنطاقك بعد عدة محاولات، ويبقى موقعك يعمل على '.$this->workspace->siteHost().'.')
            ->line('تأكد أن سجل CNAME هو السجل الوحيد للنطاق ويشير إلى '.config('tokhoom.cname_target').'، ثم اضغط «تحقق الآن» في صفحة الاشتراك.')
            ->action('فتح صفحة الاشتراك', route('billing'));
    }

    protected function icon(): string
    {
        return 'ti-world-off';
    }

    protected function tone(): string
    {
        return 'danger';
    }

    protected function text(): string
    {
        return 'تعذّر تفعيل النطاق '.$this->workspace->custom_domain.'. راجع سجل CNAME';
    }

    protected function url(): string
    {
        return route('billing');
    }
}
