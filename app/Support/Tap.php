<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Tap Payments: hosted checkout for the first payment, which saves the card
 * and a payment agreement, then charges on that saved card for renewals.
 */
class Tap
{
    public static function configured(): bool
    {
        return filled(config('services.tap.secret_key'));
    }

    /**
     * Start a payment the owner completes on Tap's hosted page (mada, Apple
     * Pay or card). Returns the charge, whose transaction.url is the page.
     *
     * @return array<string, mixed>
     */
    public static function checkout(Invoice $invoice, User $owner, string $redirectUrl, string $webhookUrl): array
    {
        $workspace = $invoice->workspace;

        return self::client()->post('/charges', [
            ...self::chargeBasics($invoice, $redirectUrl, $webhookUrl),
            'customer_initiated' => true,
            'threeDSecure' => true,
            'save_card' => true,
            'source' => ['id' => 'src_all'],
            'customer' => self::customer($workspace, $owner),
        ])->json();
    }

    /**
     * Save a card without an invoice to pay, such as during the trial: Tap
     * holds 1 riyal on its page and releases the hold within the hour.
     *
     * @return array<string, mixed>
     */
    public static function authorizeCard(Workspace $workspace, User $owner, string $redirectUrl, string $webhookUrl): array
    {
        return self::client()->post('/authorize', array_filter([
            'amount' => 1.0,
            'currency' => 'SAR',
            'customer_initiated' => true,
            'threeDSecure' => true,
            'save_card' => true,
            'auto' => ['type' => 'VOID', 'time' => 1],
            'description' => 'التحقق من البطاقة لتجديد الاشتراك تلقائياً',
            'metadata' => ['workspace' => (string) $workspace->id],
            'reference' => ['transaction' => 'CARD-'.$workspace->id.'-'.now()->timestamp],
            'merchant' => config('services.tap.merchant_id') ? ['id' => config('services.tap.merchant_id')] : null,
            'source' => ['id' => 'src_all'],
            'customer' => self::customer($workspace, $owner),
            'post' => ['url' => $webhookUrl],
            'redirect' => ['url' => $redirectUrl],
        ]))->json();
    }

    /**
     * @return array<string, mixed>
     */
    public static function authorization(string $authorizeId): array
    {
        return self::client()->get('/authorize/'.rawurlencode($authorizeId))->json();
    }

    /**
     * Charge the workspace's saved card under its payment agreement.
     *
     * @return array<string, mixed>
     */
    public static function chargeSavedCard(Invoice $invoice, string $redirectUrl, string $webhookUrl): array
    {
        $workspace = $invoice->workspace;

        $token = self::client()->post('/tokens', [
            'saved_card' => ['card_id' => $workspace->tap_card_id, 'customer_id' => $workspace->tap_customer_id],
        ])->json('id');

        return self::client()->post('/charges', [
            ...self::chargeBasics($invoice, $redirectUrl, $webhookUrl),
            'customer_initiated' => false,
            'threeDSecure' => false,
            'save_card' => false,
            'payment_agreement' => ['id' => $workspace->tap_payment_agreement_id],
            'source' => ['id' => $token],
            'customer' => ['id' => $workspace->tap_customer_id],
        ])->json();
    }

    /**
     * @return array<string, mixed>
     */
    public static function charge(string $chargeId): array
    {
        return self::client()->get('/charges/'.rawurlencode($chargeId))->json();
    }

    /**
     * Whether a webhook really came from Tap: its "hashstring" header is an
     * HMAC-SHA256 of the charge's or authorization's key fields with the secret key.
     *
     * @param  array<string, mixed>  $charge
     */
    public static function validWebhook(array $charge, ?string $hashstring): bool
    {
        if (! self::configured() || ! $hashstring) {
            return false;
        }

        $fields = 'x_id'.data_get($charge, 'id')
            .'x_amount'.number_format((float) data_get($charge, 'amount'), 2, '.', '')
            .'x_currency'.data_get($charge, 'currency')
            .'x_gateway_reference'.data_get($charge, 'reference.gateway')
            .'x_payment_reference'.data_get($charge, 'reference.payment')
            .'x_status'.data_get($charge, 'status')
            .'x_created'.data_get($charge, 'transaction.created');

        return hash_equals(hash_hmac('sha256', $fields, config('services.tap.secret_key')), $hashstring);
    }

    /**
     * The workspace's Tap customer, or the owner's details for a first payment.
     *
     * @return array<string, mixed>
     */
    private static function customer(Workspace $workspace, User $owner): array
    {
        return $workspace->tap_customer_id ? ['id' => $workspace->tap_customer_id] : array_filter([
            'first_name' => $owner->name,
            'email' => $owner->email,
            'phone' => $owner->mobile ? ['country_code' => '966', 'number' => ltrim($owner->mobile, '0')] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function chargeBasics(Invoice $invoice, string $redirectUrl, string $webhookUrl): array
    {
        return array_filter([
            'amount' => (float) $invoice->amount,
            'currency' => 'SAR',
            'description' => $invoice->description,
            'metadata' => ['invoice' => (string) $invoice->id],
            'reference' => ['transaction' => $invoice->number, 'order' => $invoice->number],
            'receipt' => ['email' => true, 'sms' => false],
            'merchant' => config('services.tap.merchant_id') ? ['id' => config('services.tap.merchant_id')] : null,
            'post' => ['url' => $webhookUrl],
            'redirect' => ['url' => $redirectUrl],
        ]);
    }

    private static function client(): PendingRequest
    {
        return Http::baseUrl(config('services.tap.base_url'))
            ->withToken(config('services.tap.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->throw();
    }
}
