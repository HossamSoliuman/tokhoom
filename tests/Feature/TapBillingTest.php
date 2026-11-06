<?php

use App\Livewire\Billing\Index as Billing;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Notifications\InvoiceDue;
use App\Notifications\PaymentFailed;
use App\Support\Subscriptions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

const TAP_KEY = 'sk_test_tokhoom';

/**
 * A charge as Tap reports it, for an invoice.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tapCharge(Invoice $invoice, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'chg_'.$invoice->id,
        'object' => 'charge',
        'status' => 'CAPTURED',
        'amount' => (float) $invoice->amount,
        'currency' => 'SAR',
        'metadata' => ['invoice' => (string) $invoice->id],
        'reference' => ['transaction' => $invoice->number, 'gateway' => '123456', 'payment' => '9876543'],
        'transaction' => ['created' => '1730900000000'],
        'customer' => ['id' => 'cus_1'],
        'card' => ['id' => 'card_1', 'brand' => 'MADA', 'last_four' => '4242', 'exp_month' => 7, 'exp_year' => 2029],
        'payment_agreement' => ['id' => 'payment_agreement_1'],
    ], $overrides);
}

/**
 * A workspace whose trial has ended, so its first month is due.
 */
function dueWorkspace(bool $savedCard = false): array
{
    [$owner, $workspace] = member();

    $workspace->forceFill(array_merge(
        ['trial_ends_at' => now()->subDay()],
        $savedCard ? ['tap_customer_id' => 'cus_1', 'tap_card_id' => 'card_1', 'tap_payment_agreement_id' => 'payment_agreement_1'] : [],
    ))->save();

    return [$owner, $workspace->fresh()];
}

beforeEach(function () {
    config(['services.tap.secret_key' => TAP_KEY]);
    Http::preventStrayRequests();
});

test('an owner pays an open invoice on Tap and the card is offered for saving', function () {
    Http::fake(['api.tap.company/v2/charges' => Http::response(['id' => 'chg_1', 'status' => 'INITIATED', 'transaction' => ['url' => 'https://checkout.tap.company/pay']])]);
    [$owner, $workspace] = dueWorkspace();
    $invoice = Subscriptions::dueInvoice($workspace);

    Livewire::actingAs($owner)->test(Billing::class)
        ->call('payInvoice', $invoice->id)
        ->assertRedirect('https://checkout.tap.company/pay');

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer '.TAP_KEY)
        && $request['amount'] == $workspace->monthlyTotal()
        && $request['currency'] === 'SAR'
        && $request['save_card'] === true
        && $request['customer_initiated'] === true
        && $request['source']['id'] === 'src_all'
        && $request['reference']['transaction'] === $invoice->number
        && $request['redirect']['url'] === route('billing.tap-return'));
});

test('returning from Tap marks the invoice paid and keeps the card for renewals', function () {
    [$owner, $workspace] = dueWorkspace();
    $invoice = Subscriptions::dueInvoice($workspace);
    Http::fake(['api.tap.company/v2/charges/chg_'.$invoice->id => Http::response(tapCharge($invoice))]);

    $this->actingAs($owner)
        ->get(route('billing.tap-return', ['tap_id' => 'chg_'.$invoice->id]))
        ->assertRedirect(route('billing'));

    expect($invoice->fresh()->paid_at)->not->toBeNull()
        ->and($invoice->fresh()->tap_charge_id)->toBe('chg_'.$invoice->id);
    expect($workspace->fresh())
        ->hasSavedCard()->toBeTrue()
        ->card_brand->toBe('MADA')
        ->card_last_four->toBe('4242')
        ->card_expiry->toBe('07/2029')
        ->and($workspace->fresh()->paid_until->toDateString())->toBe(today()->addMonthNoOverflow()->toDateString());
});

test('a charge Tap did not capture in full leaves the invoice open', function (array $overrides) {
    [$owner, $workspace] = dueWorkspace();
    $invoice = Subscriptions::dueInvoice($workspace);
    Http::fake(['api.tap.company/v2/charges/*' => Http::response(tapCharge($invoice, $overrides))]);

    $this->actingAs($owner)
        ->get(route('billing.tap-return', ['tap_id' => 'chg_'.$invoice->id]))
        ->assertRedirect(route('billing'));

    expect($invoice->fresh()->paid_at)->toBeNull()
        ->and($workspace->fresh()->hasSavedCard())->toBeFalse();
})->with([
    'declined' => [['status' => 'DECLINED']],
    'smaller amount' => [['amount' => 1.0]],
    'other currency' => [['currency' => 'USD']],
]);

