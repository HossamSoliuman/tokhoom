<?php

namespace App\Livewire\Deals;

use App\Actions\ChangeListingStatus;
use App\Enums\DealStage;
use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\LostReason;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Listing;
use App\Support\Arabic;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('الصفقات')]
class Board extends Component
{
    use HasDialogs, InteractsWithWorkspace, ShowsToasts;

    #[Locked]
    public ?int $dealId = null;

    public string $finalPrice = '';

    public string $commission = '';

    public bool $markListing = true;

    public string $lostReason = '';

    public ?int $clientId = null;

    public ?int $listingId = null;

    public string $value = '';

    private function deal(): Deal
    {
        return Deal::visibleTo($this->membership())->with(['client', 'listing'])->findOrFail($this->dealId);
    }

    public function moveTo(int $dealId, string $stage): void
    {
        $this->dealId = Deal::visibleTo($this->membership())->findOrFail($dealId)->id;
        $stage = DealStage::from($stage);

        if ($stage === DealStage::Won) {
            $deal = $this->deal();
            $this->finalPrice = number_format($deal->value);
            $this->commission = number_format($deal->suggestedCommission($deal->value));
            $this->markListing = $deal->listing !== null;
            $this->openDialog('win');

            return;
        }

        if ($stage === DealStage::Lost) {
            $this->lostReason = '';
            $this->openDialog('lose');

            return;
        }

        $deal = $this->deal();
        $deal->moveTo($stage);
        $deal->save();

        Activity::record($deal->client, $stage === DealStage::Viewing ? 'client.viewing' : 'client.deal_stage', [
            'stage' => $stage->label(),
            'listing' => $deal->listing?->title,
            'agent' => $deal->agent->name,
        ]);

        $this->toast('انتقلت الصفقة إلى «'.$stage->label().'»', 'info');
    }

    public function confirmWin(ChangeListingStatus $changeStatus): void
    {
        $price = (int) preg_replace('/\D/', '', $this->finalPrice);
        $commission = (int) preg_replace('/\D/', '', $this->commission);

        if ($price <= 0) {
            throw ValidationException::withMessages(['finalPrice' => 'أدخل السعر النهائي.']);
        }

        $deal = $this->deal();
        $deal->moveTo(DealStage::Won);
        $deal->fill(['final_price' => $price, 'commission' => $commission])->save();

        if ($this->markListing && $deal->listing) {
            $changeStatus->handle($deal->listing, $deal->listing->purpose === ListingPurpose::Rent ? ListingStatus::Rented : ListingStatus::Sold);
        }

        Activity::record($deal->client, 'client.deal_won', ['listing' => $deal->listing?->title]);

        $this->closeDialog();
        $this->toast('تم إغلاق الصفقة كناجحة');
    }

    public function confirmLoss(): void
    {
        $this->validate(['lostReason' => ['required', Rule::enum(LostReason::class)]], ['lostReason.required' => 'اختر سبب الإغلاق.']);

        $deal = $this->deal();
        $deal->moveTo(DealStage::Lost);
        $deal->lost_reason = LostReason::from($this->lostReason);
        $deal->save();

        Activity::record($deal->client, 'client.deal_lost', ['reason' => $deal->lost_reason->label()]);

        $this->closeDialog();
        $this->toast('تم إغلاق الصفقة كخاسرة', 'info');
    }

    public function openNewDeal(): void
    {
        $this->reset('clientId', 'listingId', 'value');
        $this->openDialog('new');
    }

    public function updatedListingId(): void
    {
        $listing = $this->listingId ? Listing::where('workspace_id', $this->workspace()->id)->find($this->listingId) : null;

        if ($listing?->price) {
            $this->value = number_format($listing->price);
        }
    }

    public function createDeal(): void
    {
        $this->value = (string) preg_replace('/\D/', '', $this->value);

        $this->validate([
            'clientId' => ['required', Rule::in(Client::visibleTo($this->membership())->pluck('id'))],
            'listingId' => ['nullable', Rule::in(Listing::where('workspace_id', $this->workspace()->id)->pluck('id'))],
            'value' => ['required', 'integer', 'min:1'],
        ]);

        $client = Client::findOrFail($this->clientId);

        Deal::create([
            'workspace_id' => $this->workspace()->id,
            'client_id' => $client->id,
            'listing_id' => $this->listingId,
            'agent_id' => $client->agent_id,
            'stage' => DealStage::New,
            'furthest_stage' => DealStage::New,
            'value' => (int) $this->value,
        ]);

        $this->closeDialog();
        $this->toast('تمت إضافة صفقة جديدة');
    }

    public function render(): View
    {
        $deals = Deal::visibleTo($this->membership())
            ->with(['client', 'listing', 'agent'])
            ->where(fn ($query) => $query->whereNull('closed_at')->orWhere('closed_at', '>=', now()->subDays(60)))
            ->latest('updated_at')
            ->get();

        $columns = collect(DealStage::cases())->map(fn (DealStage $stage) => [
            'stage' => $stage,
            'deals' => $deals->where('stage', $stage)->values(),
            'total' => $deals->where('stage', $stage)->sum('value'),
        ]);

        $deal = $this->dealId && in_array($this->dialog, ['win', 'lose'], true) ? $this->deal() : null;
        $price = (int) preg_replace('/\D/', '', $this->finalPrice);
        $commission = (int) preg_replace('/\D/', '', $this->commission);

        return view('livewire.deals.board', [
            'columns' => $columns,
            'hasAnyDeal' => $deals->isNotEmpty() || Deal::visibleTo($this->membership())->exists(),
            'deal' => $deal,
            'canConfirmWin' => $price > 0,
            'commissionRate' => $price > 0 ? Arabic::percent($commission / $price * 100, 1) : '٪',
            'clients' => $this->dialog === 'new' ? Client::visibleTo($this->membership())->orderBy('name')->get(['id', 'name']) : collect(),
            'listings' => $this->dialog === 'new'
                ? Listing::where('workspace_id', $this->workspace()->id)->whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])->latest()->get()
                : collect(),
        ]);
    }
}
