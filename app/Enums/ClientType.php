<?php

namespace App\Enums;

enum ClientType: string
{
    case Buyer = 'buyer';
    case Tenant = 'tenant';
    case Owner = 'owner';
    case Investor = 'investor';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'مشترٍ',
            self::Tenant => 'مستأجر',
            self::Owner => 'مالك',
            self::Investor => 'مستثمر',
        };
    }
}