test('the return page does not reveal another workspace invoice', function () {
    [, $workspace] = dueWorkspace();
    [$otherOwner] = member();
    $invoice = Subscriptions::dueInvoice($workspace);
    Http::fake(['api.tap.company/v2/charges/*' => Http::response(tapCharge($invoice, ['status' => 'DECLINED']))]);

    $this->actingAs($otherOwner)
        ->get(route('billing.tap-return', ['tap_id' => 'chg_'.$invoice->id]))
        ->assertNotFound();
});

test('the Tap webhook settles a charge only with a valid signature', function () {
    [, $workspace] = dueWorkspace();
    $invoice = Subscriptions::dueInvoice($workspace);
    $charge = tapCharge($invoice);
    $signed = 'x_id'.$charge['id'].'x_amount'.number_format($charge['amount'], 2, '.', '').'x_currencySAR'
        .'x_gateway_reference123456x_payment_reference9876543x_statusCAPTUREDx_created1730900000000';

    $this->postJson(route('webhooks.tap'), $charge, ['hashstring' => hash_hmac('sha256', $signed, 'wrong-key')])->assertForbidden();
    expect($invoice->fresh()->paid_at)->toBeNull();

    $this->postJson(route('webhooks.tap'), $charge, ['hashstring' => hash_hmac('sha256', $signed, TAP_KEY)])->assertOk();
    expect($invoice->fresh()->paid_at)->not->toBeNull();
});

test('a charge reported twice extends the paid month once', function () {
    [, $workspace] = dueWorkspace();
    $invoice = Subscriptions::dueInvoice($workspace);

    Subscriptions::settle(tapCharge($invoice));
    Subscriptions::settle(tapCharge($invoice));

    expect($workspace->fresh()->paid_until->toDateString())->toBe(today()->addMonthNoOverflow()->toDateString());
});

test('renewals charge the saved card of a due workspace', function () {
    Http::fake([
        'api.tap.company/v2/tokens' => Http::response(['id' => 'tok_1']),
        'api.tap.company/v2/charges' => fn (Request $request) => Http::response(tapCharge(Invoice::findOrFail((int) $request['metadata']['invoice']))),
    ]);
    [, $workspace] = dueWorkspace(savedCard: true);

    $this->artisan('tokhoom:renew-subscriptions')->assertSuccessful();

    $invoice = $workspace->invoices()->sole();
    expect($invoice->paid_at)->not->toBeNull()
        ->and($invoice->amount)->toBe($workspace->monthlyTotal());
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tap.company/v2/tokens'
        && $request['saved_card'] === ['card_id' => 'card_1', 'customer_id' => 'cus_1']);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tap.company/v2/charges'
        && $request['customer_initiated'] === false
        && $request['payment_agreement']['id'] === 'payment_agreement_1'
        && $request['source']['id'] === 'tok_1');
});

test('a declined renewal is retried once a day, three times, and the owner is told each time', function () {
    Notification::fake();
    Http::fake([
        'api.tap.company/v2/tokens' => Http::response(['id' => 'tok_1']),
        'api.tap.company/v2/charges' => fn (Request $request) => Http::response(tapCharge(Invoice::findOrFail((int) $request['metadata']['invoice']), ['status' => 'DECLINED'])),
    ]);
    [$owner, $workspace] = dueWorkspace(savedCard: true);

    $this->artisan('tokhoom:renew-subscriptions');
    $this->artisan('tokhoom:renew-subscriptions');
    foreach (range(1, 3) as $day) {
        $this->travel(1)->days();
        $this->artisan('tokhoom:renew-subscriptions');
    }

    expect($workspace->invoices()->sole())
        ->paid_at->toBeNull()
        ->payment_attempts->toBe(Subscriptions::MAX_AUTOMATIC_ATTEMPTS);
    Notification::assertSentToTimes($owner, PaymentFailed::class, Subscriptions::MAX_AUTOMATIC_ATTEMPTS);
});

test('without a saved card the owner is asked once to pay', function () {
    Notification::fake();
    [$owner, $workspace] = dueWorkspace();

    $this->artisan('tokhoom:renew-subscriptions');
    $this->artisan('tokhoom:renew-subscriptions');

    expect($workspace->invoices()->sole()->paid_at)->toBeNull();
    Notification::assertSentToTimes($owner, InvoiceDue::class, 1);
    Http::assertNothingSent();
});

