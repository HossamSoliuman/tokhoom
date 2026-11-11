<?php

namespace App\Enums;

/**
 * A custom domain's setup: waiting for its CNAME record, securing once the
 * record points at us and a certificate is being issued, then active when
 * the site is served on it over HTTPS.
 */
enum DomainStatus: string
{
    case Pending = 'pending';
    case Securing = 'securing';
    case Active = 'active';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار التحقق',
            self::Securing => 'جارٍ التفعيل',
            self::Active => 'مفعّل',
            self::Error => 'خطأ في الإعداد',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'ti-clock',
            self::Securing => 'ti-lock',
            self::Active => 'ti-circle-check',
            self::Error => 'ti-alert-circle',
        };
    }

    public function pillClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-soft text-warning',
            self::Securing => 'bg-primary-soft text-primary',
            self::Active => 'bg-success-soft text-success',
            self::Error => 'bg-danger-soft text-danger',
        };
    }
}
