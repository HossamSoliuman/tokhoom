<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('مساحات العمل')]
class Workspaces extends Component
{
    use HasDialogs, ShowsToasts;

    #[Locked]
    public ?int $workspaceId = null;

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function confirm(int $id): void
    {
        $workspace = Workspace::findOrFail($id);

        $this->workspaceId = $workspace->id;
        $this->openDialog($workspace->isSuspended() ? 'reactivate' : 'suspend');
    }

    public function suspend(): void
    {
        $workspace = Workspace::findOrFail($this->workspaceId);
        $workspace->forceFill(['suspended_at' => now()])->save();

        $this->closeDialog();
        $this->toast('تم إيقاف '.$workspace->name);
    }

    public function reactivate(): void
    {
        $workspace = Workspace::findOrFail($this->workspaceId);
        $workspace->forceFill(['suspended_at' => null])->save();

        $this->closeDialog();
        $this->toast('تمت إعادة تفعيل '.$workspace->name);
    }

    public function render(): View
    {
        return view('livewire.admin.workspaces', [
            'workspaces' => Workspace::where('approval_status', ApprovalStatus::Approved)
                ->with(['memberships' => fn ($query) => $query->whereNull('deactivated_at')->with('user')])
                ->withCount('listings')
                ->orderBy('name')
                ->get(),
            'target' => $this->workspaceId && $this->dialog ? Workspace::find($this->workspaceId) : null,
        ]);
    }
}
