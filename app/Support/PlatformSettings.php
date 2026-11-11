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
     * Admin settings pages and the settings each one holds.
     *
     * @var array<string, array{label: string, icon: string, fields: list<string>}>
     */
    public const SECTIONS = [
        'plans' => [
            'label' => 'الخطط والأسعار',
            'icon' => 'ti-receipt-2',
            'fields' => ['agent_price', 'agent_listing_limit', 'office_price', 'office_seats', 'plus_price', 'plus_seats', 'extra_seat_price', 'trial_days', 'collection_attempts'],
        ],
        'invoicing' => [
            'label' => 'الفوترة والضريبة',
            'icon' => 'ti-file-invoice',
            'fields' => ['seller_name', 'seller_vat_number', 'seller_cr_number', 'seller_address', 'vat_rate'],
        ],
        'contact' => [
            'label' => 'التواصل والدعم',
            'icon' => 'ti-headset',
            'fields' => ['support_whatsapp', 'support_phone', 'support_email', 'support_hours', 'demo_site_url'],
        ],
        'signups' => [
            'label' => 'التسجيل',
            'icon' => 'ti-user-plus',
            'fields' => ['signups_open', 'reserved_slugs'],
        ],
        'payments' => [
            'label' => 'بوابة الدفع',
            'icon' => 'ti-credit-card',
            'fields' => ['tap_secret_key', 'tap_merchant_id'],
        ],
        'mail' => [
            'label' => 'البريد الإلكتروني',
            'icon' => 'ti-mail',
            'fields' => ['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'],
        ],
    ];

    /**
     * Every setting by name: the config key it overrides, its type, its
     * label, and whether it is a secret stored encrypted and never shown again.
     *
     * @var array<string, array{key: string, type: 'int'|'string'|'bool'|'list', label: string, secret?: bool}>
     */
    public const FIELDS = [
        'agent_price' => ['key' => 'tokhoom.plans.agent.price', 'type' => 'int', 'label' => 'سعر خطة الوسيط'],
        'agent_listing_limit' => ['key' => 'tokhoom.plans.agent.listing_limit', 'type' => 'int', 'label' => 'حد عقارات خطة الوسيط'],
        'office_price' => ['key' => 'tokhoom.plans.office.price', 'type' => 'int', 'label' => 'سعر خطة المكتب'],
        'office_seats' => ['key' => 'tokhoom.plans.office.seats', 'type' => 'int', 'label' => 'مقاعد خطة المكتب'],
        'plus_price' => ['key' => 'tokhoom.plans.plus.price', 'type' => 'int', 'label' => 'سعر خطة المكتب بلس'],
        'plus_seats' => ['key' => 'tokhoom.plans.plus.seats', 'type' => 'int', 'label' => 'مقاعد خطة المكتب بلس'],
        'extra_seat_price' => ['key' => 'tokhoom.extra_seat_price', 'type' => 'int', 'label' => 'سعر المقعد الإضافي'],
        'trial_days' => ['key' => 'tokhoom.trial_days', 'type' => 'int', 'label' => 'مدة التجربة'],
        'collection_attempts' => ['key' => 'tokhoom.collection_attempts', 'type' => 'int', 'label' => 'محاولات الخصم التلقائي'],

        'seller_name' => ['key' => 'tokhoom.seller.name', 'type' => 'string', 'label' => 'اسم المنشأة'],
        'seller_vat_number' => ['key' => 'tokhoom.seller.vat_number', 'type' => 'string', 'label' => 'الرقم الضريبي'],
        'seller_cr_number' => ['key' => 'tokhoom.seller.cr_number', 'type' => 'string', 'label' => 'رقم السجل التجاري'],
        'seller_address' => ['key' => 'tokhoom.seller.address', 'type' => 'string', 'label' => 'العنوان'],
        'vat_rate' => ['key' => 'tokhoom.vat_rate', 'type' => 'int', 'label' => 'نسبة ضريبة القيمة المضافة'],

        'support_whatsapp' => ['key' => 'tokhoom.support.whatsapp', 'type' => 'string', 'label' => 'واتساب الدعم'],
        'support_phone' => ['key' => 'tokhoom.support.phone', 'type' => 'string', 'label' => 'هاتف الدعم'],
        'support_email' => ['key' => 'tokhoom.support.email', 'type' => 'string', 'label' => 'بريد الدعم'],
        'support_hours' => ['key' => 'tokhoom.support.hours', 'type' => 'string', 'label' => 'أوقات الدعم'],
        'demo_site_url' => ['key' => 'tokhoom.demo_site_url', 'type' => 'string', 'label' => 'رابط الموقع النموذجي'],

        'signups_open' => ['key' => 'tokhoom.signups_open', 'type' => 'bool', 'label' => 'استقبال حسابات جديدة'],
        'reserved_slugs' => ['key' => 'tokhoom.reserved_slugs', 'type' => 'list', 'label' => 'العناوين المحجوزة'],

        'tap_secret_key' => ['key' => 'services.tap.secret_key', 'type' => 'string', 'label' => 'المفتاح السري', 'secret' => true],
        'tap_merchant_id' => ['key' => 'services.tap.merchant_id', 'type' => 'string', 'label' => 'رقم التاجر'],

        'mail_mailer' => ['key' => 'mail.default', 'type' => 'string', 'label' => 'طريقة الإرسال'],
        'mail_host' => ['key' => 'mail.mailers.smtp.host', 'type' => 'string', 'label' => 'الخادم'],
        'mail_port' => ['key' => 'mail.mailers.smtp.port', 'type' => 'int', 'label' => 'المنفذ'],
        'mail_username' => ['key' => 'mail.mailers.smtp.username', 'type' => 'string', 'label' => 'اسم المستخدم'],
        'mail_password' => ['key' => 'mail.mailers.smtp.password', 'type' => 'string', 'label' => 'كلمة المرور', 'secret' => true],
        'mail_from_address' => ['key' => 'mail.from.address', 'type' => 'string', 'label' => 'بريد المرسل'],
        'mail_from_name' => ['key' => 'mail.from.name', 'type' => 'string', 'label' => 'اسم المرسل'],
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

    public static function label(string $field): string
    {
        return self::FIELDS[$field]['label'] ?? $field;
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
