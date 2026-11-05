<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Support\Arabic;

class FalLicenseExpiring extends DashboardNotification
{
    public function __construct(public Membership $membership) {}

    protected function icon(): string
    {
        return 'ti-alert-triangle';
    }

    protected function tone(): string
    {
        return 'warning';
    }

    protected function text(): string
    {
        return 'رخصة فال ل'.$this->membership->user->name.' تنتهي في '.Arabic::date($this->membership->fal_expires_on);
    }

    protected function url(): string
    {
        return route('team');
    }
}
