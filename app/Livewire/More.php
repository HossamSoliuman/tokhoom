<?php

namespace App\Livewire;

use App\View\Composers\ShellComposer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The mobile "More" tab: settings pages that do not fit in the tab bar.
 */
#[Title('المزيد')]
class More extends Component
{
    public function render(ShellComposer $shell): View
    {
        return view('livewire.more', ['items' => $shell->settingsItems()]);
    }
}
