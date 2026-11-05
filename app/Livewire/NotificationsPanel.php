<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationsPanel extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->js('$store.shell.unread = false');
    }

    public function open(string $id): void
    {
        /** @var DatabaseNotification $notification */
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect($notification->data['url'] ?? route('today'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.notifications-panel', [
            'notifications' => auth()->user()->notifications()->latest()->limit(30)->get(),
        ]);
    }
}
