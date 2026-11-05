<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Arabic formatting for dates, counts and money, matching the dashboard copy:
 * Gregorian month names, Western digits and the Arabic percent sign.
 */
class Arabic
{
    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    /**
     * @var list<string>
     */
    public const WEEKDAYS = ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'];

    public static function month(int $month): string
    {
        return self::MONTHS[$month];
    }

    /**
     * "14 ديسمبر 2026", or "14 ديسمبر" when the year is left out.
     */
    public static function date(?CarbonInterface $date, bool $withYear = true): string
    {
        if (! $date) {
            return '—';
        }

        $text = $date->day.' '.self::MONTHS[$date->month];

        return $withYear ? $text.' '.$date->year : $text;
    }

    /**
     * Date without the year when it falls in the current year.
     */
    public static function shortDate(CarbonInterface $date): string
    {
        return self::date($date, ! $date->isCurrentYear());
    }

    /**
     * "4:00 م"
     */
    public static function time(CarbonInterface $date): string
    {
        return $date->format('g:i').' '.($date->hour < 12 ? 'ص' : 'م');
    }

    /**
     * "4 نوفمبر 2026، 7:30 م"
     */
    public static function dateTime(CarbonInterface $date): string
    {
        return self::date($date).'، '.self::time($date);
    }

    /**
     * "اليوم، 4:00 م", "غداً، 10:30 ص", "أمس، 1:00 م" or a full date.
     */
    public static function dayAndTime(CarbonInterface $date): string
    {
        $day = match (true) {
            $date->isToday() => 'اليوم',
            $date->isTomorrow() => 'غداً',
            $date->isYesterday() => 'أمس',
            default => null,
        };

        return $day ? $day.'، '.self::time($date) : self::date($date);
    }

    /**
     * "قبل 5 دقائق", "قبل ساعتين", "أمس" or a short date for older moments.
     */
    public static function ago(CarbonInterface $date): string
    {
        $minutes = (int) floor($date->diffInMinutes(Carbon::now(), true));

        if ($minutes < 1) {
            return 'الآن';
        }

        if ($minutes < 60) {
            return 'قبل '.self::count($minutes, 'دقيقة', 'دقيقتين', 'دقائق', 'دقيقة');
        }

        if ($date->isToday() || $minutes < 6 * 60) {
            return 'قبل '.self::count(intdiv($minutes, 60), 'ساعة', 'ساعتين', 'ساعات', 'ساعة');
        }

        if ($date->isYesterday()) {
            return 'أمس';
        }

        return self::shortDate($date);
    }

    /**
     * Arabic number agreement: "يوم", "يومان", "3 أيام", "11 يوماً".
     */
    public static function count(int $number, string $one, string $two, string $few, string $many): string
    {
        return match (true) {
            $number === 1 => $one,
            $number === 2 => $two,
            $number >= 3 && $number <= 10 => $number.' '.$few,
            default => self::number($number).' '.$many,
        };
    }

    /**
     * Like count() but always keeps the number, for labels such as "1 دقيقة".
     */
    public static function countWithNumber(int $number, string $singular, string $few, string $many): string
    {
        return match (true) {
            $number >= 3 && $number <= 10 => $number.' '.$few,
            $number > 10 => self::number($number).' '.$many,
            default => $number.' '.$singular,
        };
    }

    public static function days(int $days): string
    {
        return self::count($days, 'يوم واحد', 'يومان', 'أيام', 'يوماً');
    }

    public static function number(int|float $number): string
    {
        return number_format($number);
    }

    public static function money(int|float|null $amount, string $suffix = ''): string
    {
        if ($amount === null) {
            return '—';
        }

        return number_format($amount).' ريال'.($suffix !== '' ? ' '.$suffix : '');
    }

    public static function percent(int|float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals).'٪';
    }

    /**
     * Signed change between two periods: "+12٪" or "−4٪". Empty when there is
     * nothing to compare against.
     */
    public static function change(int|float $current, int|float $previous): string
    {
        if ($previous == 0) {
            return '';
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return ($change >= 0 ? '+' : '−').abs($change).'٪';
    }

    /**
     * Signed difference as a count: "+3" or "−2".
     */
    public static function difference(int $current, int $previous): string
    {
        $difference = $current - $previous;

        return ($difference >= 0 ? '+' : '−').abs($difference);
    }

    /**
     * Compact minutes for tight spaces: "42 د".
     */
    public static function minutesShort(int $minutes): string
    {
        return $minutes.' د';
    }

    /**
     * The letter shown in an avatar: first letter of the first name, with "ه"
     * written in its joined form so it stays readable on its own.
     */
    public static function initial(string $name): string
    {
        $first = explode(' ', trim($name))[0] ?? '';
        $first = preg_replace('/^ال/u', '', $first) ?: $first;
        $letter = mb_substr($first, 0, 1);

        return $letter === 'ه' ? 'هـ' : $letter;
    }
}
