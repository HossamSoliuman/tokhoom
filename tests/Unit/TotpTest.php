<?php

use App\Support\Totp;

/**
 * Base32 of the RFC 6238 SHA-1 test key "12345678901234567890".
 */
const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

test('codes match the RFC 6238 test vectors', function (int $timestamp, string $code) {
    expect(Totp::code(RFC_SECRET, $timestamp))->toBe($code);
})->with([
    'T = 59' => [59, '287082'],
    'T = 1111111109' => [1111111109, '081804'],
    'T = 1234567890' => [1234567890, '005924'],
    'T = 2000000000' => [2000000000, '279037'],
]);

test('a code is accepted one step either side and refused beyond', function () {
    $code = Totp::code(RFC_SECRET, 1234567890);

    expect(Totp::verify(RFC_SECRET, $code, 1234567890 + 30))->toBeTrue()
        ->and(Totp::verify(RFC_SECRET, $code, 1234567890 - 30))->toBeTrue()
        ->and(Totp::verify(RFC_SECRET, $code, 1234567890 + 90))->toBeFalse();
});

test('generated secrets are 32 Base32 characters', function () {
    expect(Totp::generateSecret())->toMatch('/^[A-Z2-7]{32}$/');
});
