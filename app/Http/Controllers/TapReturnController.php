<?php

namespace App\Http\Controllers;

use App\Support\Subscriptions;
use App\Support\Tap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TapReturnController extends Controller
{
    /**
     * Where Tap's payment page sends the owner back. The charge is looked up
     * from Tap rather than trusted from the address.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['tap_id' => ['required', 'string', 'max:100']]);

        $invoice = Subscriptions::settle(Tap::charge($request->query('tap_id')));

        abort_unless($invoice && $invoice->workspace_id === $request->user()->current_workspace_id, 404);

        return redirect()->route('billing')->with('toast', $invoice->paid_at
            ? ['message' => 'تم الدفع وحفظ البطاقة للتجديد التلقائي', 'kind' => 'ok']
            : ['message' => 'لم تكتمل عملية الدفع. حاول مرة أخرى أو استخدم بطاقة أخرى', 'kind' => 'err']);
    }
}
