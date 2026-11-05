<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Rented = 'rented';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Available => 'متاح',
            self::Reserved => 'محجوز',
            self::Sold => 'مباع',
            self::Rented => 'مؤجّر',
            self::Hidden => 'مخفي',
        };
    }

    /**
     * Tailwind classes for the status chip (background, text and border).
     */
    public function chipClasses(): string
    {
        return match ($this) {
            self::Available => 'bg-success-soft text-success border-transparent',
            self::Reserved => 'bg-warning-soft text-warning border-transparent',
            self::Sold, self::Rented => 'bg-closed text-closed-ink border-transparent',
            self::Hidden => 'bg-divider text-body border-transparent',
            self::Draft => 'bg-white text-muted border-dashed border-faint',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Available => 'bg-success',
            self::Reserved => 'bg-warning-dot',
            self::Sold, self::Rented => 'bg-subtle',
            self::Hidden, self::Draft => 'bg-faint',
        };
    }

    /**
     * Whether a listing in this status shows on the public site.
     */
    public function isPublic(): bool
    {
        return in_array($this, [self::Available, self::Reserved], true);
    }

    /**
     * @return list<self>
     */
    public static function quickChangeOptions(ListingPurpose $purpose): array
    {
        return [
            self::Available,
            self::Reserved,
            $purpose === ListingPurpose::Sale ? self::Sold : self::Rented,
            self::Hidden,
        ];
    }
}
