<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\WorkspaceKind;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ApprovalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Create an office or independent-agent workspace, owned by the signing-up user
 * and waiting for license approval. The caller has had the owner accept the
 * terms of use and privacy policy, and the acceptance time is recorded here.
 */
class CreateWorkspace
{
    /**
     * @param  array{kind: WorkspaceKind, name: string, slug: string, city: string, cr_number: ?string, fal_number: string, license_expires_on: string}  $data
     */
    public function handle(User $owner, array $data): Workspace
    {
        $workspace = DB::transaction(function () use ($owner, $data): Workspace {
            $plan = $data['kind'] === WorkspaceKind::Office ? Plan::Office : Plan::Agent;

            $workspace = new Workspace([
                ...$data,
                'plan' => $plan,
                'seats' => $plan->includedSeats(),
                'trial_ends_at' => now()->addDays(config('tokhoom.trial_days')),
                'whatsapp' => $owner->mobile,
                'phone' => $owner->mobile,
            ]);
            $workspace->approval_status = ApprovalStatus::Pending;
            $workspace->terms_accepted_at = now();
            $workspace->save();

            $workspace->memberships()->create([
                'user_id' => $owner->id,
                'role' => MemberRole::Owner,
                'fal_number' => $data['kind'] === WorkspaceKind::Agent ? $data['fal_number'] : null,
                'fal_expires_on' => $data['kind'] === WorkspaceKind::Agent ? $data['license_expires_on'] : null,
            ]);

            $owner->forceFill(['current_workspace_id' => $workspace->id])->save();

            return $workspace;
        });

        Notification::send(User::where('is_platform_admin', true)->get(), new ApprovalRequested($workspace));

        return $workspace;
    }
}
