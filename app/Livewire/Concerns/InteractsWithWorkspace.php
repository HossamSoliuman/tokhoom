<?php

namespace App\Livewire\Concerns;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;

/**
 * Access to the signed-in member and the workspace they are working in.
 */
trait InteractsWithWorkspace
{
    protected function user(): User
    {
        return auth()->user();
    }

    protected function membership(): Membership
    {
        $membership = $this->user()->currentMembership();

        abort_unless($membership !== null, 403);

        return $membership;
    }

    protected function workspace(): Workspace
    {
        return $this->membership()->workspace;
    }

    protected function seesWholeWorkspace(): bool
    {
        return $this->membership()->seesWholeWorkspace();
    }
}
