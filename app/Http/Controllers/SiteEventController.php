<?php

namespace App\Http\Controllers;

use App\Enums\SiteEventType;
use App\Models\Workspace;
use App\Support\SiteTraffic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Contact and share clicks on a public site, sent by the page as a beacon.
 */
class SiteEventController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('site');

        $data = $request->validate([
            'type' => ['required', Rule::in([SiteEventType::WhatsappClick->value, SiteEventType::CallClick->value, SiteEventType::Share->value])],
            'listing' => ['nullable', 'string', 'max:20'],
        ]);

        $listing = isset($data['listing'])
            ? $workspace->listings()->live()->where('short_code', $data['listing'])->first()
            : null;

        SiteTraffic::record($workspace, SiteEventType::from($data['type']), $listing);

        return response()->noContent();
    }
}
