<?php

namespace App\Support;

use App\Enums\ApprovalStatus;
use App\Enums\Plan;
use App\Enums\WorkspaceStatus;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Platform-wide numbers for the admin overview: subscribers, revenue and growth.
 */
class PlatformStats
{
    /**
     * Number of workspaces in each admin status.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        return collect(WorkspaceStatus::cases())
            ->mapWithKeys(fn (WorkspaceStatus $status) => [$status->value => Workspace::inStatus($status)->count()])
            ->all();
    }

    /**
     * What paying workspaces are billed each month at their current plan and seats.
     */
    public function monthlyRecurringRevenue(): int
    {
        return (int) Workspace::paying()->get(['id', 'plan', 'seats'])->sum(fn (Workspace $workspace) => $workspace->monthlyTotal());
    }

    public function collectedBetween(Carbon $from, Carbon $to): int
    {
        return (int) Invoice::whereBetween('paid_at', [$from, $to])->sum('amount');
    }

    public function signupsBetween(Carbon $from, Carbon $to): int
    {
        return Workspace::whereBetween('created_at', [$from, $to])->count();
    }

    /**
     * Listings visitors can see right now, on sites that are approved and not suspended.
     */
    public function liveListings(): int
    {
        return Listing::live()
            ->whereHas('workspace', fn (Builder $query) => $query->where('approval_status', ApprovalStatus::Approved)->whereNull('suspended_at'))
            ->count();
    }

    /**
     * Revenue collected in each of the last months, oldest first, keyed "Y-m".
     *
     * @return array<string, int>
     */
    public function monthlyRevenue(int $months = 6): array
    {
        $from = now()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $totals = Invoice::where('paid_at', '>=', $from)->get(['amount', 'paid_at'])
            ->groupBy(fn (Invoice $invoice) => $invoice->paid_at->format('Y-m'))
            ->map(fn ($invoices) => (int) $invoices->sum('amount'));

        return $this->months($from, $months)->mapWithKeys(fn (string $key) => [$key => $totals[$key] ?? 0])->all();
    }

    /**
     * New workspaces in each of the last months, oldest first, keyed "Y-m".
     *
     * @return array<string, int>
     */
    public function monthlySignups(int $months = 6): array
    {
        $from = now()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $counts = Workspace::where('created_at', '>=', $from)->get(['id', 'created_at'])
            ->groupBy(fn (Workspace $workspace) => $workspace->created_at->format('Y-m'))
            ->map->count();

        return $this->months($from, $months)->mapWithKeys(fn (string $key) => [$key => $counts[$key] ?? 0])->all();
    }

    /**
     * Approved, active workspaces on each plan, trials included.
     *
     * @return list<array{plan: Plan, count: int, percent: int}>
     */
    public function planMix(): array
    {
        $counts = Workspace::where('approval_status', ApprovalStatus::Approved)
            ->whereNull('suspended_at')
            ->toBase()
            ->selectRaw('plan, count(*) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan');

        $all = max(1, (int) $counts->sum());

        return array_map(fn (Plan $plan) => [
            'plan' => $plan,
            'count' => (int) ($counts[$plan->value] ?? 0),
            'percent' => (int) round(($counts[$plan->value] ?? 0) / $all * 100),
        ], Plan::cases());
    }

    /**
     * @return Collection<int, string>
     */
    private function months(Carbon $from, int $count): Collection
    {
        return collect(range(0, $count - 1))->map(fn (int $offset) => $from->copy()->addMonthsNoOverflow($offset)->format('Y-m'));
    }
}
