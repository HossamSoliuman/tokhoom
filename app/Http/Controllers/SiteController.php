<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\Membership;
use App\Models\Workspace;
use App\Support\SiteTraffic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * The public site of an office or agent, served on its own host: the home
 * page, an agent's page and a listing's page.
 */
class SiteController extends Controller
{
    public function home(Request $request): View
    {
        $workspace = $this->site($request);

        SiteTraffic::arrive($request, $workspace);

        return view('site.home', [
            'workspace' => $workspace,
            'agents' => $workspace->isOffice() ? $this->agents($workspace) : collect(),
        ]);
    }

    public function agent(Request $request, int $agent): View
    {
        $workspace = $this->site($request);

        abort_unless($workspace->isOffice(), 404);

        $membership = $workspace->memberships()->active()->where('user_id', $agent)->with('user')->firstOrFail();

        SiteTraffic::arrive($request, $workspace);

        return view('site.agent', [
            'workspace' => $workspace,
            'membership' => $membership,
        ]);
    }

    public function listing(Request $request, string $code): View|Response
    {
        $workspace = $this->site($request);

        $listing = $workspace->listings()
            ->where('short_code', $code)
            ->where('status', '!=', ListingStatus::Draft)
            ->firstOrFail();

        SiteTraffic::arrive($request, $workspace);

        if (! $listing->isLive()) {
            return response()->view('site.gone', [
                'workspace' => $workspace,
                'others' => $workspace->listings()->live()->with('coverPhoto')->latest('published_at')->limit(3)->get(),
            ], 410);
        }

        SiteTraffic::viewListing($request, $workspace, $listing);

        $listing->load(['photos', 'agent']);

        return view('site.listing', [
            'workspace' => $workspace,
            'listing' => $listing,
            'membership' => $workspace->memberships()->where('user_id', $listing->agent_id)->with('user')->first(),
            'photos' => $listing->photos->map(fn (ListingPhoto $photo) => ['url' => $photo->url(), 'label' => $photo->placeholderLabel()])->values(),
        ]);
    }

    private function site(Request $request): Workspace
    {
        return $request->attributes->get('site');
    }

    /**
     * Members with listings on the site, busiest first.
     *
     * @return Collection<int, array{membership: Membership, listings: int}>
     */
    private function agents(Workspace $workspace): Collection
    {
        $counts = $workspace->listings()->live()
            ->selectRaw('agent_id, count(*) as total')
            ->groupBy('agent_id')
            ->pluck('total', 'agent_id');

        return $workspace->memberships()->active()
            ->whereIn('user_id', $counts->keys())
            ->with('user')
            ->get()
            ->map(fn (Membership $membership) => ['membership' => $membership, 'listings' => (int) $counts[$membership->user_id]])
            ->sortByDesc('listings')
            ->values();
    }
}
