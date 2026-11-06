<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * The QR code ZATCA requires on simplified tax invoices: seller name, VAT
 * number, issue time, total and VAT, each as a tag-length-value field, the
 * whole encoded in Base64.
 */
class ZatcaQr
{
    public static function payload(string $sellerName, string $vatNumber, CarbonInterface $issuedAt, float $total, float $vat): string
    {
        $fields = [
            1 => $sellerName,
            2 => $vatNumber,
            3 => $issuedAt->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            4 => number_format($total, 2, '.', ''),
            5 => number_format($vat, 2, '.', ''),
        ];

        $tlv = '';

        foreach ($fields as $tag => $value) {
            $tlv .= chr($tag).chr(strlen($value)).$value;
        }

        return base64_encode($tlv);
    }
}
