<?php

namespace App\Livewire;

use App\Support\SiteAddress;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The landing page's start form: the visitor picks their site address, sees
 * whether it is free, and carries it into signup.
 */
class ClaimSiteAddress extends Component
{
    public string $slug = '';

    public function updatedSlug(string $value): void
    {
        $this->slug = SiteAddress::clean($value);
        $this->resetErrorBag('slug');
    }

    public function useSuggestion(string $slug): void
    {
        $this->slug = SiteAddress::clean($slug);
        $this->resetErrorBag('slug');
    }

    public function start(): void
    {
        $this->slug = SiteAddress::clean($this->slug);

        if ($this->slug === '') {
            $this->redirectRoute('signup');

            return;
        }

        if ($this->slugState !== 'available') {
            $this->addError('slug', $this->slugState === 'taken' ? 'هذا العنوان مستخدم. اختر عنواناً آخر.' : 'اختر عنواناً صالحاً.');

            return;
        }

        $this->redirectRoute('signup', ['slug' => $this->slug]);
    }

    #[Computed]
    public function slugState(): string
    {
        return SiteAddress::state($this->slug);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function suggestions(): array
    {
        return $this->slugState === 'taken' ? SiteAddress::suggestions($this->slug) : [];
    }

    public function render(): View
    {
        return view('livewire.claim-site-address');
    }
}
