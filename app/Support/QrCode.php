<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

/**
 * QR codes for listing links, as SVG for print and preview and PNG for sharing.
 */
class QrCode
{
    public static function svg(string $data): string
    {
        return (new Generator(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'drawLightModules' => false,
            'connectPaths' => true,
        ])))->render($data);
    }

    public static function png(string $data, int $scale = 12): string
    {
        return (new Generator(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => $scale,
        ])))->render($data);
    }
}
