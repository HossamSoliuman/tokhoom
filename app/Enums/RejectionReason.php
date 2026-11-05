<?php

namespace App\Enums;

enum RejectionReason: string
{
    case Mismatch = 'mismatch';
    case Expiring = 'expiring';
    case InvalidCr = 'invalid_cr';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mismatch => 'بيانات الترخيص لا تطابق سجلات الهيئة العامة للعقار',
            self::Expiring => 'الترخيص منتهٍ أو ينتهي خلال أقل من 30 يوماً',
            self::InvalidCr => 'رقم السجل التجاري غير صحيح',
            self::Other => 'سبب آخر',
        };
    }
}
