<?php

namespace App\Notifications;

class PaymentFailed extends DashboardNotification
{
    protected function icon(): string
    {
        return 'ti-credit-card-off';
    }

    protected function tone(): string
    {
        return 'danger';
    }

    protected function text(): string
    {
        return 'تعذّر تحصيل رسوم الاشتراك. حدّث بيانات البطاقة لتجنب إيقاف الموقع';
    }

    protected function url(): string
    {
        return route('billing');
    }
}
