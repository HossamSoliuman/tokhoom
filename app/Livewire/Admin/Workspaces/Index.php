<?php

namespace App\Livewire\Admin\Workspaces;

use App\Enums\MemberRole;
use App\Enums\WorkspaceKind;
use App\Enums\WorkspaceStatus;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Every office and independent agent on the platform, searchable and
 * filtered by status and kind.
 */
#[Title('مساحات العمل')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $kind = 'all';

    public int $limit = 30;

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function updated(): void
    {
        $this->limit = 30;
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'kind');
    }

    public function showMore(): void
    {
        $this->limit += 30;
    }

    /**
     * Workspaces matching the search and kind, before the status filter.
     *
     * @return Builder<Workspace>
     */
    public static function filtered(string $search, ?WorkspaceKind $kind): Builder
    {
        return Workspace::query()
            ->matching($search)
            ->when($kind, fn (Builder $query) => $query->where('kind', $kind));
    }

    public function render(): View
    {
        $status = WorkspaceStatus::tryFrom($this->status);
        $base = self::filtered($this->search, WorkspaceKind::tryFrom($this->kind));

        $query = (clone $base)
            ->when($status, fn (Builder $query) => $query->inStatus($status))
            ->with(['memberships' => fn ($query) => $query->whereNull('deactivated_at')->with('user')])
            ->withCount(['listings', 'listings as live_listings_count' => fn ($query) => $query->live()])
            ->withExists(['invoices as has_open_invoice' => fn ($query) => $query->whereNull('paid_at')])
            ->orderBy('name');

        return view('livewire.admin.workspaces.index', [
            'workspaces' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'counts' => collect(WorkspaceStatus::cases())
                ->mapWithKeys(fn (WorkspaceStatus $case) => [$case->value => (clone $base)->inStatus($case)->count()]),
            'owner' => MemberRole::Owner,
        ]);
    }
}
