<?php

namespace App\Enums;

enum ListingPurpose: string
{
    case Sale = 'sale';
    case Rent = 'rent';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'للبيع',
            self::Rent => 'للإيجار',
        };
    }

    /**
     * How a client request for this purpose is worded: شراء / إيجار.
     */
    public function requestLabel(): string
    {
        return match ($this) {
            self::Sale => 'شراء',
            self::Rent => 'إيجار',
        };
    }
}
