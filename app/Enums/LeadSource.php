<?php

namespace App\Enums;

enum LeadSource: string
{
    case Site = 'site';
    case SharedLink = 'shared_link';
    case Qr = 'qr';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'الموقع',
            self::SharedLink => 'رابط مشترك',
            self::Qr => 'رمز QR',
            self::Direct => 'مباشر',
        };
    }

    /**
     * Label used for traffic sources on a listing, where "site" means the office site.
     */
    public function trafficLabel(): string
    {
        return $this === self::Site ? 'موقع المكتب' : $this->label();
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::Site => 'bg-primary',
            self::SharedLink => 'bg-primary-mid',
            self::Qr => 'bg-sand',
            self::Direct => 'bg-subtle',
        };
    }
}
