<?php

namespace App\Support;

use App\Enums\LeadSource;
use App\Enums\SiteEventType;
use App\Models\Listing;
use App\Models\SiteEvent;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * Visitor analytics for a public site. The session remembers where the
 * visitor came from (a shared link or a QR code), so later contact clicks and
 * inquiries keep that source. Visits and listing views count once per session.
 */
class SiteTraffic
{
    /**
     * Record the visit, and the source when the visitor arrived from a short link.
     */
    public static function arrive(Request $request, Workspace $workspace): void
    {
        $source = LeadSource::tryFrom((string) $request->query('src'));

        if (in_array($source, [LeadSource::SharedLink, LeadSource::Qr], true)) {
            $request->session()->put(self::key($workspace, 'source'), $source->value);
        }

        if ($request->session()->has(self::key($workspace, 'visited'))) {
            return;
        }

        $request->session()->put(self::key($workspace, 'visited'), true);

        self::record($workspace, SiteEventType::SiteVisit, null, self::source($workspace));
    }

    /**
     * Count a listing view. Short links count the view themselves before
     * redirecting here with ?src, so those arrivals are not counted twice.
     */
    public static function viewListing(Request $request, Workspace $workspace, Listing $listing): void
    {
        $key = self::key($workspace, 'viewed');
        $viewed = $request->session()->get($key, []);

        if (in_array($listing->id, $viewed, true)) {
            return;
        }

        $request->session()->put($key, [...$viewed, $listing->id]);

        if (! $request->query->has('src')) {
            self::record($workspace, SiteEventType::ListingView, $listing, LeadSource::Site);
        }
    }

    /**
     * Where this session's visitor came from: a shared link, a QR code or the site itself.
     */
    public static function source(Workspace $workspace): LeadSource
    {
        return LeadSource::tryFrom((string) session(self::key($workspace, 'source'))) ?? LeadSource::Site;
    }

    public static function record(Workspace $workspace, SiteEventType $type, ?Listing $listing = null, ?LeadSource $source = null): void
    {
        SiteEvent::create([
            'workspace_id' => $workspace->id,
            'listing_id' => $listing?->id,
            'type' => $type,
            'source' => $source ?? self::source($workspace),
            'occurred_at' => now(),
        ]);
    }

    private static function key(Workspace $workspace, string $name): string
    {
        return 'site.'.$workspace->id.'.'.$name;
    }
}
