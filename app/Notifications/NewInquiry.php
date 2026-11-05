<?php

namespace App\Notifications;

use App\Models\Inquiry;

class NewInquiry extends DashboardNotification
{
    public function __construct(public Inquiry $inquiry) {}

    protected function icon(): string
    {
        return 'ti-message-circle';
    }

    protected function tone(): string
    {
        return 'brand';
    }

    protected function text(): string
    {
        $listing = $this->inquiry->listing;

        return 'استفسار جديد من '.$this->inquiry->name.($listing ? ' عن «'.$listing->title.'»' : '');
    }

    protected function url(): string
    {
        return $this->inquiry->client_id
            ? route('clients.show', $this->inquiry->client_id)
            : route('today');
    }
}
