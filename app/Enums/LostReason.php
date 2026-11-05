<?php

namespace App\Enums;

enum LostReason: string
{
    case Price = 'price';
    case OtherListing = 'other_listing';
    case NoInterest = 'no_interest';
    case Unreachable = 'unreachable';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Price => 'السعر أعلى من ميزانية العميل',
            self::OtherListing => 'اختار عقاراً آخر',
            self::NoInterest => 'لم يعد مهتماً',
            self::Unreachable => 'تعذّر التواصل مع العميل',
            self::Other => 'سبب آخر',
        };
    }
}
