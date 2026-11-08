<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\DomainStatus;
use App\Enums\MemberRole;
use App\Models\AdminAction;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Support\Arabic;
use App\Support\PlatformStats;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The platform at a glance: subscribers, revenue, growth and what needs an
 * admin's attention today.
 */
#[Title('نظرة عامة')]
class Overview extends Component
{
    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    /**
     * @return list<array{icon: string, label: string, value: string, note: string, tone: string}>
     */
    private function kpis(PlatformStats $stats, array $counts): array
    {
        $monthStart = now()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $collected = $stats->collectedBetween($monthStart, now());
        $collectedLastMonth = $stats->collectedBetween($lastMonthStart, $lastMonthStart->copy()->addDays(now()->day - 1)->endOfDay()->min($monthStart->copy()->subSecond()));
        $signups = $stats->signupsBetween(now()->subDays(30), now());
        $signupsBefore = $stats->signupsBetween(now()->subDays(60), now()->subDays(30));
        $outstanding = Invoice::whereNull('paid_at');
        $collectedChange = Arabic::change($collected, $collectedLastMonth);
        $signupChange = Arabic::change($signups, $signupsBefore);

        return [
            [
                'icon' => 'ti-repeat',
                'label' => 'الإيراد الشهري المتكرر',
                'value' => Arabic::money($stats->monthlyRecurringRevenue()),
                'note' => Arabic::count($counts['active'] + $counts['overdue'], 'مشترك واحد يدفع', 'مشتركان يدفعان', 'مشتركين يدفعون', 'مشتركاً يدفعون'),
                'tone' => 'neutral',
            ],
            [
                'icon' => 'ti-cash',
                'label' => 'المحصّل هذا الشهر',
                'value' => Arabic::money($collected),
                'note' => $collectedChange !== '' ? $collectedChange.' عن الفترة نفسها من الشهر الماضي' : 'لا تحصيل في الفترة نفسها من الشهر الماضي',
                'tone' => $collectedChange === '' ? 'neutral' : ($collected >= $collectedLastMonth ? 'up' : 'down'),
            ],
            [
                'icon' => 'ti-file-invoice',
                'label' => 'بانتظار الدفع',
                'value' => Arabic::money((int) (clone $outstanding)->sum('amount')),
                'note' => Arabic::count((clone $outstanding)->count(), 'فاتورة واحدة', 'فاتورتان', 'فواتير', 'فاتورة'),
                'tone' => (clone $outstanding)->exists() ? 'down' : 'neutral',
            ],
            [
                'icon' => 'ti-user-plus',
                'label' => 'تسجيلات آخر 30 يوماً',
                'value' => Arabic::number($signups),
                'note' => $signupChange !== '' ? $signupChange.' عن الثلاثين يوماً السابقة' : Arabic::number($counts['trial']).' في الفترة التجريبية',
                'tone' => $signupChange === '' ? 'neutral' : ($signups >= $signupsBefore ? 'up' : 'down'),
            ],
            [
                'icon' => 'ti-home-check',
                'label' => 'العقارات المعروضة',
                'value' => Arabic::number($stats->liveListings()),
                'note' => 'على مواقع المشتركين الآن',
                'tone' => 'neutral',
            ],
        ];
    }

    /**
     * Things an admin should act on: overdue invoices, licenses running out
     * and custom domains that stopped pointing at us.
     *
     * @return list<array{icon: string, tone: string, text: string, meta: string, url: string}>
     */
    private function attention(): array
    {
        $items = [];

        $overdue = Invoice::whereNull('paid_at')->with('workspace')->oldest('issued_on')->limit(5)->get();

        foreach ($overdue as $invoice) {
            $items[] = [
                'icon' => $invoice->hasFailedCollection() ? 'ti-credit-card-off' : 'ti-file-invoice',
                'tone' => $invoice->hasFailedCollection() ? 'danger' : 'warning',
                'text' => ($invoice->hasFailedCollection() ? 'تعثّر تحصيل ' : 'فاتورة مستحقة على ').$invoice->workspace->name,
                'meta' => Arabic::money($invoice->amount).' · منذ '.Arabic::date($invoice->issued_on),
                'url' => route('admin.workspaces.show', $invoice->workspace),
            ];
        }

        $expiring = Workspace::where('approval_status', ApprovalStatus::Approved)
            ->whereNull('suspended_at')
            ->whereDate('license_expires_on', '<=', today()->addDays(30))
            ->orderBy('license_expires_on')
            ->limit(5)
            ->get();

        foreach ($expiring as $workspace) {
            $expired = $workspace->license_expires_on->isPast();

            $items[] = [
                'icon' => 'ti-id-badge-2',
                'tone' => $expired ? 'danger' : 'warning',
                'text' => ($expired ? 'انتهى ترخيص ' : 'ينتهي ترخيص ').$workspace->name,
                'meta' => Arabic::date($workspace->license_expires_on),
                'url' => route('admin.workspaces.show', $workspace),
            ];
        }

        $domains = Workspace::where('domain_status', DomainStatus::Error)->whereNotNull('custom_domain')->limit(5)->get();

        foreach ($domains as $workspace) {
            $items[] = [
                'icon' => 'ti-world-off',
                'tone' => 'warning',
                'text' => 'النطاق '.$workspace->custom_domain.' لا يشير إلى تخوم',
                'meta' => $workspace->name,
                'url' => route('admin.workspaces.show', $workspace),
            ];
        }

        return $items;
    }

    public function render(PlatformStats $stats): View
    {
        $counts = $stats->statusCounts();
        $revenue = $stats->monthlyRevenue();
        $signups = $stats->monthlySignups();

        return view('livewire.admin.overview', [
            'counts' => $counts,
            'kpis' => $this->kpis($stats, $counts),
            'revenue' => $revenue,
            'signups' => $signups,
            'planMix' => $stats->planMix(),
            'attention' => $this->attention(),
            'recent' => Workspace::latest()->latest('id')
                ->with(['memberships' => fn ($query) => $query->where('role', MemberRole::Owner)->with('user')])
                ->withExists(['invoices as has_open_invoice' => fn ($query) => $query->whereNull('paid_at')])
                ->limit(6)
                ->get(),
            'actions' => AdminAction::with(['admin', 'workspace', 'user'])->latest('created_at')->latest('id')->limit(8)->get(),
        ]);
    }
}
