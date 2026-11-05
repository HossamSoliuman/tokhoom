<?php

namespace App\Enums;

enum DealStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Viewing = 'viewing';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديدة',
            self::Contacted => 'تم التواصل',
            self::Viewing => 'معاينة',
            self::Negotiation => 'تفاوض',
            self::Won => 'ناجحة',
            self::Lost => 'خاسرة',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::New => 'bg-subtle',
            self::Contacted, self::Viewing => 'bg-primary-mid',
            self::Negotiation => 'bg-warning-dot',
            self::Won => 'bg-success',
            self::Lost => 'bg-danger',
        };
    }

    /**
     * Position in the pipeline, used for the lead funnel. Lost deals have no rank.
     */
    public function rank(): int
    {
        return match ($this) {
            self::New => 0,
            self::Contacted => 1,
            self::Viewing => 2,
            self::Negotiation => 3,
            self::Won => 4,
            self::Lost => -1,
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }

    /**
     * Pill colours used where a deal stage labels an inquiry.
     */
    public function pillClasses(): string
    {
        return match ($this) {
            self::New, self::Contacted => 'bg-divider text-body',
            self::Viewing => 'bg-primary-soft text-primary-strong',
            self::Negotiation => 'bg-warning-soft text-warning',
            self::Won => 'bg-success-soft text-success',
            self::Lost => 'bg-danger-soft text-danger',
        };
    }

    /**
     * @return list<self>
     */
    public static function openStages(): array
    {
        return [self::New, self::Contacted, self::Viewing, self::Negotiation];
    }
}
