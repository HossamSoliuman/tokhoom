<?php

namespace App\Actions;

use App\Enums\MemberRole;
use App\Models\AdminAction;
use App\Models\Listing;
use App\Notifications\ListingRemoved;
use Illuminate\Support\Facades\Notification;

/**
 * Take a listing off its office's public site on the platform's behalf, and
 * tell the agent and the office's owners and managers why.
 */
class RemoveListing
{
    public function handle(Listing $listing, string $reason): void
    {
        $listing->forceFill(['removed_at' => now(), 'removal_reason' => $reason])->save();

        AdminAction::record('listing.removed', $listing->workspace, properties: ['code' => $listing->short_code, 'reason' => $reason]);

        $recipients = $listing->workspace->memberships()->active()
            ->whereIn('role', [MemberRole::Owner, MemberRole::Manager])
            ->with('user')
            ->get()
            ->pluck('user')
            ->push($listing->agent)
            ->filter()
            ->unique('id');

        Notification::send($recipients, new ListingRemoved($listing));
    }
}
