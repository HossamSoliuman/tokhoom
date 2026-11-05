<?php

namespace App\Notifications;

use App\Models\Listing;

class AdLicenseExpired extends DashboardNotification
{
    public function __construct(public Listing $listing) {}

    protected function icon(): string
    {
        return 'ti-file-alert';
    }

    protected function tone(): string
    {
        return 'warning';
    }

    protected function text(): string
    {
        return 'انتهى ترخيص إعلان «'.$this->listing->title.'». الإعلان مخفي من الموقع حتى التجديد';
    }

    protected function url(): string
    {
        return route('listings.edit', $this->listing);
    }
}
