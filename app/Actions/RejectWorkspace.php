<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\RejectionReason;
use App\Models\AdminAction;
use App\Models\Workspace;
use App\Notifications\WorkspaceRejected;

/**
 * Turn down a license application and tell the applicant why.
 */
class RejectWorkspace
{
    public function handle(Workspace $workspace, RejectionReason $reason, ?string $message = null): void
    {
        $workspace->forceFill([
            'approval_status' => ApprovalStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'rejection_message' => filled($message) ? trim($message) : null,
        ])->save();

        AdminAction::record('workspace.rejected', $workspace, properties: ['reason' => $reason->value]);

        $workspace->owner()?->notify(new WorkspaceRejected($workspace));
    }
}
