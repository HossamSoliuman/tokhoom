<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Everyone with an account: office owners, their team and platform admins.
 */
#[Title('المستخدمون')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

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
        $this->reset('search', 'filter');
    }

    public function showMore(): void
    {
        $this->limit += 30;
    }

    /**
     * @return Builder<User>
     */
    private function searched(): Builder
    {
        $term = trim($this->search);
        $digits = preg_match('/^[\d٠-٩\s+()-]+$/u', $term) ? Phone::digits($term) : '';

        return User::query()->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('name', 'like', '%'.$term.'%')
            ->orWhere('email', 'like', '%'.$term.'%')
            ->when(strlen($digits) >= 4, fn (Builder $query) => $query->orWhere('mobile', 'like', '%'.Phone::normalize($digits).'%'))));
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'admins' => $query->where('is_platform_admin', true),
            'two-factor' => $query->whereNotNull('two_factor_confirmed_at'),
            'no-workspace' => $query->whereDoesntHave('memberships', fn (Builder $query) => $query->whereNull('deactivated_at')),
            default => null,
        };
    }

    public function render(): View
    {
        $filters = ['all' => 'الكل', 'admins' => 'مشرفو المنصة', 'two-factor' => 'التحقق بخطوتين', 'no-workspace' => 'بلا مساحة عمل'];

        $query = $this->searched()
            ->tap(fn (Builder $query) => $this->applyFilter($query, $this->filter))
            ->with(['memberships' => fn ($query) => $query->whereNull('deactivated_at')->with('workspace')])
            ->latest()
            ->latest('id');

        return view('livewire.admin.users.index', [
            'users' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'filters' => collect($filters)->map(fn (string $label, string $key) => [
                'label' => $label,
                'count' => $this->searched()->tap(fn (Builder $query) => $this->applyFilter($query, $key))->count(),
            ]),
        ]);
    }
}
