<?php

namespace App\Http\Controllers;

use App\Enums\LeadSource;
use App\Enums\SiteEventType;
use App\Models\Listing;
use App\Models\SiteEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resolve a listing short link (tkh.sa/{code}) to the listing on its office
 * site. Links opened from a QR code carry ?src=qr.
 */
class ShortLinkController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $listing = Listing::where('short_code', $code)->with('workspace')->firstOrFail();

        $source = $request->query('src') === 'qr' ? LeadSource::Qr : LeadSource::SharedLink;

        defer(fn () => SiteEvent::create([
            'workspace_id' => $listing->workspace_id,
            'listing_id' => $listing->id,
            'type' => SiteEventType::ListingView,
            'source' => $source,
            'occurred_at' => now(),
        ]));

        return redirect()->away($listing->publicUrl().'?src='.$source->value);
    }
}
