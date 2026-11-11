<?php

namespace App\Livewire\Admin;

use App\Models\AdminAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Everything platform admins did, newest first: approvals, suspensions,
 * payments, listing takedowns, account changes and settings.
 */
#[Title('سجل الإدارة')]
class Log extends Component
{
    /**
     * Filter keys and the action type prefix each one matches.
     */
    private const AREAS = [
        'all' => ['label' => 'الكل', 'prefix' => null],
        'workspaces' => ['label' => 'مساحات العمل', 'prefix' => 'workspace.'],
        'invoices' => ['label' => 'الفواتير', 'prefix' => 'invoice.'],
        'listings' => ['label' => 'العقارات', 'prefix' => 'listing.'],
        'users' => ['label' => 'المستخدمون', 'prefix' => 'user.'],
        'settings' => ['label' => 'الإعدادات', 'prefix' => 'settings.'],
    ];

    #[Url]
    public string $area = 'all';

    public int $limit = 50;

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function updated(): void
    {
        $this->limit = 50;
    }

    public function showMore(): void
    {
        $this->limit += 50;
    }

    /**
     * @return Builder<AdminAction>
     */
    private function inArea(string $area): Builder
    {
        $prefix = self::AREAS[$area]['prefix'] ?? null;

        return AdminAction::query()->when($prefix, fn (Builder $query) => $query->where('type', 'like', $prefix.'%'));
    }

    public function render(): View
    {
        $query = $this->inArea($this->area)->with(['admin', 'workspace', 'user'])->latest('created_at')->latest('id');

        return view('livewire.admin.log', [
            'entries' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'areas' => collect(self::AREAS)->map(fn (array $area, string $key) => [
                'label' => $area['label'],
                'count' => $this->inArea($key)->count(),
            ]),
        ]);
    }
}
