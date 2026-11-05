<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Support\Arabic;

class ReminderDue extends DashboardNotification
{
    public function __construct(public Reminder $reminder) {}

    protected function icon(): string
    {
        return 'ti-bell-ringing';
    }

    protected function tone(): string
    {
        return 'neutral';
    }

    protected function text(): string
    {
        return 'تذكير: '.$this->reminder->title.' مع '.$this->reminder->client->name.' الساعة '.Arabic::time($this->reminder->due_at);
    }

    protected function url(): string
    {
        return route('clients.show', $this->reminder->client_id);
    }
}
