<?php

namespace App\Support;

use App\Enums\DealStage;
use App\Enums\SiteEventType;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Membership;
use App\Models\SiteEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Counts behind the dashboard numbers, scoped to what the member may see:
 * agents get their own listings, clients and deals; owners and managers get
 * the whole workspace.
 */
class Metrics
{
    public function __construct(public Membership $membership) {}

    /**
     * @return Builder<SiteEvent>
     */
    public function events(SiteEventType $type, CarbonInterface $from, CarbonInterface $to, ?int $listingId = null): Builder
    {
        $query = SiteEvent::query()
            ->where('workspace_id', $this->membership->workspace_id)
            ->where('type', $type)
            ->whereBetween('occurred_at', [$from, $to]);

        if ($listingId) {
            $query->where('listing_id', $listingId);
        } elseif ($type !== SiteEventType::SiteVisit && ! $this->membership->seesWholeWorkspace()) {
            $query->whereHas('listing', fn (Builder $listing) => $listing->where('agent_id', $this->membership->user_id));
        }

        return $query;
    }

    public function eventCount(SiteEventType $type, CarbonInterface $from, CarbonInterface $to, ?int $listingId = null): int
    {
        return $this->events($type, $from, $to, $listingId)->count();
    }

    /**
     * Daily counts for each day in the range, oldest first.
     *
     * @return array<string, int>
     */
    public function daily(SiteEventType $type, CarbonInterface $from, CarbonInterface $to, ?int $listingId = null): array
    {
        $counts = $this->events($type, $from, $to, $listingId)
            ->get(['occurred_at'])
            ->countBy(fn (SiteEvent $event) => $event->occurred_at->toDateString());

        $days = [];

        for ($day = Carbon::parse($from)->startOfDay(); $day->lte($to); $day->addDay()) {
            $days[$day->toDateString()] = $counts->get($day->toDateString(), 0);
        }

        return $days;
    }

    public function newClients(CarbonInterface $from, CarbonInterface $to): int
    {
        return Client::visibleTo($this->membership)->whereBetween('created_at', [$from, $to])->count();
    }

    /**
     * Inquiries, then deals that reached each stage, for leads that came in during the range.
     *
     * @return list<array{label: string, count: int}>
     */
    public function funnel(CarbonInterface $from, CarbonInterface $to): array
    {
        $inquiries = Inquiry::visibleTo($this->membership)->whereBetween('created_at', [$from, $to])->count();

        $reached = Deal::visibleTo($this->membership)
            ->whereBetween('created_at', [$from, $to])
            ->get(['furthest_stage'])
            ->map(fn (Deal $deal) => $deal->furthest_stage->rank());

        $atLeast = fn (DealStage $stage) => $reached->filter(fn (int $rank) => $rank >= $stage->rank())->count();

        return [
            ['label' => 'استفسارات', 'count' => max($inquiries, $atLeast(DealStage::Contacted))],
            ['label' => 'تم التواصل', 'count' => $atLeast(DealStage::Contacted)],
            ['label' => 'معاينة', 'count' => $atLeast(DealStage::Viewing)],
            ['label' => 'تفاوض', 'count' => $atLeast(DealStage::Negotiation)],
            ['label' => 'ناجحة', 'count' => $atLeast(DealStage::Won)],
        ];
    }

    /**
     * Average first-reply time and the share answered within the target.
     *
     * @return array{average: ?int, onTime: ?int}
     */
    public function responseTimes(CarbonInterface $from, CarbonInterface $to, ?int $agentId = null): array
    {
        $query = Inquiry::visibleTo($this->membership)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('responded_at');

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        /** @var Collection<int, int> $minutes */
        $minutes = $query->get(['created_at', 'responded_at'])->map(fn (Inquiry $inquiry) => $inquiry->waitMinutes());

        if ($minutes->isEmpty()) {
            return ['average' => null, 'onTime' => null];
        }

        return [
            'average' => (int) round($minutes->avg()),
            'onTime' => (int) round($minutes->filter(fn (int $value) => $value <= Inquiry::RESPONSE_TARGET_MINUTES)->count() / $minutes->count() * 100),
        ];
    }
}
