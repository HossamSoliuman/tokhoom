<?php

namespace App\Notifications;

use App\Models\Workspace;

/**
 * Sent to platform admins when the last automatic try on a workspace's saved
 * card fails, so someone follows up with the owner.
 */
class CollectionFailed extends DashboardNotification
{
    public function __construct(public Workspace $workspace, public int $attempts) {}

    protected function icon(): string
    {
        return 'ti-credit-card-off';
    }

    protected function tone(): string
    {
        return 'danger';
    }

    protected function text(): string
    {
        return 'تعذّر تحصيل اشتراك '.$this->workspace->name.' بعد '.$this->attempts.' محاولات';
    }

    protected function url(): string
    {
        return route('admin.workspaces.show', $this->workspace);
    }
}
