<?php

namespace App\Http\Controllers;

use App\Support\Subscriptions;
use App\Support\Tap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TapReturnController extends Controller
{
    /**
     * Where Tap's payment page sends the owner back, after paying an invoice
     * or saving a card. The result is looked up from Tap rather than trusted
     * from the address.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['tap_id' => ['required', 'string', 'max:100']]);

        $tapId = (string) $request->query('tap_id');
        $workspaceId = $request->user()->current_workspace_id;

        if (str_starts_with($tapId, 'auth_')) {
            $authorization = Tap::authorization($tapId);
            $workspace = Subscriptions::saveCard($authorization);

            abort_unless($workspace && $workspace->id === $workspaceId, 404);

            return redirect()->route('billing')->with('toast', data_get($authorization, 'status') === 'AUTHORIZED'
                ? ['message' => 'تم حفظ البطاقة للتجديد التلقائي، ويُلغى مبلغ التحقق تلقائياً', 'kind' => 'ok']
                : ['message' => 'لم يكتمل حفظ البطاقة. حاول مرة أخرى أو استخدم بطاقة أخرى', 'kind' => 'err']);
        }

        $invoice = Subscriptions::settle(Tap::charge($tapId));

        abort_unless($invoice && $invoice->workspace_id === $workspaceId, 404);

        return redirect()->route('billing')->with('toast', $invoice->paid_at
            ? ['message' => 'تم الدفع وحفظ البطاقة للتجديد التلقائي', 'kind' => 'ok']
            : ['message' => 'لم تكتمل عملية الدفع. حاول مرة أخرى أو استخدم بطاقة أخرى', 'kind' => 'err']);
    }
}
