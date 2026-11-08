<?php

namespace App\Notifications;

use App\Models\Listing;

/**
 * Sent to the listing's agent and the office's owners and managers when a
 * platform admin takes the listing off the public site.
 */
class ListingRemoved extends DashboardNotification
{
    public function __construct(public Listing $listing) {}

    protected function icon(): string
    {
        return 'ti-eye-off';
    }

    protected function tone(): string
    {
        return 'danger';
    }

    protected function text(): string
    {
        return 'أوقفت إدارة المنصة عرض «'.$this->listing->title.'»'.($this->listing->removal_reason ? ': '.$this->listing->removal_reason : '');
    }

    protected function url(): string
    {
        return route('listings.show', $this->listing);
    }
}
