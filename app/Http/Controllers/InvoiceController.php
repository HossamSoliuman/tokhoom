<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * A printable invoice for the current workspace.
     */
    public function __invoke(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->workspace_id === $request->user()->current_workspace_id, 404);

        return view('billing.invoice', ['invoice' => $invoice->load('workspace')]);
    }
}
