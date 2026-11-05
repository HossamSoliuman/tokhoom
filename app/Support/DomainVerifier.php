<?php

namespace App\Support;

/**
 * Checks that a custom domain points at Tokhoom with a CNAME record.
 */
class DomainVerifier
{
    public function pointsToUs(string $domain): bool
    {
        $target = strtolower(rtrim((string) config('tokhoom.cname_target'), '.'));
        $records = @dns_get_record($domain, DNS_CNAME) ?: [];

        foreach ($records as $record) {
            if (strtolower(rtrim($record['target'] ?? '', '.')) === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise what people type: no scheme, path or trailing dot, lowercase.
     */
    public static function clean(string $input): string
    {
        $host = strtolower(trim($input));
        $host = (string) preg_replace('#^https?://#', '', $host);

        return rtrim(explode('/', $host)[0], '.');
    }

    public static function isValid(string $domain): bool
    {
        return (bool) preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain);
    }

    /**
     * The record name to create: the subdomain part, or "@" for a bare domain.
     */
    public static function recordName(string $domain): string
    {
        $parts = explode('.', $domain);

        return count($parts) > 2 ? implode('.', array_slice($parts, 0, -2)) : '@';
    }
}
