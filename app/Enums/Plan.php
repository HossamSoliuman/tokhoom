<?php

namespace App\Enums;

use App\Support\Arabic;

enum Plan: string
{
    case Agent = 'agent';
    case Office = 'office';
    case Plus = 'plus';

    /**
     * Monthly price of one seat beyond what the plan includes.
     */
    public static function extraSeatPrice(): int
    {
        return (int) config('tokhoom.extra_seat_price');
    }

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
            self::Agent => 'وسيط واحد · '.Arabic::countWithNumber($this->listingLimit(), 'عقار', 'عقارات', 'عقاراً'),
            self::Office => 'حتى '.Arabic::countWithNumber($this->includedSeats(), 'مقعد', 'مقاعد', 'مقعداً').' · عقارات غير محدودة',
            self::Plus => 'حتى '.Arabic::countWithNumber($this->includedSeats(), 'مقعد', 'مقاعد', 'مقعداً').' · نطاق خاص وتقارير متقدمة',
        };
    }

    public function monthlyPrice(): int
    {
        return (int) config('tokhoom.plans.'.$this->value.'.price');
    }

    public function includedSeats(): int
    {
        return $this === self::Agent ? 1 : (int) config('tokhoom.plans.'.$this->value.'.seats');
    }

    public function listingLimit(): ?int
    {
        return $this === self::Agent ? (int) config('tokhoom.plans.agent.listing_limit') : null;
    }
}
