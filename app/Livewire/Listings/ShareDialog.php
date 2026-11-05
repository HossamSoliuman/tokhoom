<?php

namespace App\Livewire\Listings;

use App\Enums\LeadSource;
use App\Enums\SiteEventType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Listing;
use App\Models\SiteEvent;
use App\Support\QrCode;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Share a listing: short link, social apps, QR code and a WhatsApp preview.
 */
class ShareDialog extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
    public ?int $listingId = null;

    #[On('open-share')]
    public function open(int $listingId): void
    {
        $this->listingId = Listing::visibleTo($this->membership())->findOrFail($listingId)->id;
        unset($this->listing);
    }

    public function closeDialog(): void
    {
        $this->listingId = null;
    }

    /**
     * Count a share when the agent sends the link out through an app.
     */
    public function recordShare(): void
    {
        if ($this->listing) {
            SiteEvent::create([
                'workspace_id' => $this->listing->workspace_id,
                'listing_id' => $this->listing->id,
                'type' => SiteEventType::Share,
                'source' => LeadSource::SharedLink,
                'occurred_at' => now(),
            ]);
        }
    }

    #[Computed]
    public function listing(): ?Listing
    {
        return $this->listingId
            ? Listing::visibleTo($this->membership())->with(['coverPhoto', 'workspace'])->find($this->listingId)
            : null;
    }

    /**
     * @return list<array{label: string, icon: string, classes: string, url: ?string}>
     */
    private function socials(Listing $listing): array
    {
        $text = $listing->title.' — '.$listing->priceLabel();
        $url = $listing->shortUrl();

        return [
            ['label' => 'واتساب', 'icon' => 'ti-brand-whatsapp', 'classes' => 'bg-whatsapp text-white', 'url' => 'https://wa.me/?text='.rawurlencode($text."\n".$url)],
            ['label' => 'X', 'icon' => 'ti-brand-x', 'classes' => 'bg-ink text-white', 'url' => 'https://twitter.com/intent/tweet?text='.rawurlencode($text).'&url='.rawurlencode($url)],
            ['label' => 'سناب شات', 'icon' => 'ti-brand-snapchat', 'classes' => 'bg-[#FFF35C] text-ink', 'url' => 'https://www.snapchat.com/scan?attachmentUrl='.rawurlencode($url)],
            ['label' => 'إنستغرام', 'icon' => 'ti-brand-instagram', 'classes' => 'bg-divider text-ink', 'url' => null],
        ];
    }

    public function render(): View
    {
        $listing = $this->listing;

        return view('livewire.listings.share-dialog', [
            'listing' => $listing,
            'socials' => $listing ? $this->socials($listing) : [],
            'qr' => $listing ? QrCode::svg($listing->shortUrl().'?src=qr') : null,
        ]);
    }
}
