<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\QrCode;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Share the member's public page: its link, WhatsApp and a printable QR code.
 */
class ShareSite extends Component
{
    use InteractsWithWorkspace;

    public bool $open = false;

    #[On('open-site-share')]
    public function openDialog(): void
    {
        $this->open = true;
    }

    public function closeDialog(): void
    {
        $this->open = false;
    }

    public function render(): View
    {
        if (! $this->open) {
            return view('livewire.share-site', ['url' => null]);
        }

        $membership = $this->membership();
        $url = $membership->publicUrl();

        return view('livewire.share-site', [
            'url' => $url,
            'host' => preg_replace('#^https?://#', '', $url),
            'whatsappUrl' => 'https://wa.me/?text='.rawurlencode('تصفّح عقارات '.$membership->workspace->name."\n".$url),
            'qr' => QrCode::svg($url.'?src=qr'),
        ]);
    }
}
