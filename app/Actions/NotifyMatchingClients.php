<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\Listing;
use App\Notifications\ListingMatchesRequest;
use App\Support\Matcher;

/**
 * Tell each client's agent when a newly published listing fits the client's request.
 */
class NotifyMatchingClients
{
    public function __construct(private Matcher $matcher) {}

    public function handle(Listing $listing): int
    {
        $clients = $this->matcher->clientsFor($listing);

        $clients->each(fn (Client $client) => $client->agent->notify(new ListingMatchesRequest($listing, $client)));

        return $clients->count();
    }
}
