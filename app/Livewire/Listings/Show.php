<?php

namespace App\Livewire\Listings;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\SiteEventType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Support\Arabic;
use App\Support\ListingQuality;
use App\Support\Metrics;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
    public int $listingId;

    #[Url]
    public string $metric = 'views';

    public function mount(Listing $listing): void
    {
        Gate::authorize('view', $listing);

        $this->listingId = $listing->id;
    }

    /**
     * @return list<array{icon: string, label: string, value: string, note: string, tone: string}>
     */
    private function kpis(Listing $listing, Metrics $metrics): array
    {
        $now = now();
        $from = $now->copy()->subDays(30);
        $previousFrom = $now->copy()->subDays(60);

        $count = fn (SiteEventType $type, $start, $end) => $metrics->eventCount($type, $start, $end, $listing->id);
        $views = $count(SiteEventType::ListingView, $from, $now);
        $inquiries = $listing->inquiries()->whereBetween('created_at', [$from, $now])->count();
        $previousInquiries = $listing->inquiries()->whereBetween('created_at', [$previousFrom, $from])->count();

        $kpi = fn (string $icon, string $label, int $current, int $previous, bool $difference = false) => [
            'icon' => $icon,
            'label' => $label,
            'value' => Arabic::number($current),
            'note' => $difference ? Arabic::difference($current, $previous) : Arabic::change($current, $previous),
            'tone' => $current >= $previous ? 'up' : 'down',
        ];

        $viewsKpi = $kpi('ti-eye', 'المشاهدات', $views, $count(SiteEventType::ListingView, $previousFrom, $from));
        $viewsKpi['note'] .= ' عن الشهر السابق';

        return [
            $viewsKpi,
            $kpi('ti-brand-whatsapp', 'نقرات واتساب', $count(SiteEventType::WhatsappClick, $from, $now), $count(SiteEventType::WhatsappClick, $previousFrom, $from)),
            $kpi('ti-phone', 'الاتصالات', $count(SiteEventType::CallClick, $from, $now), $count(SiteEventType::CallClick, $previousFrom, $from)),
            $kpi('ti-message-circle', 'الاستفسارات', $inquiries, $previousInquiries, true),
            $kpi('ti-share', 'المشاركات', $count(SiteEventType::Share, $from, $now), $count(SiteEventType::Share, $previousFrom, $from), true),
            [
                'icon' => 'ti-percentage',
                'label' => 'معدل التحويل',
                'value' => $views ? Arabic::percent($inquiries / $views * 100, 1) : '—',
                'note' => 'استفسار لكل مشاهدة',
                'tone' => 'neutral',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function series(Listing $listing, Metrics $metrics): array
    {
        $from = today()->subDays(29);

        if ($this->metric === 'inquiries') {
            $counts = $listing->inquiries()->where('created_at', '>=', $from)->get(['created_at'])
                ->countBy(fn (Inquiry $inquiry) => $inquiry->created_at->toDateString());

            $days = [];
            for ($day = $from->copy(); $day->lte(today()); $day->addDay()) {
                $days[$day->toDateString()] = $counts->get($day->toDateString(), 0);
            }

            return $days;
        }

        $type = $this->metric === 'whatsapp' ? SiteEventType::WhatsappClick : SiteEventType::ListingView;

        return $metrics->daily($type, $from, now(), $listing->id);
    }

    /**
     * @return list<array{source: LeadSource, count: int, percent: int}>
     */
    private function sources(Listing $listing): array
    {
        $counts = $listing->siteEvents()->where('type', SiteEventType::ListingView)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->get(['source'])
            ->countBy(fn ($event) => $event->source?->value ?? LeadSource::Direct->value);

        $total = max(1, $counts->sum());

        return array_map(fn (LeadSource $source) => [
            'source' => $source,
            'count' => $counts->get($source->value, 0),
            'percent' => (int) round($counts->get($source->value, 0) / $total * 100),
        ], LeadSource::cases());
    }

    public function render(): View
    {
        $listing = Listing::visibleTo($this->membership())
            ->with(['agent', 'workspace', 'coverPhoto'])
            ->withCount('photos')
            ->findOrFail($this->listingId);

        $metrics = new Metrics($this->membership());

        $inquiries = $listing->inquiries()->latest()->limit(6)->get();
        $stages = Deal::where('listing_id', $listing->id)->whereIn('client_id', $inquiries->pluck('client_id')->filter())
            ->pluck('stage', 'client_id');

        return view('livewire.listings.show', [
            'listing' => $listing,
            'kpis' => $this->kpis($listing, $metrics),
            'series' => $this->series($listing, $metrics),
            'sources' => $this->sources($listing),
            'quality' => new ListingQuality($listing, $listing->photos_count),
            'inquiries' => $inquiries,
            'stages' => $stages->map(fn ($stage) => $stage instanceof DealStage ? $stage : DealStage::from($stage)),
            'history' => $listing->activities()->with('user')->latest('occurred_at')->limit(8)->get(),
            'chartLabels' => [today()->subDays(29), today()->subDays(15), today()],
        ])->title($listing->title);
    }
}
