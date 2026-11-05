<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Members of the listing's workspace; agents only for their own listings.
     */
    public function view(User $user, Listing $listing): bool
    {
        $membership = $user->currentMembership();

        return $membership !== null
            && $membership->workspace_id === $listing->workspace_id
            && ($membership->seesWholeWorkspace() || $listing->agent_id === $user->id);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $this->view($user, $listing);
    }
}
