<?php

namespace App\Livewire\Concerns;

use App\Models\AdminAction;
use App\Models\Invoice;
use App\Support\Subscriptions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/**
 * The admin "record a payment" dialog for invoices paid outside Tap. Views
 * render it with x-admin.payment-dialog. Needs HasDialogs and ShowsToasts.
 */
trait RecordsManualPayments
{
    #[Locked]
    public ?int $payingInvoiceId = null;

    public string $paymentNote = '';

    /**
     * Invoices this page may record payments for.
     *
     * @return Builder<Invoice>
     */
    protected function payableInvoices(): Builder
    {
        return Invoice::query();
    }

    public function openPayment(int $invoiceId): void
    {
        $this->payingInvoiceId = $this->payableInvoices()->whereNull('paid_at')->findOrFail($invoiceId)->id;
        $this->reset('paymentNote');
        $this->openDialog('payment');
    }

    public function recordPayment(): void
    {
        $this->validate(['paymentNote' => ['required', 'string', 'max:255']], [
            'paymentNote.required' => 'اكتب كيف دُفعت الفاتورة، مثل رقم الحوالة البنكية.',
        ]);

        $invoice = $this->payableInvoices()->whereNull('paid_at')->with('workspace')->findOrFail($this->payingInvoiceId);

        Subscriptions::recordManualPayment($invoice, auth()->user(), trim($this->paymentNote));
        AdminAction::record('invoice.paid_manually', $invoice->workspace, properties: ['number' => $invoice->number, 'note' => trim($this->paymentNote)]);

        $this->closeDialog();
        $this->toast('تم تسجيل دفع الفاتورة '.$invoice->number);
    }

    protected function payingInvoice(): ?Invoice
    {
        return $this->dialog === 'payment' && $this->payingInvoiceId
            ? Invoice::with('workspace')->find($this->payingInvoiceId)
            : null;
    }
}
