<?php

namespace App\Support;

/**
 * Time-based one-time codes (RFC 6238) as shown by authenticator apps:
 * six digits from an HMAC-SHA1 of the 30-second step, with a Base32 secret.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    public static function generateSecret(): string
    {
        $secret = '';

        foreach (str_split(random_bytes(32)) as $byte) {
            $secret .= self::ALPHABET[ord($byte) & 31];
        }

        return $secret;
    }

    /**
     * The otpauth:// link that authenticator apps read from the QR code.
     */
    public static function uri(string $secret, string $account, string $issuer = 'Tokhoom'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account)
            .'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&digits=6&period='.self::PERIOD;
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $step = intdiv($timestamp ?? time(), self::PERIOD);
        $hash = hash_hmac('sha1', pack('J', $step), self::decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Whether the code matches the current step or the one either side,
     * allowing for a phone clock a little off.
     */
    public static function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $timestamp ??= time();

        foreach ([-1, 0, 1] as $drift) {
            if (hash_equals(self::code($secret, $timestamp + $drift * self::PERIOD), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function decode(string $secret): string
    {
        $bits = '';

        foreach (str_split(strtoupper($secret)) as $character) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $character)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr((int) bindec($chunk));
            }
        }

        return $bytes;
    }
}
