<?php

namespace App\Http\Controllers;

use App\Support\Subscriptions;
use App\Support\Tap;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TapWebhookController extends Controller
{
    /**
     * Tap's server-to-server notice of a charge's final state, signed with the secret key.
     */
    public function __invoke(Request $request): Response
    {
        $charge = $request->json()->all();

        abort_unless(Tap::validWebhook($charge, $request->header('hashstring')), 403);

        match (data_get($charge, 'object')) {
            'charge' => Subscriptions::settle($charge),
            'authorize' => Subscriptions::saveCard($charge),
            default => null,
        };

        return response()->noContent(200);
    }
}
