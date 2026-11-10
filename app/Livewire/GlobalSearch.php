<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Client;
use App\Models\Listing;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * The top bar search: clients by name or mobile and listings by district,
 * code or ad license, limited to what the member can see.
 */
class GlobalSearch extends Component
{
    use InteractsWithWorkspace;

    public const LIMIT = 6;

    public string $query = '';

    /**
     * @return list<array{kind: string, icon: string, label: string, meta: string, url: string}>
     */
    private function results(): array
    {
        $search = trim($this->query);

        if (mb_strlen($search) < 2) {
            return [];
        }

        $membership = $this->membership();
        $digits = preg_replace('/\D/', '', Phone::digits($search));

        $clients = Client::visibleTo($membership)
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->when(strlen($digits) >= 3, fn (Builder $query) => $query->orWhere('mobile', 'like', '%'.Phone::normalize($digits).'%')))
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Client $client) => [
                'kind' => 'عميل',
                'icon' => 'ti-user',
                'label' => $client->name,
                'meta' => Phone::display($client->mobile),
                'url' => route('clients.show', $client),
            ]);

        $listings = Listing::visibleTo($membership)
            ->where(fn (Builder $query) => $query
                ->where('district', 'like', '%'.$search.'%')
                ->orWhere('short_code', $search)
                ->when($digits !== '', fn (Builder $query) => $query->orWhere('ad_license_number', 'like', $digits.'%')))
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Listing $listing) => [
                'kind' => 'عقار',
                'icon' => 'ti-home',
                'label' => $listing->title,
                'meta' => $listing->priceLabel(),
                'url' => route('listings.show', $listing),
            ]);

        return $clients->concat($listings)->values()->all();
    }

    public function render(): View
    {
        return view('livewire.global-search', [
            'results' => $this->results(),
        ]);
    }
}
