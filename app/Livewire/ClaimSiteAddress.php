<?php

namespace App\Livewire;

use App\Enums\WorkspaceKind;
use App\Support\SiteAddress;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The landing page's start form: the visitor picks their account type and site
 * address, sees whether it is free, and carries both into signup. The page can
 * hold several copies; each remembers the last pick so the next one starts with it.
 */
class ClaimSiteAddress extends Component
{
    private const SESSION_KEY = 'claim_site_address';

    public string $slug = '';

    public string $kind = 'office';

    public bool $typedArabic = false;

    #[Locked]
    public bool $withLabel = true;

    public function mount(bool $withLabel = true): void
    {
        $this->withLabel = $withLabel;
        $this->slug = SiteAddress::clean((string) session(self::SESSION_KEY.'.slug', ''));
        $this->kind = WorkspaceKind::tryFrom((string) session(self::SESSION_KEY.'.kind'))?->value ?? 'office';
    }

    public function updatedSlug(string $value): void
    {
        $this->typedArabic = SiteAddress::hasArabic($value);
        $this->slug = SiteAddress::clean($value);
        $this->resetErrorBag('slug');
        $this->remember();
    }

    public function updatedKind(string $value): void
    {
        $this->kind = WorkspaceKind::tryFrom($value)?->value ?? 'office';
        $this->remember();
    }

    public function useSuggestion(string $slug): void
    {
        $this->typedArabic = false;
        $this->slug = SiteAddress::clean($slug);
        $this->resetErrorBag('slug');
        $this->remember();
    }

    public function start(): void
    {
        $this->slug = SiteAddress::clean($this->slug);
        $this->remember();

        if ($this->slug === '' && ! $this->typedArabic) {
            $this->redirectRoute('signup', ['kind' => $this->kind]);

            return;
        }

        if ($this->slugState !== 'available') {
            $this->addError('slug', match ($this->slugState) {
                'arabic' => 'اكتب العنوان بأحرف إنجليزية، مثل alrashid',
                'taken' => 'هذا العنوان مستخدم. اختر عنواناً آخر.',
                'reserved' => 'هذا العنوان غير متاح.',
                default => 'أحرف إنجليزية صغيرة وأرقام وشرطة، 3 أحرف على الأقل.',
            });

            return;
        }

        $this->redirectRoute('signup', ['slug' => $this->slug, 'kind' => $this->kind]);
    }

    /**
     * The picker state, plus "arabic" when the visitor typed Arabic letters.
     */
    #[Computed]
    public function slugState(): string
    {
        if ($this->typedArabic && ! SiteAddress::isValid($this->slug)) {
            return 'arabic';
        }

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

    /**
     * Keep the pick for the next form and tell the page, which shows it in the preview and links.
     */
    private function remember(): void
    {
        unset($this->slugState);

        session([self::SESSION_KEY => ['slug' => $this->slug, 'kind' => $this->kind]]);

        $this->dispatch('site-address', slug: $this->slug, kind: $this->kind, state: $this->slugState);
    }

    public function render(): View
    {
        return view('livewire.claim-site-address');
    }
}
