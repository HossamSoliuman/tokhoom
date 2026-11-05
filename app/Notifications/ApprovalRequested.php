<?php

namespace App\Notifications;

use App\Models\Workspace;

/**
 * Sent to platform admins when a new office or agent signs up.
 */
class ApprovalRequested extends DashboardNotification
{
    public function __construct(public Workspace $workspace) {}

    protected function icon(): string
    {
        return 'ti-certificate';
    }

    protected function tone(): string
    {
        return 'brand';
    }

    protected function text(): string
    {
        return 'طلب اعتماد جديد من '.$this->workspace->name;
    }

    protected function url(): string
    {
        return route('admin.approvals');
    }
}
