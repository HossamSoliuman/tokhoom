<?php

namespace App\Support;

use App\Enums\ListingStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientRequest;
use App\Models\Listing;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Pairs client requests with available office listings that fit them.
 */
class Matcher
{
    /**
     * Matches for the clients a member can see, leaving out listings already sent.
     *
     * @return Collection<int, array{client: Client, listing: Listing}>
     */
    public function forMember(Membership $membership, int $limit = 5): Collection
    {
        $clients = Client::visibleTo($membership)->with('requests')->has('requests')->get();

        return $this->pairs($clients, $this->availableListings($membership->workspace_id))->take($limit)->values();
    }

    /**
     * Available listings that fit any of the client's requests.
     *
     * @return Collection<int, Listing>
     */
    public function forClient(Client $client): Collection
    {
        $client->loadMissing('requests');

        return $this->availableListings($client->workspace_id)
            ->filter(fn (Listing $listing) => $client->requests->contains(fn (ClientRequest $request) => $request->matches($listing)))
            ->values();
    }

    /**
     * Clients in the workspace whose requests the listing fits.
     *
     * @return EloquentCollection<int, Client>
     */
    public function clientsFor(Listing $listing): EloquentCollection
    {
        return Client::where('workspace_id', $listing->workspace_id)
            ->with(['requests', 'agent'])
            ->has('requests')
            ->get()
            ->filter(fn (Client $client) => $client->requests->contains(fn (ClientRequest $request) => $request->matches($listing)))
            ->values();
    }

    /**
     * @return EloquentCollection<int, Listing>
     */
    private function availableListings(int $workspaceId): EloquentCollection
    {
        return Listing::where('workspace_id', $workspaceId)
            ->where('status', ListingStatus::Available)
            ->where(fn ($query) => $query->whereNull('ad_license_expires_on')->orWhere('ad_license_expires_on', '>=', today()))
            ->with('coverPhoto', 'workspace')
            ->latest('published_at')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Client>  $clients
     * @param  EloquentCollection<int, Listing>  $listings
     * @return Collection<int, array{client: Client, listing: Listing}>
     */
    private function pairs(EloquentCollection $clients, EloquentCollection $listings): Collection
    {
        $sent = Activity::where('type', 'client.listing_sent')
            ->where('subject_type', (new Client)->getMorphClass())
            ->whereIn('subject_id', $clients->modelKeys())
            ->get(['subject_id', 'properties'])
            ->map(fn (Activity $activity) => $activity->subject_id.':'.($activity->properties['listing_id'] ?? ''))
            ->flip();

        $pairs = collect();

        foreach ($clients as $client) {
            foreach ($listings as $listing) {
                if ($sent->has($client->id.':'.$listing->id)) {
                    continue;
                }

                if ($client->requests->contains(fn (ClientRequest $request) => $request->matches($listing))) {
                    $pairs->push(['client' => $client, 'listing' => $listing]);
                }
            }
        }

        return $pairs;
    }
}
