<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\InvoiceDue;
use App\Notifications\PaymentFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Monthly subscription billing: one open invoice at a time, settled by a
 * confirmed Tap charge, renewed from the saved card when there is one.
 */
class Subscriptions
{
    /**
     * Automatic tries on the saved card, one a day, before the owner has to pay by hand.
     */
    public const MAX_AUTOMATIC_ATTEMPTS = 3;

    /**
     * The workspace's unpaid invoice, issued now for the coming month if there is none.
     */
    public static function dueInvoice(Workspace $workspace): Invoice
    {
        $open = $workspace->invoices()->whereNull('paid_at')->latest('id')->first();

        if ($open) {
            return $open;
        }

        return DB::transaction(function () use ($workspace): Invoice {
            $issued = Carbon::today();
            $extraSeats = $workspace->extraSeats();

            $invoice = $workspace->invoices()->create([
                'number' => (string) Str::uuid(),
                'description' => 'اشتراك '.$workspace->plan->label().' — '.Arabic::month($issued->month).' '.$issued->year
                    .($extraSeats > 0 ? ' · '.Arabic::countWithNumber($extraSeats, 'مقعد إضافي', 'مقاعد إضافية', 'مقعداً إضافياً') : ''),
                'amount' => $workspace->monthlyTotal(),
                'issued_on' => $issued->toDateString(),
            ]);

            $invoice->forceFill(['number' => 'TKH-'.$issued->format('Ym').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT)])->save();

            return $invoice;
        });
    }

    /**
     * Record a charge reported by Tap. A captured charge for the invoice's full
     * amount marks it paid, extends the paid month and keeps the saved card.
     * Returns the invoice the charge belongs to, paid or not.
     *
     * @param  array<string, mixed>  $charge
     */
    public static function settle(array $charge): ?Invoice
    {
        $invoice = Invoice::find((int) data_get($charge, 'metadata.invoice'));

        if (! $invoice || data_get($charge, 'reference.transaction') !== $invoice->number) {
            return null;
        }

        $captured = data_get($charge, 'status') === 'CAPTURED'
            && data_get($charge, 'currency') === 'SAR'
            && round((float) data_get($charge, 'amount'), 2) === round((float) $invoice->amount, 2);

        if (! $captured) {
            return $invoice;
        }

        return self::recordPayment($invoice, ['tap_charge_id' => data_get($charge, 'id')], self::cardDetails($charge));
    }

    /**
     * Record a payment made outside Tap, such as a bank transfer, entered by
     * a platform admin with a note saying how it was paid.
     */
    public static function recordManualPayment(Invoice $invoice, User $admin, string $note): Invoice
    {
        return self::recordPayment($invoice, ['recorded_by' => $admin->id, 'payment_note' => $note]);
    }

    /**
     * Mark the invoice paid and extend the paid month by one. An invoice that
     * is already paid is left as it is.
     *
     * @param  array<string, mixed>  $invoiceDetails
     * @param  array<string, mixed>  $workspaceDetails
     */
    private static function recordPayment(Invoice $invoice, array $invoiceDetails, array $workspaceDetails = []): Invoice
    {
        return DB::transaction(function () use ($invoice, $invoiceDetails, $workspaceDetails): Invoice {
            $invoice = Invoice::lockForUpdate()->find($invoice->id);

            if ($invoice->paid_at) {
                return $invoice;
            }

            $invoice->forceFill(['paid_at' => now(), ...$invoiceDetails])->save();

            $workspace = $invoice->workspace;
            $from = $workspace->nextChargeDate()->max(Carbon::today());

            $workspace->forceFill([
                'paid_until' => $from->copy()->addMonthNoOverflow()->toDateString(),
                ...$workspaceDetails,
            ])->save();

            return $invoice;
        });
    }

    /**
     * Keep the card from an authorization Tap confirmed, made to save a card
     * when there was no invoice to pay. Returns the workspace it belongs to.
     *
     * @param  array<string, mixed>  $authorization
     */
    public static function saveCard(array $authorization): ?Workspace
    {
        $workspace = Workspace::find((int) data_get($authorization, 'metadata.workspace'));

        if (! $workspace || ! str_starts_with((string) data_get($authorization, 'reference.transaction'), 'CARD-'.$workspace->id.'-')) {
            return null;
        }

        if (data_get($authorization, 'status') === 'AUTHORIZED') {
            $workspace->forceFill(self::cardDetails($authorization))->save();
        }

        return $workspace;
    }

    /**
     * The Tap customer and saved card a payment returned. A new card replaces
     * every field of the old one, including its payment agreement.
     *
     * @param  array<string, mixed>  $payment
     * @return array<string, ?string>
     */
    private static function cardDetails(array $payment): array
    {
        $customer = array_filter(['tap_customer_id' => data_get($payment, 'customer.id')]);

        if (! data_get($payment, 'card.id')) {
            return $customer;
        }

        return $customer + [
            'tap_card_id' => data_get($payment, 'card.id'),
            'tap_payment_agreement_id' => data_get($payment, 'payment_agreement.id'),
            'card_brand' => data_get($payment, 'card.brand') ?? data_get($payment, 'card.scheme'),
            'card_last_four' => data_get($payment, 'card.last_four'),
            'card_expiry' => data_get($payment, 'card.exp_month') && data_get($payment, 'card.exp_year')
                ? str_pad((string) data_get($payment, 'card.exp_month'), 2, '0', STR_PAD_LEFT).'/'.data_get($payment, 'card.exp_year')
                : null,
        ];
    }

    /**
     * Bill a workspace whose month is due: issue the invoice, then charge the
     * saved card at most once a day, or ask the owner to pay when there is none.
     */
    public static function renew(Workspace $workspace): void
    {
        $invoice = self::dueInvoice($workspace);
        $owner = $workspace->owner();

        if (! Tap::configured() || ! $workspace->hasSavedCard()) {
            if ($invoice->wasRecentlyCreated) {
                $owner?->notify(new InvoiceDue($invoice));
            }

            return;
        }

        if ($invoice->payment_attempts >= self::MAX_AUTOMATIC_ATTEMPTS || $invoice->last_attempted_at?->isToday()) {
            return;
        }

        $invoice->forceFill(['payment_attempts' => $invoice->payment_attempts + 1, 'last_attempted_at' => now()])->save();

        try {
            $charge = Tap::chargeSavedCard($invoice, route('billing'), route('webhooks.tap'));
        } catch (ConnectionException|RequestException $exception) {
            report($exception);
            $charge = [];
        }

        if (! self::settle($charge)?->paid_at) {
            $owner?->notify(new PaymentFailed);
        }
    }
}
