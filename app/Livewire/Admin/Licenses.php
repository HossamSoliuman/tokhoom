<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\WorkspaceKind;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Models\Membership;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * FAL licenses of approved workspaces and their agents that ran out or run
 * out soon, so admins can follow up and record renewals.
 */
#[Title('التراخيص')]
class Licenses extends Component
{
    use HasDialogs, ShowsToasts;

    /**
     * "expired" or the number of days ahead to look.
     */
    #[Url]
    public string $window = '60';

    #[Locked]
    public ?int $workspaceId = null;

    public string $expiresOn = '';

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function openRenewal(int $id): void
    {
        $workspace = Workspace::findOrFail($id);

        $this->workspaceId = $workspace->id;
        $this->expiresOn = $workspace->license_expires_on->copy()->addYear()->toDateString();
        $this->openDialog('renew');
    }

    /**
     * Record the new expiry date of a renewed workspace license.
     */
    public function renew(): void
    {
        $this->validate(['expiresOn' => ['required', 'date', 'after:today']], [
            'expiresOn.after' => 'أدخل تاريخ انتهاء الترخيص بعد التجديد.',
        ], ['expiresOn' => 'تاريخ الانتهاء']);

        $workspace = Workspace::findOrFail($this->workspaceId);
        $workspace->update(['license_expires_on' => $this->expiresOn]);

        if ($workspace->kind === WorkspaceKind::Agent) {
            $workspace->memberships()->where('role', MemberRole::Owner)->update(['fal_expires_on' => $this->expiresOn]);
        }

        AdminAction::record('workspace.license_updated', $workspace, properties: ['expires_on' => $this->expiresOn]);

        $this->closeDialog();
        $this->toast('تم تحديث ترخيص '.$workspace->name);
    }

    private function until(): Carbon
    {
        return $this->window === 'expired' ? today()->subDay() : today()->addDays(in_array($this->window, ['30', '60', '90'], true) ? (int) $this->window : 60);
    }

    public function render(): View
    {
        $until = $this->until();
        $approved = fn (Builder $query) => $query->where('approval_status', ApprovalStatus::Approved)->whereNull('suspended_at');

        return view('livewire.admin.licenses', [
            'workspaces' => Workspace::query()
                ->tap($approved)
                ->whereDate('license_expires_on', '<=', $until)
                ->with(['memberships' => fn ($query) => $query->where('role', MemberRole::Owner)->with('user')])
                ->orderBy('license_expires_on')
                ->get(),
            'members' => Membership::active()
                ->whereDate('fal_expires_on', '<=', $until)
                ->whereHas('workspace', fn (Builder $query) => $query->tap($approved)->where('kind', WorkspaceKind::Office))
                ->with(['user', 'workspace'])
                ->orderBy('fal_expires_on')
                ->get(),
            'renewing' => $this->dialog === 'renew' && $this->workspaceId ? Workspace::find($this->workspaceId) : null,
        ]);
    }
}
