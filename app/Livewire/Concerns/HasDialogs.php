<?php

namespace App\Livewire\Concerns;

/**
 * One open dialog at a time, named by $dialog. Views render it with x-ui.dialog.
 */
trait HasDialogs
{
    public ?string $dialog = null;

    public function closeDialog(): void
    {
        $this->dialog = null;
        $this->resetValidation();
    }

    protected function openDialog(string $name): void
    {
        $this->resetValidation();
        $this->dialog = $name;
    }
}
