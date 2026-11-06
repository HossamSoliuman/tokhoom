<?php

namespace App\Support;

/**
 * Saudi mobile numbers: stored as "05XXXXXXXX", shown as "055 123 4567".
 */
class Phone
{
    /**
     * Normalise user input to the local "05XXXXXXXX" form. Returns the digits
     * unchanged when they are not a recognisable Saudi mobile number.
     */
    public static function normalize(?string $input): string
    {
        $digits = self::digits($input);

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * The Latin digits typed, with Arabic-Indic digits converted and everything else dropped.
     */
    public static function digits(?string $input): string
    {
        return preg_replace('/\D/', '', strtr((string) $input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ])) ?? '';
    }

    public static function isMobile(string $normalized): bool
    {
        return (bool) preg_match('/^05\d{8}$/', $normalized);
    }

    public static function display(?string $mobile): string
    {
        $digits = self::normalize($mobile);

        if (strlen($digits) === 10) {
            return substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6);
        }

        return (string) $mobile;
    }

    public static function international(string $mobile): string
    {
        $digits = self::normalize($mobile);

        return str_starts_with($digits, '0') ? '966'.substr($digits, 1) : $digits;
    }

    public static function whatsappUrl(string $mobile, ?string $text = null): string
    {
        $url = 'https://wa.me/'.self::international($mobile);

        return $text ? $url.'?text='.rawurlencode($text) : $url;
    }

    public static function telUrl(string $mobile): string
    {
        return 'tel:'.self::normalize($mobile);
    }
}
