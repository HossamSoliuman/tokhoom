<?php

namespace App\Enums;

enum DomainStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار التحقق',
            self::Active => 'مفعّل',
            self::Error => 'خطأ في الإعداد',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'ti-clock',
            self::Active => 'ti-circle-check',
            self::Error => 'ti-alert-circle',
        };
    }

    public function pillClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-soft text-warning',
            self::Active => 'bg-success-soft text-success',
            self::Error => 'bg-danger-soft text-danger',
        };
    }
}
