<?php

namespace App\Actions;

use App\Enums\ListingStatus;
use App\Models\Activity;
use App\Models\Listing;
use Illuminate\Validation\ValidationException;

/**
 * Change a listing's status, publishing it the first time it goes live.
 */
class ChangeListingStatus
{
    public function __construct(private NotifyMatchingClients $notifyMatchingClients) {}

    public function handle(Listing $listing, ListingStatus $status): void
    {
        if ($listing->status === $status) {
            return;
        }

        if ($status->isPublic() && $listing->isRemoved()) {
            throw ValidationException::withMessages([
                'status' => 'أوقفت إدارة المنصة عرض هذا العقار. تواصل مع الدعم لإعادته.',
            ]);
        }

        if ($status->isPublic() && ! $listing->ad_license_number) {
            throw ValidationException::withMessages([
                'status' => 'أدخل رقم ترخيص الإعلان قبل نشر العقار.',
            ]);
        }

        $firstPublish = $listing->published_at === null && $status !== ListingStatus::Draft;

        $listing->status = $status;
        $listing->published_at ??= $status !== ListingStatus::Draft ? now() : null;
        $listing->price_changed_at ??= $listing->published_at;
        $listing->save();

        if ($firstPublish) {
            Activity::record($listing, 'listing.published', ['by' => auth()->user()?->name]);
        } else {
            Activity::record($listing, 'listing.status_changed', ['status' => $status->value]);
        }

        if ($firstPublish && $status === ListingStatus::Available) {
            $this->notifyMatchingClients->handle($listing);
        }
    }
}
