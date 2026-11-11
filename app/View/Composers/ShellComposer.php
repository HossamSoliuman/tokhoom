<?php

namespace App\View\Composers;

use App\Models\Inquiry;
use App\Models\Membership;
use App\Models\Reminder;
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
            'nav' => $admin ? $this->adminNav() : $this->workspaceNav($membership),
            'tabs' => $admin ? $this->adminTabs() : $this->workspaceTabs(),
            'workspaces' => $admin ? collect() : $user->workspaces()->orderBy('memberships.id')->get(),
            'unread' => $user->unreadNotifications()->count(),
            'fab' => ! $admin && Request::routeIs('today', 'listings.index'),
            'accountUrl' => route($admin ? 'admin.account' : 'account'),
            'switch' => $this->switchLink($user, $admin, $membership !== null),
        ]);
    }

    /**
     * For platform admins who also belong to a workspace: the way between the
     * admin panel and their workspace.
     *
     * @return array{label: string, icon: string, url: string}|null
     */
    public function switchLink(User $user, bool $admin, bool $hasWorkspace): ?array
    {
        if (! $user->is_platform_admin) {
            return null;
        }

        if (! $admin) {
            return ['label' => 'إدارة المنصة', 'icon' => 'ti-shield', 'url' => route('admin.overview')];
        }

        $hasWorkspace = $hasWorkspace || $user->memberships()->whereNull('deactivated_at')->exists();

        return $hasWorkspace ? ['label' => 'العودة إلى مساحة العمل', 'icon' => 'ti-arrow-back-up', 'url' => route('today')] : null;
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool, badge?: int}>
     */
    private function workspaceNav(?Membership $membership): array
    {
        $today = $this->item('اليوم', 'ti-sun', 'today', 'today');

        if ($membership) {
            $today['badge'] = $this->openTaskCount($membership);
        }

        $items = [
            $today,
            $this->item('العقارات', 'ti-home', 'listings.index', 'listings.*'),
            $this->item('العملاء', 'ti-users', 'clients.index', 'clients.*'),
            $this->item('الصفقات', 'ti-layout-kanban', 'deals.index', 'deals.*'),
        ];

        return array_merge($items, $this->settingsItems());
    }

    /**
     * Follow-ups due by the end of today plus inquiries still waiting for a reply.
     */
    public function openTaskCount(Membership $membership): int
    {
        $reminders = Reminder::visibleTo($membership)
            ->whereNull('completed_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->count();

        $inquiries = Inquiry::visibleTo($membership)
            ->awaitingReply()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $reminders + $inquiries;
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
            $this->item('نظرة عامة', 'ti-layout-dashboard', 'admin.overview', 'admin.overview'),
            $approvals,
            $this->item('مساحات العمل', 'ti-building-community', 'admin.workspaces', 'admin.workspaces*'),
            ...$this->adminMoreItems(),
        ];
    }

    /**
     * Admin pages that live under "More" on mobile.
     *
     * @return list<array{label: string, icon: string, url: string, active: bool}>
     */
    public function adminMoreItems(): array
    {
        return [
            $this->item('الفواتير', 'ti-file-invoice', 'admin.invoices', 'admin.invoices*'),
            $this->item('المستخدمون', 'ti-users', 'admin.users', 'admin.users*'),
            $this->item('العقارات', 'ti-home-search', 'admin.listings', 'admin.listings'),
            $this->item('التراخيص', 'ti-id-badge-2', 'admin.licenses', 'admin.licenses'),
            $this->item('سجل الإدارة', 'ti-history', 'admin.log', 'admin.log'),
            $this->item('الإعدادات', 'ti-settings', 'admin.settings', 'admin.settings'),
        ];
    }

    /**
     * @return list<array{label: string, icon: string, url: string, active: bool}>
     */
    private function adminTabs(): array
    {
        $more = $this->item('المزيد', 'ti-dots', 'admin.more', 'admin.more');
        $more['active'] = Request::routeIs('admin.more', 'admin.account', 'admin.users*', 'admin.listings', 'admin.licenses', 'admin.log', 'admin.settings');

        return [
            $this->item('نظرة عامة', 'ti-layout-dashboard', 'admin.overview', 'admin.overview'),
            $this->item('الاعتماد', 'ti-certificate', 'admin.approvals', 'admin.approvals'),
            $this->item('مساحات العمل', 'ti-building-community', 'admin.workspaces', 'admin.workspaces*'),
            $this->item('الفواتير', 'ti-file-invoice', 'admin.invoices', 'admin.invoices*'),
            $more,
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
