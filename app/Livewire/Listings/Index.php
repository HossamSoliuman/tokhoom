<?php

namespace App\Livewire\Listings;

use App\Actions\ChangeListingStatus;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\SiteEventType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('العقارات')]
class Index extends Component
{
    use InteractsWithWorkspace, ShowsToasts;

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $agent = 'all';

    #[Url]
    public string $type = 'all';

    /**
     * Narrowing from the listing health card on the today screen.
     */
    #[Url]
    public string $issue = '';

    public function resetFilters(): void
    {
        $this->reset('status', 'agent', 'type', 'issue');
    }

    public function setStatus(int $id, string $status, ChangeListingStatus $changeStatus): void
    {
        $listing = Listing::visibleTo($this->membership())->findOrFail($id);
        $status = ListingStatus::from($status);

        try {
            $changeStatus->handle($listing, $status);
        } catch (ValidationException $exception) {
            $this->toast($exception->validator->errors()->first(), 'err');

            return;
        }

        $this->toast('تم تغيير الحالة إلى «'.$status->label().'»');
    }

    /**
     * Listings matching every filter except status, so status chips can show counts.
     *
     * @return Collection<int, Listing>
     */
    private function filteredListings(): Collection
    {
        $query = Listing::visibleTo($this->membership())
            ->with(['coverPhoto', 'agent', 'workspace'])
            ->withCount([
                'photos',
                'inquiries',
                'siteEvents as views_count' => fn (Builder $query) => $query->where('type', SiteEventType::ListingView),
                'siteEvents as whatsapp_count' => fn (Builder $query) => $query->where('type', SiteEventType::WhatsappClick),
            ])
            ->when($this->agent !== 'all' && $this->seesWholeWorkspace(), fn (Builder $query) => $query->where('agent_id', (int) $this->agent))
            ->when(ListingType::tryFrom($this->type), fn (Builder $query, ListingType $type) => $query->where('type', $type))
            ->latest('updated_at')
            ->latest('id');

        $listings = $query->get();

        return match ($this->issue) {
            'expired' => $listings->filter(fn (Listing $listing) => $listing->status->isPublic() && $listing->isAdLicenseExpired()),
            'photos' => $listings->filter(fn (Listing $listing) => $listing->isPublished() && $listing->photos_count < Listing::MINIMUM_PHOTOS),
            'stale' => $listings->filter(fn (Listing $listing) => $listing->status->isPublic() && ($listing->daysSincePriceChange() ?? 0) >= Listing::STALE_PRICE_DAYS),
            'drafts' => $listings->where('status', ListingStatus::Draft),
            default => $listings,
        };
    }

    private function issueLabel(): ?string
    {
        return match ($this->issue) {
            'expired' => 'ترخيص إعلان منتهٍ',
            'photos' => 'عقارات بأقل من '.Listing::MINIMUM_PHOTOS.' صور',
            'stale' => 'لم يُحدَّث السعر منذ '.Listing::STALE_PRICE_DAYS.' يوماً',
            'drafts' => 'مسودات غير منشورة',
            default => null,
        };
    }

    public function render(): View
    {
        $all = $this->filteredListings();
        $status = ListingStatus::tryFrom($this->status);
        $listings = $status ? $all->where('status', $status) : $all;

        $chips = collect([['all', 'الكل', $all->count()]])->concat(
            collect(ListingStatus::cases())
                ->reject(fn (ListingStatus $case) => $case === ListingStatus::Draft)
                ->push(ListingStatus::Draft)
                ->map(fn (ListingStatus $case) => [$case->value, $case->label(), $all->where('status', $case)->count()])
        );

        return view('livewire.listings.index', [
            'listings' => $listings->values(),
            'chips' => $chips,
            'hasAnyListing' => Listing::visibleTo($this->membership())->exists(),
            'agents' => $this->seesWholeWorkspace()
                ? $this->workspace()->members()->orderBy('memberships.id')->get()
                : collect(),
            'issueLabel' => $this->issueLabel(),
        ]);
    }
}
