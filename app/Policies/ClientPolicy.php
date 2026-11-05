<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Members of the client's workspace; agents only for clients assigned to them.
     */
    public function view(User $user, Client $client): bool
    {
        $membership = $user->currentMembership();

        return $membership !== null
            && $membership->workspace_id === $client->workspace_id
            && ($membership->seesWholeWorkspace() || $client->agent_id === $user->id);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }
}
