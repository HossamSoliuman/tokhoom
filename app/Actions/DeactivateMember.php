<?php

namespace App\Actions;

use App\Enums\DealStage;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use Illuminate\Support\Facades\DB;

/**
 * Deactivate a member and hand their listings, clients, open deals, open
 * follow-ups and inquiries to a colleague, so the office keeps its work.
 */
class DeactivateMember
{
    public function handle(Membership $leaving, Membership $recipient): void
    {
        DB::transaction(function () use ($leaving, $recipient): void {
            $scope = fn (string $model, string $column = 'agent_id') => $model::where('workspace_id', $leaving->workspace_id)
                ->where($column, $leaving->user_id);

            $scope(Listing::class)->update(['agent_id' => $recipient->user_id]);
            $scope(Client::class)->update(['agent_id' => $recipient->user_id]);
            $scope(Inquiry::class)->update(['agent_id' => $recipient->user_id]);
            $scope(Deal::class)->whereNotIn('stage', [DealStage::Won, DealStage::Lost])->update(['agent_id' => $recipient->user_id]);
            $scope(Reminder::class, 'user_id')->whereNull('completed_at')->update(['user_id' => $recipient->user_id]);

            $leaving->update(['deactivated_at' => now()]);

            $user = $leaving->user;

            if ($user->current_workspace_id === $leaving->workspace_id) {
                $user->switchWorkspace();
            }
        });
    }
}
