<?php

namespace App\Livewire\Admin;

use App\View\Composers\ShellComposer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The admin "More" tab on mobile: pages that do not fit in the tab bar.
 */
#[Title('المزيد')]
class More extends Component
{
    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function render(ShellComposer $shell): View
    {
        return view('livewire.admin.more', [
            'items' => array_values(array_filter($shell->adminMoreItems(), fn (array $item) => $item['url'] !== route('admin.invoices'))),
            'switch' => $shell->switchLink(auth()->user(), true, false),
        ]);
    }
}
