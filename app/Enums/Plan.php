<?php

namespace App\Enums;

enum Plan: string
{
    case Agent = 'agent';
    case Office = 'office';
    case Plus = 'plus';

    /**
     * Monthly price of one seat beyond what the plan includes.
     */
    public const EXTRA_SEAT_PRICE = 49;

    public function label(): string
    {
        return match ($this) {
            self::Agent => 'خطة الوسيط',
            self::Office => 'خطة المكتب',
            self::Plus => 'خطة المكتب بلس',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Agent => 'الوسيط',
            self::Office => 'المكتب',
            self::Plus => 'المكتب بلس',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Agent => 'وسيط واحد · 30 عقاراً',
            self::Office => 'حتى 5 مقاعد · عقارات غير محدودة',
            self::Plus => 'حتى 15 مقعداً · نطاق خاص وتقارير متقدمة',
        };
    }

    public function monthlyPrice(): int
    {
        return match ($this) {
            self::Agent => 99,
            self::Office => 349,
            self::Plus => 799,
        };
    }

    public function includedSeats(): int
    {
        return match ($this) {
            self::Agent => 1,
            self::Office => 5,
            self::Plus => 15,
        };
    }

    public function listingLimit(): ?int
    {
        return $this === self::Agent ? 30 : null;
    }
}
