<?php

namespace App\Support;

/**
 * The colours a public site derives from its brand colour: the text colour
 * that reads on a brand fill, and a brand shade dark enough for text on white.
 */
class BrandColor
{
    public const DEFAULT = '#1E3A4C';

    public static function normalize(?string $hex): string
    {
        return $hex !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? strtoupper($hex) : self::DEFAULT;
    }

    /**
     * Text colour on a brand-coloured fill.
     */
    public static function ink(?string $hex): string
    {
        return self::luminance($hex) > 0.4 ? '#14120F' : '#FFFFFF';
    }

    /**
     * Brand colour for text and icons on white: light brand colours are darkened.
     */
    public static function text(?string $hex): string
    {
        $hex = self::normalize($hex);

        return self::luminance($hex) > 0.18 ? 'color-mix(in oklch, '.$hex.' 55%, #000)' : $hex;
    }

    /**
     * CSS custom properties for the site's root element.
     */
    public static function cssVariables(?string $hex): string
    {
        return '--brand: '.self::normalize($hex).'; --brand-ink: '.self::ink($hex).'; --brand-text: '.self::text($hex).';';
    }

    /**
     * Relative luminance (WCAG) of the colour.
     */
    private static function luminance(?string $hex): float
    {
        $hex = substr(self::normalize($hex), 1);

        [$red, $green, $blue] = array_map(function (int $offset) use ($hex): float {
            $channel = hexdec(substr($hex, $offset, 2)) / 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, [0, 2, 4]);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }
}
