<?php

namespace App\Livewire\Admin;

use App\Actions\RemoveListing;
use App\Enums\ListingStatus;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Models\Listing;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Find any listing on the platform by its ad license number, code or link,
 * to answer a complaint or a regulator's question, and take it down if needed.
 */
#[Title('العقارات')]
class Listings extends Component
{
    use HasDialogs, ShowsToasts;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    public int $limit = 30;

    #[Locked]
    public ?int $listingId = null;

    public string $reason = '';

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

    public function openRemove(int $id): void
    {
        $this->listingId = Listing::whereNull('removed_at')->findOrFail($id)->id;
        $this->reset('reason');
        $this->openDialog('remove');
    }

    public function remove(RemoveListing $removeListing): void
    {
        $this->validate(['reason' => ['required', 'string', 'max:255']], [
            'reason.required' => 'اكتب سبب الإيقاف. يظهر للمكتب في الإشعار.',
        ]);

        $listing = Listing::whereNull('removed_at')->with(['workspace', 'agent'])->findOrFail($this->listingId);
        $removeListing->handle($listing, trim($this->reason));

        $this->closeDialog();
        $this->toast('أُوقف عرض العقار '.$listing->short_code);
    }

    public function restore(int $id): void
    {
        $listing = Listing::whereNotNull('removed_at')->with('workspace')->findOrFail($id);
        $listing->forceFill(['removed_at' => null, 'removal_reason' => null])->save();

        AdminAction::record('listing.restored', $listing->workspace, properties: ['code' => $listing->short_code]);

        $this->toast('أُعيد عرض العقار '.$listing->short_code);
    }

    /**
     * A pasted listing link searches by the code at its end.
     */
    private function searchTerm(): string
    {
        $term = trim($this->search);

        if (str_contains($term, '/')) {
            $term = (string) str($term)->before('?')->rtrim('/')->afterLast('/');
        }

        return $term;
    }

    /**
     * @return Builder<Listing>
     */
    private function searched(): Builder
    {
        $term = $this->searchTerm();
        $digits = preg_match('/^[\d٠-٩\s-]+$/u', $term) ? Phone::digits($term) : '';

        return Listing::query()
            ->whereNotNull('published_at')
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('short_code', $term)
                ->when($digits !== '', fn (Builder $query) => $query->orWhere('ad_license_number', 'like', '%'.$digits.'%'))
                ->orWhere('district', 'like', '%'.$term.'%')
                ->orWhereHas('workspace', fn (Builder $query) => $query->where('name', 'like', '%'.$term.'%'))));
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function applyFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'live' => $query->live(),
            'removed' => $query->whereNotNull('removed_at'),
            'expired' => $query->whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])->whereDate('ad_license_expires_on', '<', today()),
            default => null,
        };
    }

    public function render(): View
    {
        $filters = ['all' => 'الكل', 'live' => 'معروضة', 'expired' => 'ترخيص منتهٍ', 'removed' => 'موقوفة من الإدارة'];

        $query = $this->searched()
            ->tap(fn (Builder $query) => $this->applyFilter($query, $this->filter))
            ->with(['workspace', 'agent', 'coverPhoto'])
            ->latest('published_at')
            ->latest('id');

        return view('livewire.admin.listings', [
            'listings' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'filters' => collect($filters)->map(fn (string $label, string $key) => [
                'label' => $label,
                'count' => $this->searched()->tap(fn (Builder $query) => $this->applyFilter($query, $key))->count(),
            ]),
            'removing' => $this->dialog === 'remove' && $this->listingId ? Listing::with('workspace')->find($this->listingId) : null,
        ]);
    }
}
