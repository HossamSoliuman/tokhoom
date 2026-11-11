<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Platform settings that admins change from the admin panel. Each one
 * overrides a config value, so the rest of the app keeps reading config().
 * A setting never saved keeps its value from the config files and .env.
 */
class PlatformSettings
{
    private const CACHE_KEY = 'platform-settings';

    /**
     * Every setting by name: the config key it overrides, its type, and
     * whether it is a secret stored encrypted and never shown again.
     *
     * @var array<string, array{key: string, type: 'int'|'string'|'bool'|'list', secret?: bool}>
     */
    public const FIELDS = [
        'agent_price' => ['key' => 'tokhoom.plans.agent.price', 'type' => 'int'],
        'agent_listing_limit' => ['key' => 'tokhoom.plans.agent.listing_limit', 'type' => 'int'],
        'office_price' => ['key' => 'tokhoom.plans.office.price', 'type' => 'int'],
        'office_seats' => ['key' => 'tokhoom.plans.office.seats', 'type' => 'int'],
        'plus_price' => ['key' => 'tokhoom.plans.plus.price', 'type' => 'int'],
        'plus_seats' => ['key' => 'tokhoom.plans.plus.seats', 'type' => 'int'],
        'extra_seat_price' => ['key' => 'tokhoom.extra_seat_price', 'type' => 'int'],
        'trial_days' => ['key' => 'tokhoom.trial_days', 'type' => 'int'],
        'collection_attempts' => ['key' => 'tokhoom.collection_attempts', 'type' => 'int'],

        'seller_name' => ['key' => 'tokhoom.seller.name', 'type' => 'string'],
        'seller_vat_number' => ['key' => 'tokhoom.seller.vat_number', 'type' => 'string'],
        'seller_cr_number' => ['key' => 'tokhoom.seller.cr_number', 'type' => 'string'],
        'seller_address' => ['key' => 'tokhoom.seller.address', 'type' => 'string'],
        'vat_rate' => ['key' => 'tokhoom.vat_rate', 'type' => 'int'],

        'demo_site_url' => ['key' => 'tokhoom.demo_site_url', 'type' => 'string'],
        'support_whatsapp' => ['key' => 'tokhoom.support.whatsapp', 'type' => 'string'],
        'support_phone' => ['key' => 'tokhoom.support.phone', 'type' => 'string'],
        'support_email' => ['key' => 'tokhoom.support.email', 'type' => 'string'],
        'support_hours' => ['key' => 'tokhoom.support.hours', 'type' => 'string'],

        'signups_open' => ['key' => 'tokhoom.signups_open', 'type' => 'bool'],
        'reserved_slugs' => ['key' => 'tokhoom.reserved_slugs', 'type' => 'list'],

        'tap_secret_key' => ['key' => 'services.tap.secret_key', 'type' => 'string', 'secret' => true],
        'tap_merchant_id' => ['key' => 'services.tap.merchant_id', 'type' => 'string'],

        'mail_mailer' => ['key' => 'mail.default', 'type' => 'string'],
        'mail_host' => ['key' => 'mail.mailers.smtp.host', 'type' => 'string'],
        'mail_port' => ['key' => 'mail.mailers.smtp.port', 'type' => 'int'],
        'mail_scheme' => ['key' => 'mail.mailers.smtp.scheme', 'type' => 'string'],
        'mail_username' => ['key' => 'mail.mailers.smtp.username', 'type' => 'string'],
        'mail_password' => ['key' => 'mail.mailers.smtp.password', 'type' => 'string', 'secret' => true],
        'mail_from_address' => ['key' => 'mail.from.address', 'type' => 'string'],
        'mail_from_name' => ['key' => 'mail.from.name', 'type' => 'string'],
    ];

    /**
     * Put every saved setting into the config for this request.
     */
    public static function apply(): void
    {
        foreach (self::stored() as $field => $value) {
            config([self::FIELDS[$field]['key'] => $value]);
        }
    }

    /**
     * The value in effect for a setting, saved or from config.
     */
    public static function get(string $field): mixed
    {
        return self::cast($field, config(self::FIELDS[$field]['key']));
    }

    public static function isSecret(string $field): bool
    {
        return self::FIELDS[$field]['secret'] ?? false;
    }

    /**
     * Save the given settings and apply them now. A value equal to the one in
     * effect is skipped, so untouched settings keep following .env. Returns
     * the names of the settings that changed.
     *
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    public static function save(array $values): array
    {
        $changed = [];

        foreach ($values as $field => $value) {
            $value = self::cast($field, $value);

            if ($value === self::get($field)) {
                continue;
            }

            Setting::updateOrCreate(['key' => $field], ['value' => self::encode($field, $value)]);
            config([self::FIELDS[$field]['key'] => $value]);
            $changed[] = $field;
        }

        Cache::forget(self::CACHE_KEY);

        return $changed;
    }

    /**
     * Saved settings, decoded. Secrets that no longer decrypt, after the app
     * key changed, are skipped so .env applies again.
     *
     * @return array<string, mixed>
     */
    private static function stored(): array
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
        $values = [];

        foreach ($rows as $field => $encoded) {
            if (! isset(self::FIELDS[$field])) {
                continue;
            }

            try {
                $values[$field] = self::decode($field, $encoded);
            } catch (DecryptException) {
                continue;
            }
        }

        return $values;
    }

    private static function encode(string $field, mixed $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);

        return self::isSecret($field) ? Crypt::encryptString($json) : $json;
    }

    private static function decode(string $field, ?string $encoded): mixed
    {
        if ($encoded === null) {
            return null;
        }

        return self::cast($field, json_decode(self::isSecret($field) ? Crypt::decryptString($encoded) : $encoded, true));
    }

    /**
     * Normalise a value to its setting's type. Blank text becomes null.
     */
    private static function cast(string $field, mixed $value): mixed
    {
        return match (self::FIELDS[$field]['type']) {
            'int' => $value === null || $value === '' ? null : (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'list' => array_values(array_filter(array_map(fn ($item) => trim((string) $item), (array) $value), fn (string $item) => $item !== '')),
            default => filled($value) ? trim((string) $value) : null,
        };
    }
}
