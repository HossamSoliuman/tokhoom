<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Arabic;

class InvoiceDue extends DashboardNotification
{
    public function __construct(public Invoice $invoice) {}

    protected function icon(): string
    {
        return 'ti-file-invoice';
    }

    protected function tone(): string
    {
        return 'warning';
    }

    protected function text(): string
    {
        return 'فاتورة الاشتراك بقيمة '.Arabic::money($this->invoice->amount).' بانتظار الدفع';
    }

    protected function url(): string
    {
        return route('billing');
    }
}
