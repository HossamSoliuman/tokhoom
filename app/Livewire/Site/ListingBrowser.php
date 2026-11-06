<?php

namespace App\Livewire\Site;

use App\Enums\ListingPurpose;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Arabic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The listings on a public site, or on one agent's page, with filters and
 * sorting kept in the address so a filtered view can be shared.
 */
class ListingBrowser extends Component
{
    public const PER_PAGE = 12;

    private const FILTERS = ['purpose', 'type', 'district', 'min', 'max', 'beds'];

    #[Locked]
    public Workspace $workspace;

    #[Locked]
    public ?int $agentId = null;

    #[Url(except: '')]
    public string $purpose = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $district = '';

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    #[Url(except: '')]
    public string $beds = '';

    #[Url(except: 'new')]
    public string $sort = 'new';

    #[Locked]
    public int $limit = self::PER_PAGE;

    public function updated(string $property): void
    {
        if (in_array($property, ['min', 'max'], true)) {
            $this->{$property} = (string) preg_replace('/\D/', '', $this->{$property});
        }

        if (in_array($property, self::FILTERS, true)) {
            $this->limit = self::PER_PAGE;
        }
    }

    public function clearFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->limit = self::PER_PAGE;
    }

    public function showMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    /**
     * @return Builder<Listing>
     */
    private function pool(): Builder
    {
        return Listing::query()
            ->where('workspace_id', $this->workspace->id)
            ->when($this->agentId, fn (Builder $query) => $query->where('agent_id', $this->agentId))
            ->live();
    }

    /**
     * @param  Builder<Listing>  $query
     * @return Builder<Listing>
     */
    private function filter(Builder $query): Builder
    {
        $purpose = ListingPurpose::tryFrom($this->purpose);
        $type = ListingType::tryFrom($this->type);

        return $query
            ->when($purpose, fn (Builder $query) => $query->where('purpose', $purpose))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($this->district !== '', fn (Builder $query) => $query->where('district', $this->district))
            ->when((int) $this->beds > 0, fn (Builder $query) => $query->where('bedrooms', '>=', (int) $this->beds))
            ->when($this->min !== '', fn (Builder $query) => $query->where('price', '>=', (int) $this->min))
            ->when($this->max !== '', fn (Builder $query) => $query->where('price', '<=', (int) $this->max));
    }

    private function activeFilters(): int
    {
        return collect([
            ListingPurpose::tryFrom($this->purpose) !== null,
            ListingType::tryFrom($this->type) !== null,
            $this->district !== '',
            (int) $this->beds > 0,
            $this->min !== '' || $this->max !== '',
        ])->filter()->count();
    }

    /**
     * Contact numbers for the empty state: the agent's own on an agent page.
     *
     * @return array{whatsapp: ?string, phone: ?string}
     */
    private function contact(): array
    {
        $mobile = $this->agentId ? User::find($this->agentId)?->mobile : null;

        return [
            'whatsapp' => $mobile ?: $this->workspace->contactWhatsapp(),
            'phone' => $mobile ?: $this->workspace->contactPhone(),
        ];
    }

    public function render(): View
    {
        $pool = $this->pool();
        $results = $this->filter(clone $pool);
        $total = (clone $results)->count();

        $listings = $results
            ->with(['coverPhoto', 'agent'])
            ->withCount('photos')
            ->when($this->sort === 'asc', fn (Builder $query) => $query->orderBy('price'))
            ->when($this->sort === 'desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->latest('published_at')
            ->latest('id')
            ->limit($this->limit)
            ->get();

        $presentTypes = (clone $pool)->distinct()->pluck('type');

        return view('livewire.site.listing-browser', [
            'hasAny' => $pool->exists(),
            'listings' => $listings,
            'total' => $total,
            'countText' => $total === 0 ? 'لا توجد عقارات' : Arabic::count($total, 'عقار واحد', 'عقاران', 'عقارات', 'عقاراً'),
            'activeFilters' => $this->activeFilters(),
            'types' => array_values(array_filter(ListingType::cases(), fn (ListingType $type) => $presentTypes->contains($type))),
            'districts' => (clone $pool)->whereNotNull('district')->distinct()->orderBy('district')->pluck('district'),
            'showAgent' => $this->workspace->isOffice() && ! $this->agentId,
            'contact' => $this->contact(),
        ]);
    }
}
