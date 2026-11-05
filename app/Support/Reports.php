<?php

namespace App\Support;

use App\Enums\DealStage;
use App\Enums\ListingStatus;
use App\Enums\SiteEventType;
use App\Models\Client;
use App\Models\CommissionTarget;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use App\Models\SiteEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Office reports for a date range: visits, lead sources, the funnel, monthly
 * targets and per-agent performance.
 */
class Reports
{
    private Metrics $metrics;

    public function __construct(public Membership $membership, public CarbonInterface $from, public CarbonInterface $to)
    {
        $this->metrics = new Metrics($membership);
    }

    /**
     * @return array<string, int>
     */
    public function visits(): array
    {
        return $this->metrics->daily(SiteEventType::SiteVisit, $this->from, $this->to);
    }

    /**
     * @return list<array{source: \App\Enums\LeadSource, count: int, percent: int}>
     */
    public function clientSources(): array
    {
        $counts = Client::visibleTo($this->membership)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get(['source'])
            ->countBy(fn (Client $client) => $client->source->value);

        $total = max(1, $counts->sum());

        return collect(\App\Enums\LeadSource::cases())
            ->map(fn ($source) => [
                'source' => $source,
                'count' => $counts->get($source->value, 0),
                'percent' => (int) round($counts->get($source->value, 0) / $total * 100),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    public function funnel(): array
    {
        return $this->metrics->funnel($this->from, $this->to);
    }

    /**
     * Commission and deal targets for the month the range ends in.
     *
     * @return list<array{name: string, earned: int, target: int, percent: int, color: string, won: int, dealsTarget: int}>
     */
    public function targets(): array
    {
        $month = Carbon::parse($this->to)->startOfMonth();

        return CommissionTarget::where('workspace_id', $this->membership->workspace_id)
            ->whereDate('month', $month)
            ->when(! $this->membership->seesWholeWorkspace(), fn (Builder $query) => $query->where('user_id', $this->membership->user_id))
            ->with('user')
            ->get()
            ->map(function (CommissionTarget $target) use ($month): array {
                $won = Deal::where('workspace_id', $target->workspace_id)
                    ->where('agent_id', $target->user_id)
                    ->where('stage', DealStage::Won)
                    ->whereBetween('closed_at', [$month, $month->copy()->endOfMonth()])
                    ->get(['commission']);

                $earned = (int) $won->sum('commission');
                $percent = $target->commission_target > 0 ? (int) round($earned / $target->commission_target * 100) : 0;

                return [
                    'name' => $target->user->name,
                    'earned' => $earned,
                    'target' => (int) $target->commission_target,
                    'percent' => $percent,
                    'color' => match (true) {
                        $percent >= 75 => 'bg-success',
                        $percent >= 30 => 'bg-warning-dot',
                        default => 'bg-danger',
                    },
                    'won' => $won->count(),
                    'dealsTarget' => $target->deals_target,
                ];
            })
            ->all();
    }

    /**
     * One row per member with activity, plus a total row.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function agents(): Collection
    {
        $workspaceId = $this->membership->workspace_id;
        $range = [$this->from, $this->to];

        $members = Membership::where('workspace_id', $workspaceId)->active()->with('user')
            ->when(! $this->membership->seesWholeWorkspace(), fn (Builder $query) => $query->whereKey($this->membership->id))
            ->orderBy('id')
            ->get();

        $rows = $members->map(function (Membership $member) use ($workspaceId, $range): array {
            $agentId = $member->user_id;
            $listings = Listing::where('workspace_id', $workspaceId)->where('agent_id', $agentId);
            $reminders = Reminder::where('workspace_id', $workspaceId)->where('user_id', $agentId)->whereBetween('due_at', $range)->get(['completed_at']);
            $won = Deal::where('workspace_id', $workspaceId)->where('agent_id', $agentId)->where('stage', DealStage::Won)->whereBetween('closed_at', $range)->get(['commission']);
            $response = $this->metrics->responseTimes($this->from, $this->to, $agentId);

            return [
                'name' => $member->user->name,
                'added' => (clone $listings)->whereBetween('created_at', $range)->count(),
                'active' => (clone $listings)->whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])->count(),
                'views' => SiteEvent::where('type', SiteEventType::ListingView)->whereBetween('occurred_at', $range)
                    ->whereIn('listing_id', (clone $listings)->select('id'))->count(),
                'clients' => Client::where('workspace_id', $workspaceId)->where('agent_id', $agentId)->whereBetween('created_at', $range)->count(),
                'response' => $response['average'],
                'followUpsDone' => $reminders->whereNotNull('completed_at')->count(),
                'followUps' => $reminders->count(),
                'won' => $won->count(),
                'commission' => (int) $won->sum('commission'),
                'total' => false,
            ];
        })->filter(fn (array $row) => $row['added'] + $row['active'] + $row['views'] + $row['clients'] + $row['followUps'] + $row['won'] > 0)->values();

        if ($rows->count() > 1) {
            $responses = $rows->pluck('response')->filter(fn ($value) => $value !== null);

            $rows->push([
                'name' => 'الإجمالي',
                'added' => $rows->sum('added'),
                'active' => $rows->sum('active'),
                'views' => $rows->sum('views'),
                'clients' => $rows->sum('clients'),
                'response' => $responses->isEmpty() ? null : (int) round($responses->avg()),
                'followUpsDone' => $rows->sum('followUpsDone'),
                'followUps' => $rows->sum('followUps'),
                'won' => $rows->sum('won'),
                'commission' => $rows->sum('commission'),
                'total' => true,
            ]);
        }

        return $rows;
    }
}
