<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Models\AdminAction;
use App\Models\Workspace;
use App\Notifications\WorkspaceApproved;

/**
 * Approve a workspace's license so its site goes live, including one that was
 * rejected before and has since sent corrected details.
 */
class ApproveWorkspace
{
    public function handle(Workspace $workspace): void
    {
        $workspace->forceFill([
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
            'rejection_message' => null,
        ])->save();

        AdminAction::record('workspace.approved', $workspace);

        $workspace->owner()?->notify(new WorkspaceApproved($workspace));
    }
}
