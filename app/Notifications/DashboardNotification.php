<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A notification shown in the dashboard panel. Subclasses describe the line:
 * its icon, colour tone, text and where tapping it leads.
 */
abstract class DashboardNotification extends Notification
{
    abstract protected function icon(): string;

    /**
     * One of: brand, neutral, warning, danger.
     */
    abstract protected function tone(): string;

    abstract protected function text(): string;

    abstract protected function url(): string;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{icon: string, tone: string, text: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'tone' => $this->tone(),
            'text' => $this->text(),
            'url' => $this->url(),
        ];
    }
}