test('workspaces on trial or with a paid month are not billed', function (array $state) {
    [, $workspace] = member();
    $workspace->forceFill($state)->save();

    $this->artisan('tokhoom:renew-subscriptions');

    expect($workspace->invoices()->count())->toBe(0);
})->with([
    'on trial' => [['trial_ends_at' => now()->addDays(3)]],
    'paid month running' => [['trial_ends_at' => now()->subMonth(), 'paid_until' => now()->addDays(10)->toDateString()]],
]);

test('suspended and unapproved workspaces are not billed', function (Closure $makeWorkspace) {
    $workspace = $makeWorkspace();
    $workspace->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $this->artisan('tokhoom:renew-subscriptions');

    expect($workspace->invoices()->count())->toBe(0);
})->with([
    'suspended' => fn () => Workspace::factory()->suspended()->create(),
    'pending approval' => fn () => Workspace::factory()->pending()->create(),
]);

/**
 * A card hold as Tap reports it, for a workspace.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tapAuthorization(Workspace $workspace, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'auth_'.$workspace->id,
        'object' => 'authorize',
        'status' => 'AUTHORIZED',
        'amount' => 1.0,
        'currency' => 'SAR',
        'metadata' => ['workspace' => (string) $workspace->id],
        'reference' => ['transaction' => 'CARD-'.$workspace->id.'-1730900000', 'gateway' => '123456', 'payment' => '9876543'],
        'transaction' => ['created' => '1730900000000'],
        'customer' => ['id' => 'cus_1'],
        'card' => ['id' => 'card_1', 'brand' => 'VISA', 'last_four' => '1111'],
        'payment_agreement' => ['id' => 'payment_agreement_1'],
    ], $overrides);
}

test('with nothing to pay a card is saved through a 1 riyal hold that Tap voids', function () {
    Http::fake(['api.tap.company/v2/authorize' => Http::response(['id' => 'auth_1', 'status' => 'INITIATED', 'transaction' => ['url' => 'https://checkout.tap.company/hold']])]);
    [$owner, $workspace] = member();

    Livewire::actingAs($owner)->test(Billing::class)
        ->call('changeCard')
        ->assertRedirect('https://checkout.tap.company/hold');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tap.company/v2/authorize'
        && $request['amount'] == 1
        && $request['save_card'] === true
        && $request['auto'] === ['type' => 'VOID', 'time' => 1]
        && $request['metadata']['workspace'] === (string) $workspace->id);
});

test('returning from a confirmed hold keeps the card without paying anything', function () {
    [$owner, $workspace] = member();
    Http::fake(['api.tap.company/v2/authorize/auth_'.$workspace->id => Http::response(tapAuthorization($workspace))]);

    $this->actingAs($owner)
        ->get(route('billing.tap-return', ['tap_id' => 'auth_'.$workspace->id]))
        ->assertRedirect(route('billing'));

    expect($workspace->fresh())
        ->hasSavedCard()->toBeTrue()
        ->card_last_four->toBe('1111')
        ->paid_until->toBeNull()
        ->and($workspace->invoices()->count())->toBe(0);
});

test('a hold Tap did not authorize saves no card', function () {
    [$owner, $workspace] = member();
    Http::fake(['api.tap.company/v2/authorize/*' => Http::response(tapAuthorization($workspace, ['status' => 'DECLINED']))]);

    $this->actingAs($owner)->get(route('billing.tap-return', ['tap_id' => 'auth_'.$workspace->id]))->assertRedirect(route('billing'));

    expect($workspace->fresh()->hasSavedCard())->toBeFalse();
});

test('a new card without a payment agreement replaces the old card entirely', function () {
    [, $workspace] = dueWorkspace(savedCard: true);

    Subscriptions::saveCard(tapAuthorization($workspace, ['card' => ['id' => 'card_2'], 'payment_agreement' => null]));

    expect($workspace->fresh())
        ->tap_card_id->toBe('card_2')
        ->tap_payment_agreement_id->toBeNull()
        ->hasSavedCard()->toBeFalse();
});

test('the Tap webhook saves a card from a signed authorization', function () {
    [, $workspace] = member();
    $authorization = tapAuthorization($workspace);
    $signed = 'x_id'.$authorization['id'].'x_amount1.00x_currencySAR'
        .'x_gateway_reference123456x_payment_reference9876543x_statusAUTHORIZEDx_created1730900000000';

    $this->postJson(route('webhooks.tap'), $authorization, ['hashstring' => hash_hmac('sha256', $signed, TAP_KEY)])->assertOk();

    expect($workspace->fresh()->hasSavedCard())->toBeTrue();
});
