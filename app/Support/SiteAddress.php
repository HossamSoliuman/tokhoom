<?php

namespace App\Support;

use App\Models\Workspace;

/**
 * Rules for the {slug}.tokhoom.sa site address chosen at signup.
 */
class SiteAddress
{
    /**
     * @var array<string, string>
     */
    private const CITY_SLUGS = [
        'الرياض' => 'riyadh',
        'جدة' => 'jeddah',
        'مكة المكرمة' => 'makkah',
        'المدينة المنورة' => 'madinah',
        'الدمام' => 'dammam',
        'الخبر' => 'khobar',
        'أبها' => 'abha',
    ];

    /**
     * Lowercase latin letters, digits and inner hyphens, 3 to 30 characters.
     * A pasted link such as https://alrashid.tokhoom.sa/ keeps only its first label.
     */
    public static function clean(string $input): string
    {
        $input = strtolower(trim($input));

        if (str_contains($input, '.') || str_contains($input, '/')) {
            $host = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $input) ?? '';
            $input = explode('.', explode('/', $host)[0])[0];
        }

        return substr(preg_replace('/[^a-z0-9-]/', '', $input) ?? '', 0, 30);
    }

    /**
     * Whether the raw input has Arabic letters, which cleaning would drop.
     */
    public static function hasArabic(string $input): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $input);
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, config('tokhoom.reserved_slugs'), true);
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28})[a-z0-9]$/', $slug);
    }

    public static function isAvailable(string $slug): bool
    {
        return self::isValid($slug)
            && ! self::isReserved($slug)
            && Workspace::where('slug', $slug)->doesntExist();
    }

    /**
     * Where an address stands for the picker: empty, invalid, reserved, available or taken.
     */
    public static function state(string $slug): string
    {
        return match (true) {
            $slug === '' => 'empty',
            ! self::isValid($slug) => 'invalid',
            self::isReserved($slug) => 'reserved',
            self::isAvailable($slug) => 'available',
            default => 'taken',
        };
    }

    /**
     * Available alternatives to a taken address.
     *
     * @return list<string>
     */
    public static function suggestions(string $slug, ?string $city = null): array
    {
        $candidates = [$slug.'-re', $slug.'-'.(self::CITY_SLUGS[$city] ?? 'riyadh'), $slug.'office', $slug.'-sa', $slug.'2'];

        return array_slice(array_values(array_filter(array_unique($candidates), self::isAvailable(...))), 0, 3);
    }

    /**
     * @return list<string>
     */
    public static function cities(): array
    {
        return array_keys(self::CITY_SLUGS);
    }
}
