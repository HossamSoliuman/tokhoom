<?php

namespace App\View\Composers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;

/**
 * Data for the dashboard shell: navigation, the current workspace and user, and
 * the workspaces the user can switch between.
 */
class ShellComposer
{
    public function compose(View $view): void
    {
        /** @var User|null $user */
        $user = Request::user();

        if (! $user) {
            $view->with('shell', null);

            return;
        }

        $membership = $user->currentMembership();
        $admin = Request::routeIs('admin.*');

        $view->with('shell', [
            'admin' => $admin,
            'user' => $user,
            'membership' => $membership,
            'workspace' => $membership?->workspace,
            'roleLabel' => $admin ? ($user->gender === 'female' ? 'مشرفة المنصة' : 'مشرف المنصة') : $membership?->roleLabel(),
            'nav' => $admin ? $this->adminNav() : $this->workspaceNav(),
            'tabs' => $admin ? $this->adminTabs() : $this->workspaceTabs(),
            'workspaces' => $admin ? collect() : $user->workspaces()->orderBy('memberships.id')->get(),
            'unread' => $user->unreadNotifications()->count(),
            'fab' => ! $admin && Request::routeIs('today', 'listings.index'),
        ]);
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool, badge?: int}>
     */
    private function workspaceNav(): array
    {
        $items = [
            $this->item('اليوم', 'ti-sun', 'today', 'today'),
            $this->item('العقارات', 'ti-home', 'listings.index', 'listings.*'),
            $this->item('العملاء', 'ti-users', 'clients.index', 'clients.*'),
            $this->item('الصفقات', 'ti-layout-kanban', 'deals.index', 'deals.*'),
        ];

        return array_merge($items, $this->settingsItems());
    }

    /**
     * Team, reports, site and billing, filtered by what the member may manage.
     *
     * @return list<array{label: string, icon: string, url: string, active: bool}>
     */
    public function settingsItems(): array
    {
        return array_values(array_filter([
            Gate::allows('manage-team') ? $this->item('الفريق', 'ti-user-shield', 'team', 'team') : null,
            Gate::allows('view-reports') ? $this->item('التقارير', 'ti-chart-bar', 'reports', 'reports') : null,
            Gate::allows('manage-site') ? $this->item('إعدادات الموقع', 'ti-world', 'site', 'site') : null,
            Gate::allows('manage-billing') ? $this->item('الاشتراك والنطاق', 'ti-credit-card', 'billing', 'billing') : null,
        ]));
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool}>
     */
    private function workspaceTabs(): array
    {
        $more = $this->item('المزيد', 'ti-dots', 'more', 'more');
        $more['active'] = Request::routeIs('more', 'account', 'team', 'reports', 'site', 'billing');

        return [
            $this->item('اليوم', 'ti-sun', 'today', 'today'),
            $this->item('العقارات', 'ti-home', 'listings.index', 'listings.*'),
            $this->item('العملاء', 'ti-users', 'clients.index', 'clients.*'),
            $this->item('الصفقات', 'ti-layout-kanban', 'deals.index', 'deals.*'),
            $more,
        ];
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool, badge?: int}>
     */
    private function adminNav(): array
    {
        $approvals = $this->item('طلبات الاعتماد', 'ti-certificate', 'admin.approvals', 'admin.approvals');
        $approvals['badge'] = Workspace::pendingApproval()->count();

        return [
            $approvals,
            $this->item('مساحات العمل', 'ti-building-community', 'admin.workspaces', 'admin.workspaces'),
        ];
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool}>
     */
    private function adminTabs(): array
    {
        return [
            $this->item('الاعتماد', 'ti-certificate', 'admin.approvals', 'admin.approvals'),
            $this->item('مساحات العمل', 'ti-building-community', 'admin.workspaces', 'admin.workspaces'),
        ];
    }

    /**
     * @return array{label: string, icon: string, url: string, active: bool}
     */
    private function item(string $label, string $icon, string $route, string $pattern): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'url' => route($route),
            'active' => Request::routeIs($pattern),
        ];
    }
}
