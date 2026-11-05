<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\RejectionReason;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Workspace;
use App\Notifications\WorkspaceApproved;
use App\Notifications\WorkspaceRejected;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * License approval queue for new offices and independent agents.
 */
#[Title('طلبات اعتماد التراخيص')]
class Approvals extends Component
{
    use HasDialogs, ShowsToasts;

    #[Locked]
    public ?int $workspaceId = null;

    public string $reason = '';

    public string $message = '';

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    private function pending(int $id): Workspace
    {
        return Workspace::pendingApproval()->findOrFail($id);
    }

    public function approve(int $id): void
    {
        $workspace = $this->pending($id);

        $workspace->forceFill([
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
        ])->save();

        $this->owner($workspace)?->notify(new WorkspaceApproved($workspace));

        $this->toast('تم اعتماد '.$workspace->name);
    }

    public function openReject(int $id): void
    {
        $this->workspaceId = $this->pending($id)->id;
        $this->reset('reason', 'message');
        $this->openDialog('reject');
    }

    public function reject(): void
    {
        $this->validate([
            'reason' => ['required', Rule::enum(RejectionReason::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'اختر سبب الرفض.']);

        $workspace = $this->pending($this->workspaceId);

        $workspace->forceFill([
            'approval_status' => ApprovalStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => RejectionReason::from($this->reason),
            'rejection_message' => trim($this->message) ?: null,
        ])->save();

        $this->owner($workspace)?->notify(new WorkspaceRejected($workspace));

        $this->closeDialog();
        $this->toast('تم رفض الطلب وإبلاغ المتقدم', 'info');
    }

    private function owner(Workspace $workspace)
    {
        return $workspace->memberships()->where('role', MemberRole::Owner)->with('user')->first()?->user;
    }

    public function render(): View
    {
        return view('livewire.admin.approvals', [
            'queue' => Workspace::pendingApproval()->with(['memberships' => fn ($query) => $query->where('role', MemberRole::Owner)->with('user')])->oldest()->get(),
            'rejecting' => $this->dialog === 'reject' && $this->workspaceId ? Workspace::find($this->workspaceId) : null,
        ]);
    }
}
