<?php

namespace App\Notifications;

use App\Models\Workspace;
use App\Support\Arabic;

/**
 * Sent to platform admins when a workspace's FAL license is about to run out.
 */
class WorkspaceLicenseExpiring extends DashboardNotification
{
    public function __construct(public Workspace $workspace) {}

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
        return 'ترخيص '.$this->workspace->name.' ينتهي في '.Arabic::date($this->workspace->license_expires_on);
    }

    protected function url(): string
    {
        return route('admin.workspaces.show', $this->workspace);
    }
}
