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
     */
    public static function clean(string $input): string
    {
        return substr(preg_replace('/[^a-z0-9-]/', '', strtolower(trim($input))) ?? '', 0, 30);
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28})[a-z0-9]$/', $slug);
    }

    public static function isAvailable(string $slug): bool
    {
        return self::isValid($slug)
            && ! in_array($slug, config('tokhoom.reserved_slugs'), true)
            && Workspace::where('slug', $slug)->doesntExist();
    }

    /**
     * Where an address stands for the picker: empty, invalid, available or taken.
     */
    public static function state(string $slug): string
    {
        return match (true) {
            $slug === '' => 'empty',
            ! self::isValid($slug) => 'invalid',
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
