<?php

use App\Livewire\Admin\Invoices;
use App\Livewire\Admin\Workspaces\Show;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Subscriptions;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create();
});

function invoiceFor(Workspace $workspace, string $number, array $attributes = []): Invoice
{
    $invoice = $workspace->invoices()->create(['number' => $number, 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);
    $invoice->forceFill($attributes)->save();

    return $invoice;
}

test('the invoices page lists every workspace and filters failed collections', function () {
    invoiceFor(Workspace::factory()->create(), 'TKH-OPEN');
    invoiceFor(Workspace::factory()->create(), 'TKH-FAILED', ['payment_attempts' => Subscriptions::MAX_AUTOMATIC_ATTEMPTS]);
    invoiceFor(Workspace::factory()->create(), 'TKH-PAID', ['paid_at' => now()]);

    $this->actingAs($this->admin)->get(route('admin.invoices'))
        ->assertOk()
        ->assertSee(['TKH-OPEN', 'TKH-FAILED', 'TKH-PAID']);

    Livewire::actingAs($this->admin)->test(Invoices::class)
        ->set('status', 'failed')
        ->assertSee('TKH-FAILED')
        ->assertDontSee(['TKH-OPEN', 'TKH-PAID']);
});

test('a manual payment needs a note, marks the invoice paid and extends the month', function () {
    $this->travelTo('2026-11-08 10:00');
    $workspace = Workspace::factory()->create(['trial_ends_at' => now()->subMonth()]);
    $workspace->forceFill(['paid_until' => '2026-11-01'])->save();
    $invoice = invoiceFor($workspace, 'TKH-MANUAL');

    Livewire::actingAs($this->admin)->test(Invoices::class)
        ->call('openPayment', $invoice->id)
        ->call('recordPayment')
        ->assertHasErrors(['paymentNote' => 'required'])
        ->set('paymentNote', 'تحويل بنكي 4471')
        ->call('recordPayment')
        ->assertHasNoErrors()
        ->assertSee('يدوي');

    expect($invoice->fresh())
        ->paid_at->not->toBeNull()
        ->payment_note->toBe('تحويل بنكي 4471')
        ->recorded_by->toBe($this->admin->id)
        ->and($workspace->fresh()->paid_until->toDateString())->toBe('2026-12-08')
        ->and($workspace->adminActions()->where('type', 'invoice.paid_manually')->exists())->toBeTrue();
});

test('a paid invoice cannot be paid again', function () {
    $invoice = invoiceFor(Workspace::factory()->create(), 'TKH-PAID', ['paid_at' => now()]);

    Livewire::actingAs($this->admin)->test(Invoices::class)
        ->call('openPayment', $invoice->id)
        ->assertNotFound();
});

test('the workspace page only records payments for its own invoices', function () {
    $workspace = Workspace::factory()->create();
    $other = invoiceFor(Workspace::factory()->create(), 'TKH-OTHER');

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('openPayment', $other->id)
        ->assertNotFound();
});

test('the invoices export follows the status filter', function () {
    invoiceFor(Workspace::factory()->create(), 'TKH-OPEN');
    invoiceFor(Workspace::factory()->create(), 'TKH-PAID', ['paid_at' => now()]);

    $csv = $this->actingAs($this->admin)->get(route('admin.invoices.export', ['status' => 'paid']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('TKH-PAID')->not->toContain('TKH-OPEN');
});

test('admins open any printable invoice and members cannot use the admin link', function () {
    [$owner] = member();
    $invoice = invoiceFor(Workspace::factory()->create(), 'TKH-PRINT');

    $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('TKH-PRINT');
    $this->actingAs($owner)->get(route('admin.invoices.show', $invoice))->assertForbidden();
});
