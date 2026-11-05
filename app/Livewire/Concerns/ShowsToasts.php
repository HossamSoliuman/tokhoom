<?php

namespace App\Livewire\Concerns;

trait ShowsToasts
{
    /**
     * Show a toast now. Kind is "ok", "info" or "err".
     */
    protected function toast(string $message, string $kind = 'ok'): void
    {
        $this->dispatch('toast', message: $message, kind: $kind);
    }

    /**
     * Show a toast on the next page, for actions that redirect.
     */
    protected function flashToast(string $message, string $kind = 'ok'): void
    {
        session()->flash('toast', ['message' => $message, 'kind' => $kind]);
    }
}
