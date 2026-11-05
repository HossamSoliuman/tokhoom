<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The public site of an office or agent, served on its own host. A bare
 * version until the full site design is built.
 */
class SiteController extends Controller
{
    public function home(Request $request): View
    {
        $workspace = $this->site($request);

        return view('site.home', [
            'workspace' => $workspace,
            'listings' => $this->liveListings($workspace)->latest('published_at')->get(),
        ]);
    }

    public function listing(Request $request, string $code): View
    {
        $workspace = $this->site($request);

        return view('site.listing', [
            'workspace' => $workspace,
            'listing' => $this->liveListings($workspace)->where('short_code', $code)->firstOrFail(),
        ]);
    }

    private function site(Request $request): Workspace
    {
        return $request->attributes->get('site');
    }

    /**
     * @return Builder<Listing>
     */
    private function liveListings(Workspace $workspace): Builder
    {
        return $workspace->listings()->getQuery()
            ->whereIn('status', array_filter(ListingStatus::cases(), fn (ListingStatus $status) => $status->isPublic()))
            ->where(fn (Builder $query) => $query->whereNull('ad_license_expires_on')->orWhereDate('ad_license_expires_on', '>=', today()));
    }
}
