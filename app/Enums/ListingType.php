<?php

namespace App\Enums;

enum ListingType: string
{
    case Villa = 'villa';
    case Apartment = 'apartment';
    case Land = 'land';
    case Floor = 'floor';
    case Commercial = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::Villa => 'فيلا',
            self::Apartment => 'شقة',
            self::Land => 'أرض',
            self::Floor => 'دور',
            self::Commercial => 'تجاري',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Villa => 'ti-home',
            self::Apartment => 'ti-building',
            self::Land => 'ti-map-2',
            self::Floor => 'ti-stack-2',
            self::Commercial => 'ti-building-store',
        };
    }
}
