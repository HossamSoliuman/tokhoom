<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Any workspace's printable invoice, for platform admins.
     */
    public function __invoke(Invoice $invoice): View
    {
        return view('billing.invoice', ['invoice' => $invoice->load('workspace')]);
    }
}
