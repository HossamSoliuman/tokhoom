<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\Listing;

class ListingMatchesRequest extends DashboardNotification
{
    public function __construct(public Listing $listing, public Client $client) {}

    protected function icon(): string
    {
        return 'ti-arrows-exchange';
    }

    protected function tone(): string
    {
        return 'brand';
    }

    protected function text(): string
    {
        return 'عقار يطابق طلب '.$this->client->name.': '.$this->listing->title;
    }

    protected function url(): string
    {
        return route('clients.show', $this->client);
    }
}
