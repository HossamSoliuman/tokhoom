<?php

namespace Database\Seeders\Concerns;

use App\Enums\ClientType;
use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\LostReason;
use App\Enums\ReminderChannel;
use App\Enums\SiteEventType;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Reminder;
use App\Models\SiteEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Locations;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Believable history for a demo workspace: listings published over time,
 * leads asking about them, deals moving through the stages, the follow-ups
 * and notes behind them, and the site traffic around it all.
 */
trait GeneratesDemoActivity
{
    private const MALE_NAMES = ['محمد', 'عبدالله', 'سعد', 'عبدالرحمن', 'نايف', 'سلمان', 'عمر', 'مشاري', 'راكان', 'زياد', 'حمد', 'إبراهيم', 'طلال', 'وليد', 'ناصر', 'صالح', 'سامي', 'هشام', 'عادل', 'يزيد', 'أنس', 'نواف', 'معاذ', 'أحمد'];

    private const FEMALE_NAMES = ['العنود', 'لطيفة', 'هيفاء', 'منى', 'أسماء', 'حصة', 'جواهر', 'مها', 'رنا', 'غادة', 'وعد', 'شوق', 'بشاير', 'أروى', 'ديما', 'لجين'];

    private const FAMILY_NAMES = ['العتيبي', 'القحطاني', 'الدوسري', 'الشمري', 'الحربي', 'المطيري', 'الغامدي', 'الزهراني', 'السبيعي', 'العنزي', 'الشهري', 'المالكي', 'القرني', 'العمري', 'الرشيدي', 'البقمي', 'الجهني', 'السهلي', 'الخالدي', 'التميمي', 'الشهراني', 'العسيري', 'اليامي', 'الفيفي'];

    private const SOURCE_WEIGHTS = [[LeadSource::Site, 44], [LeadSource::SharedLink, 28], [LeadSource::Qr, 16], [LeadSource::Direct, 12]];

    /**
     * When each listing left the market (sold, rented or withdrawn), by listing id.
     *
     * @var array<int, Carbon>
     */
    private array $offMarketAt = [];

    /**
     * @var array<int, Carbon>
     */
    private array $lastInquiryAt = [];

    /**
     * @var array<string, true>
     */
    private array $takenNames = [];

    /**
     * @var array<string, true>
     */
    private array $takenMobiles = [];

    /**
     * Published listings spread between two moments, all on the market at first.
     *
     * @param  list<User>  $agents
     * @return list<Listing>
     */
    protected function generateListings(Workspace $workspace, array $agents, int $count, CarbonInterface $from, CarbonInterface $to): array
    {
        $districts = Locations::districts($workspace->city);
        $listings = [];

        foreach (range(1, $count) as $index) {
            $agent = $this->any($agents);
            $type = $this->pick([[ListingType::Apartment, 34], [ListingType::Villa, 26], [ListingType::Floor, 16], [ListingType::Land, 14], [ListingType::Commercial, 10]]);
            $purpose = match ($type) {
                ListingType::Land => ListingPurpose::Sale,
                ListingType::Commercial => $this->chance(70) ? ListingPurpose::Rent : ListingPurpose::Sale,
                default => $this->chance(55) ? ListingPurpose::Sale : ListingPurpose::Rent,
            };
            $sale = $purpose === ListingPurpose::Sale;
            $district = $this->any($districts);
            $area = match ($type) {
                ListingType::Villa => $this->between(300, 600, 25),
                ListingType::Apartment => $this->between(110, 220, 5),
                ListingType::Floor => $this->between(180, 320, 10),
                ListingType::Land => $this->between(375, 900, 25),
                ListingType::Commercial => $this->between(120, 600, 20),
            };
            [$bedrooms, $bathrooms, $floors] = match ($type) {
                ListingType::Villa => [mt_rand(4, 7), mt_rand(4, 6), 2],
                ListingType::Apartment => [mt_rand(2, 4), mt_rand(2, 3), 1],
                ListingType::Floor => [mt_rand(3, 5), mt_rand(2, 4), 1],
                ListingType::Commercial => [null, mt_rand(1, 2), 1],
                ListingType::Land => [null, null, null],
            };
            $publishedAt = $this->daytime($this->momentBetween($from, $to), $from, $to);

            $listing = new Listing([
                'workspace_id' => $workspace->id,
                'agent_id' => $agent->id,
                'purpose' => $purpose,
                'type' => $type,
                'status' => ListingStatus::Available,
                'price' => $this->price($type, $sale),
                'area' => $area,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'age' => $type === ListingType::Land ? null : mt_rand(0, 15),
                'floors' => $floors,
                'city' => $workspace->city,
                'district' => $district,
                'features' => $this->some($this->featuresFor($type), mt_rand(1, 4)),
                'description' => $this->describe($type, $district, $area, $bedrooms),
                'ad_license_number' => '72'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                'ad_license_expires_on' => now()->addDays(mt_rand(45, 330))->toDateString(),
                'brokerage_contract_number' => '44100'.mt_rand(10000, 99999),
                'tour_url' => $type === ListingType::Villa && $this->chance(50) ? 'https://tour.example.com/'.$workspace->slug.'-'.$index : null,
                'published_at' => $publishedAt,
                'price_changed_at' => $publishedAt,
            ]);
            $listing->created_at = $publishedAt;
            $listing->save();

            $captions = $this->captionsFor($type);

            foreach (range(0, ($this->chance(75) ? mt_rand(6, 10) : mt_rand(2, 5)) - 1) as $position) {
                $listing->photos()->create([
                    'path' => 'sample:'.($position === 0 ? $type->value.' ' : '').$captions[$position % count($captions)],
                    'position' => $position,
                ]);
            }

            $this->record($listing, 'listing.published', ['by' => $agent->name], $publishedAt, $agent);

            if ($publishedAt->diffInDays(now(), true) > 40 && $this->chance(55)) {
                $changedFrom = $publishedAt->copy()->addDays(10)->max(now()->subDays(50));
                $changedAt = $this->daytime($this->momentBetween($changedFrom, now()->subDay()), $changedFrom, now()->subDay());
                $listing->forceFill(['price_changed_at' => $changedAt])->save();
                $this->record($listing, 'listing.price_changed', [], $changedAt, $agent);
            }

            $listings[] = $listing;
        }

        return $listings;
    }

    /**
     * Leads asking about the listings over time, with the deals, follow-ups,
     * notes and timeline that grew out of them. Nothing happens after $to.
     *
     * @param  list<Listing>  $listings
     */
    protected function generateLeads(Workspace $workspace, array $listings, int $count, CarbonInterface $from, CarbonInterface $to): void
    {
        $this->takenNames = Client::where('workspace_id', $workspace->id)->pluck('name')->flip()->map(fn () => true)->all();
        $this->takenMobiles = Client::where('workspace_id', $workspace->id)->pluck('mobile')->flip()->map(fn () => true)->all();

        $moments = [];

        foreach (range(1, $count) as $lead) {
            $moments[] = $this->daytime($this->momentBetween($from, $to, 0.7), $from, $to);
        }

        usort($moments, fn (Carbon $a, Carbon $b) => $a <=> $b);

        $agents = collect($listings)->map(fn (Listing $listing) => $listing->agent)->unique('id')->values()->all();
        $plans = [];

        foreach ($moments as $at) {
            if ($this->chance(12)) {
                $this->ownerLead($workspace, $this->any($agents), $at, $to);

                continue;
            }

            $open = array_values(array_filter($listings, fn (Listing $listing) => $listing->published_at->lt($at->copy()->subHours(12))
                && (! isset($this->offMarketAt[$listing->id]) || $this->offMarketAt[$listing->id]->gt($at))));

            if ($open === []) {
                continue;
            }

            $listing = $this->pick(array_map(fn (Listing $listing) => [$listing, max(5, 60 - (int) $listing->published_at->diffInDays($at, true))], $open));
            $client = $this->inquiringClient($workspace, $listing, $at, $to);
            $this->lastInquiryAt[$listing->id] = $at;

            if ($this->chance(70)) {
                $plans[] = $this->planDeal($client, $listing, $at, $to);
            } elseif ($at->copy()->addDay()->lt($to)) {
                $this->followUp($client, 'اتصال متابعة بعد الاستفسار', $at->copy()->addHours(mt_rand(3, 30))->min($to));
            }
        }

        foreach ($plans as $plan) {
            $this->closeDeal($plan, $to);
        }

        $this->withdrawOne($listings, $to);
    }

    /**
     * Site visits per day, rising from the first rate to the last, quieter on Fridays.
     */
    protected function generateVisits(Workspace $workspace, CarbonInterface $from, CarbonInterface $to, int $firstDaily, int $lastDaily): void
    {
        $rows = [];
        $days = max(1, (int) Carbon::parse($from)->startOfDay()->diffInDays($to, true));

        for ($day = Carbon::parse($from)->startOfDay(), $index = 0; $day->lte($to); $day->addDay(), $index++) {
            $rhythm = match ($day->dayOfWeek) {
                Carbon::FRIDAY => 0.8,
                Carbon::THURSDAY => 1.15,
                default => 1.0,
            };
            $count = (int) round(($firstDaily + ($lastDaily - $firstDaily) * $index / $days) * $rhythm * mt_rand(80, 120) / 100);

            foreach (range(1, max(1, $count)) as $visit) {
                $rows[] = $this->event($workspace->id, null, SiteEventType::SiteVisit, $this->momentOn($day, $to));
            }
        }

        $this->insertEvents($rows);
    }

    /**
     * Views, contact clicks and shares for each listing while it was on the market.
     *
     * @param  list<Listing>  $listings
     */
    protected function generateListingTraffic(array $listings, float $scale = 1.0): void
    {
        foreach ($listings as $listing) {
            if (! $listing->published_at) {
                continue;
            }

            $base = match ($listing->type) {
                ListingType::Villa => 14,
                ListingType::Apartment => 10,
                ListingType::Floor => 8,
                ListingType::Land => 6,
                ListingType::Commercial => 4,
            };

            $until = isset($this->offMarketAt[$listing->id]) ? $this->offMarketAt[$listing->id]->copy()->addDays(2)->min(now()) : now();

            $this->generateListingEvents($listing, $listing->published_at, $until, $base * $scale * mt_rand(60, 150) / 100);
        }
    }

    /**
     * Daily listing views at roughly the given rate, busier in the first week,
     * with WhatsApp clicks, calls and shares following the views.
     */
    protected function generateListingEvents(Listing $listing, CarbonInterface $from, CarbonInterface $to, float $viewsPerDay): void
    {
        $rows = [];

        for ($day = Carbon::parse($from)->startOfDay(); $day->lte($to); $day->addDay()) {
            $launch = $day->diffInDays($from, true) < 7 ? 1.4 : 1.0;
            $views = (int) round($viewsPerDay * $launch * mt_rand(65, 135) / 100);

            for ($view = 0; $view < $views; $view++) {
                $at = $this->momentOn($day, $to);

                if ($at->lt($from)) {
                    $at = Carbon::parse($from)->addMinutes(mt_rand(1, 300))->min($to);
                }

                $rows[] = $this->event($listing->workspace_id, $listing->id, SiteEventType::ListingView, $at);

                foreach ([[SiteEventType::WhatsappClick, 65], [SiteEventType::CallClick, 24], [SiteEventType::Share, 17]] as [$type, $perThousand]) {
                    if (mt_rand(1, 1000) <= $perThousand) {
                        $rows[] = $this->event($listing->workspace_id, $listing->id, $type, $at->copy()->addSeconds(mt_rand(20, 240))->min(now()));
                    }
                }
            }
        }

        $this->insertEvents($rows);
    }

    /**
     * An owner who wants to market a property, with no inquiry behind them.
     */
    private function ownerLead(Workspace $workspace, User $agent, Carbon $at, CarbonInterface $to): void
    {
        $type = $this->any([ListingType::Villa, ListingType::Apartment, ListingType::Land, ListingType::Floor]);
        $purpose = $type === ListingType::Land || $this->chance(60) ? ListingPurpose::Sale : ListingPurpose::Rent;

        $client = $this->newClient($workspace, $agent, ClientType::Owner, $this->any([LeadSource::Direct, LeadSource::Direct, LeadSource::SharedLink]), $at, female: false);

        $client->notes()->forceCreate([
            'user_id' => $agent->id,
            'body' => 'يرغب بعرض '.$type->label().' في حي '.$this->any(Locations::districts($workspace->city)).' '.$purpose->label().'، بانتظار صورة الصك.',
            'is_shared' => true,
            'created_at' => $at->copy()->addMinutes(mt_rand(10, 90))->min($to),
        ]);

        if ($at->copy()->addDays(2)->lt($to)) {
            $this->followUp($client, 'زيارة العقار مع المالك', $at->copy()->addDays(mt_rand(1, 4))->setTime(mt_rand(16, 20), 0)->min($to));
        }
    }

    private function inquiringClient(Workspace $workspace, Listing $listing, Carbon $at, CarbonInterface $to): Client
    {
        $sale = $listing->purpose === ListingPurpose::Sale;
        $investor = $sale && in_array($listing->type, [ListingType::Land, ListingType::Commercial], true) && $this->chance(40);
        $type = match (true) {
            $investor => ClientType::Investor,
            $sale => ClientType::Buyer,
            default => ClientType::Tenant,
        };

        $client = $this->newClient($workspace, $listing->agent, $type, $this->pick(self::SOURCE_WEIGHTS), $at, female: $this->chance(35));

        $reply = $this->pick([[mt_rand(3, 15), 60], [mt_rand(16, 40), 30], [mt_rand(41, 120), 10]]);
        $respondedAt = $at->copy()->addMinutes($reply);

        Inquiry::create([
            'workspace_id' => $workspace->id,
            'listing_id' => $listing->id,
            'client_id' => $client->id,
            'agent_id' => $listing->agent_id,
            'name' => $client->name,
            'mobile' => $client->mobile,
            'message' => $this->chance(50) ? $this->any($this->inquiryMessages($listing->purpose)) : null,
            'source' => $client->source,
            'responded_at' => $respondedAt->lte(now()) ? $respondedAt : null,
            'created_at' => $at,
        ]);

        $this->record($client, 'client.inquiry', ['source' => $client->source->label(), 'listing' => $listing->title], $at);

        if ($this->chance(40)) {
            $price = (int) $listing->price;
            $request = $client->requests()->create([
                'purpose' => $listing->purpose,
                'types' => [$listing->type->value],
                'districts' => array_values(array_unique([$listing->district, $this->any(Locations::districts($workspace->city))])),
                'budget_min' => $this->round($price * 0.8),
                'budget_max' => $this->round($price * 1.1),
            ]);

            $this->record($client, 'client.request_added', ['summary' => $request->summary()], $this->daytime($at->copy()->addMinutes(mt_rand(20, 180)), $at, $to), $listing->agent);
        }

        if ($this->chance(30)) {
            $client->notes()->forceCreate([
                'user_id' => $listing->agent_id,
                'body' => $this->any($this->notesFor($listing)),
                'is_shared' => $this->chance(80),
                'created_at' => $at->copy()->addHours(mt_rand(1, 48))->min($to),
            ]);
        }

        return $client;
    }

    private function newClient(Workspace $workspace, User $agent, ClientType $type, LeadSource $source, Carbon $at, bool $female): Client
    {
        do {
            $name = $this->any($female ? self::FEMALE_NAMES : self::MALE_NAMES).' '.$this->any(self::FAMILY_NAMES);
        } while (isset($this->takenNames[$name]));

        do {
            $mobile = '05'.$this->any(['0', '3', '4', '5', '6', '8', '9']).str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (isset($this->takenMobiles[$mobile]));

        $this->takenNames[$name] = $this->takenMobiles[$mobile] = true;

        $client = new Client([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'name' => $name,
            'mobile' => $mobile,
            'type' => $type,
            'source' => $source,
        ]);
        $client->created_at = $at;
        $client->save();

        return $client;
    }

    /**
     * Where a lead's deal ends up, decided by how long ago the lead arrived.
     *
     * @return array{client: Client, listing: Listing, createdAt: Carbon, outcome: DealStage, furthest: DealStage, closeAt: ?Carbon}
     */
    private function planDeal(Client $client, Listing $listing, Carbon $at, CarbonInterface $to): array
    {
        $age = (int) $at->diffInDays(now(), true);
        $createdAt = $this->daytime($at->copy()->addHours(mt_rand(1, 20)), $at, $to);
        $closeAt = $this->daytime($createdAt->copy()->addDays(mt_rand(6, 40)), $createdAt, $to);

        $outcome = $this->pick(match (true) {
            $age > 45 => [[DealStage::Won, 35], [DealStage::Lost, 50], [DealStage::Negotiation, 15]],
            $age > 14 => [[DealStage::Won, 25], [DealStage::Lost, 30], [DealStage::Contacted, 15], [DealStage::Viewing, 18], [DealStage::Negotiation, 12]],
            default => [[DealStage::Lost, 15], [DealStage::New, 20], [DealStage::Contacted, 30], [DealStage::Viewing, 25], [DealStage::Negotiation, 10]],
        });

        $closes = in_array($outcome, [DealStage::Won, DealStage::Lost], true);

        if ($closes && $closeAt->gte($to)) {
            $outcome = DealStage::Negotiation;
            $closes = false;
        }

        if ($outcome === DealStage::Won) {
            if (isset($this->offMarketAt[$listing->id])) {
                $outcome = DealStage::Lost;
            } else {
                $this->offMarketAt[$listing->id] = $closeAt;
            }
        }

        $furthest = match ($outcome) {
            DealStage::Lost => $this->pick([[DealStage::Contacted, 45], [DealStage::Viewing, 35], [DealStage::Negotiation, 20]]),
            default => $outcome,
        };

        return [
            'client' => $client,
            'listing' => $listing,
            'createdAt' => $createdAt,
            'outcome' => $outcome,
            'furthest' => $furthest,
            'closeAt' => $closes ? $closeAt : null,
        ];
    }

    /**
     * Store the deal with its timeline. Open deals on a listing that has since
     * been sold or rented to someone else are closed as lost.
     *
     * @param  array{client: Client, listing: Listing, createdAt: Carbon, outcome: DealStage, furthest: DealStage, closeAt: ?Carbon}  $plan
     */
    private function closeDeal(array $plan, CarbonInterface $to): void
    {
        ['client' => $client, 'listing' => $listing, 'createdAt' => $createdAt, 'outcome' => $outcome, 'furthest' => $furthest, 'closeAt' => $closeAt] = $plan;
        $agent = $listing->agent;
        $reason = null;

        if ($outcome === DealStage::Lost) {
            $reason = $this->pick([[LostReason::Price, 30], [LostReason::OtherListing, 25], [LostReason::NoInterest, 20], [LostReason::Unreachable, 15], [LostReason::Other, 10]]);
        }

        $soldAt = $this->offMarketAt[$listing->id] ?? null;

        if ($outcome !== DealStage::Won && $soldAt && (! $closeAt || $closeAt->gt($soldAt))) {
            $outcome = DealStage::Lost;
            $reason = LostReason::Other;
            $closeAt = $this->daytime($soldAt->copy()->addHours(mt_rand(20, 72)), $soldAt, $to);
        }

        $end = $closeAt ?? $createdAt->copy()->addDays(mt_rand(3, 25))->min($to);
        $steps = [
            DealStage::Contacted->value => $this->daytime($createdAt->copy()->addHours(mt_rand(1, 6)), $createdAt, $end),
            DealStage::Viewing->value => $this->daytime($createdAt->copy()->addMinutes((int) ($createdAt->diffInMinutes($end, true) * 0.35)), $createdAt, $end),
            DealStage::Negotiation->value => $this->daytime($createdAt->copy()->addMinutes((int) ($createdAt->diffInMinutes($end, true) * 0.7)), $createdAt, $end),
        ];

        $previous = $createdAt;

        foreach ($steps as $stage => $moment) {
            $steps[$stage] = $previous = $moment->max($previous->copy()->addMinutes(30))->min($end);
        }

        $final = $outcome === DealStage::Won ? $this->round((int) $listing->price * mt_rand(93, 100) / 100) : null;

        $deal = new Deal([
            'workspace_id' => $listing->workspace_id,
            'client_id' => $client->id,
            'listing_id' => $listing->id,
            'agent_id' => $agent->id,
            'stage' => $outcome,
            'furthest_stage' => $outcome === DealStage::Lost ? $furthest : $outcome,
            'value' => $listing->price,
            'final_price' => $final,
            'lost_reason' => $reason,
            'closed_at' => $closeAt,
        ]);
        $deal->setRelation('listing', $listing);
        $deal->commission = $final ? $deal->suggestedCommission($final) : null;
        $deal->created_at = $createdAt;
        $deal->save();

        $reached = $deal->furthest_stage->rank();

        if ($reached >= DealStage::Contacted->rank()) {
            $this->record($client, 'client.listing_sent', ['listing' => $listing->title, 'listing_id' => $listing->id], $steps[DealStage::Contacted->value], $agent);
        }

        if ($reached >= DealStage::Viewing->rank()) {
            $this->followUp($client, 'تأكيد موعد المعاينة', $steps[DealStage::Viewing->value]->copy()->subHours(3)->max($createdAt));
            $this->record($client, 'client.viewing', ['listing' => $listing->title, 'agent' => $agent->name], $steps[DealStage::Viewing->value], $agent);
        }

        if ($reached >= DealStage::Negotiation->rank()) {
            $this->followUp($client, 'إرسال عرض السعر', $steps[DealStage::Negotiation->value]->copy()->subHours(2)->max($createdAt));
            $this->record($client, 'client.deal_stage', ['stage' => DealStage::Negotiation->label()], $steps[DealStage::Negotiation->value], $agent);
        }

        if ($outcome === DealStage::Won) {
            $rental = $listing->purpose === ListingPurpose::Rent;
            $this->followUp($client, $rental ? 'تجهيز عقد الإيجار' : 'متابعة إجراءات الإفراغ', $closeAt->copy()->subDay()->max($createdAt));
            $this->record($client, 'client.deal_won', ['listing' => $listing->title], $closeAt, $agent);

            $status = $rental ? ListingStatus::Rented : ListingStatus::Sold;
            $listing->forceFill(['status' => $status])->save();
            $this->record($listing, 'listing.status_changed', ['status' => $status->value], $closeAt, $agent);
        } elseif ($outcome === DealStage::Lost) {
            $this->record($client, 'client.deal_lost', ['reason' => $reason->label()], $closeAt, $agent);
        } else {
            if ($outcome === DealStage::Negotiation && $listing->status === ListingStatus::Available && $this->chance(50)) {
                $listing->forceFill(['status' => ListingStatus::Reserved])->save();
                $this->record($listing, 'listing.status_changed', ['status' => ListingStatus::Reserved->value], $steps[DealStage::Negotiation->value], $agent);
            }

            $title = match ($outcome) {
                DealStage::New => 'اتصال أول بالعميل',
                DealStage::Contacted => 'اتصال متابعة',
                DealStage::Viewing => 'متابعة بعد المعاينة',
                default => 'متابعة رد المالك على العرض',
            };

            Reminder::create([
                'workspace_id' => $listing->workspace_id,
                'client_id' => $client->id,
                'user_id' => $agent->id,
                'title' => $title,
                'channel' => $this->any(ReminderChannel::cases()),
                'due_at' => today()->addDays(mt_rand(1, 14))->setTime(mt_rand(10, 19), $this->any([0, 30])),
            ]);
        }
    }

    /**
     * Take one quiet listing off the site, as when an owner changes their mind.
     *
     * @param  list<Listing>  $listings
     */
    private function withdrawOne(array $listings, CarbonInterface $to): void
    {
        foreach ($listings as $listing) {
            $quietSince = ($this->lastInquiryAt[$listing->id] ?? $listing->published_at)->copy()->addDays(5);

            if ($listing->status !== ListingStatus::Available || $quietSince->gte($to) || Deal::where('listing_id', $listing->id)->whereNull('closed_at')->exists()) {
                continue;
            }

            $at = $this->daytime($this->momentBetween($quietSince, $to), $quietSince, $to);
            $listing->forceFill(['status' => ListingStatus::Hidden])->save();
            $this->record($listing, 'listing.status_changed', ['status' => ListingStatus::Hidden->value], $at, $listing->agent);
            $this->offMarketAt[$listing->id] = $at;

            return;
        }
    }

    /**
     * A follow-up that was due at the given moment and done on time.
     */
    private function followUp(Client $client, string $title, Carbon $due): void
    {
        if ($due->hour < 7) {
            $due = $due->copy()->setTime(7, 30);
        }

        Reminder::create([
            'workspace_id' => $client->workspace_id,
            'client_id' => $client->id,
            'user_id' => $client->agent_id,
            'title' => $title,
            'channel' => $this->any(ReminderChannel::cases()),
            'due_at' => $due,
            'completed_at' => $due->copy()->addMinutes(mt_rand(0, 90))->min(now()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(Listing|Client $subject, string $type, array $properties, CarbonInterface $at, ?User $user = null): void
    {
        Activity::create([
            'workspace_id' => $subject->workspace_id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'user_id' => $user?->id,
            'type' => $type,
            'properties' => $properties,
            'occurred_at' => $at,
        ]);
    }

    private function price(ListingType $type, bool $sale): int
    {
        return match ($type) {
            ListingType::Villa => $sale ? $this->between(1600000, 4200000, 10000) : $this->between(90000, 220000, 5000),
            ListingType::Apartment => $sale ? $this->between(650000, 1400000, 10000) : $this->between(32000, 75000, 1000),
            ListingType::Floor => $sale ? $this->between(900000, 1800000, 10000) : $this->between(40000, 85000, 1000),
            ListingType::Land => $this->between(900000, 3500000, 50000),
            ListingType::Commercial => $sale ? $this->between(2500000, 7000000, 50000) : $this->between(80000, 350000, 5000),
        };
    }

    /**
     * @return list<string>
     */
    private function featuresFor(ListingType $type): array
    {
        return match ($type) {
            ListingType::Villa => ['مسبح', 'ملحق علوي', 'مجلس رجال', 'موقف خاص', 'تكييف مركزي', 'غرفة خادمة', 'حديقة', 'مطبخ راكب'],
            ListingType::Apartment => ['مصعد', 'موقف خاص', 'تكييف مركزي', 'مطبخ راكب'],
            ListingType::Floor => ['مدخل مستقل', 'موقف خاص', 'مجلس رجال', 'مطبخ راكب', 'تكييف مركزي'],
            ListingType::Commercial => ['موقف خاص', 'تكييف مركزي', 'مصعد'],
            ListingType::Land => [],
        };
    }

    /**
     * @return list<string>
     */
    private function captionsFor(ListingType $type): array
    {
        return match ($type) {
            ListingType::Villa, ListingType::Floor => ['facade', 'majlis', 'family living', 'kitchen', 'master bedroom', 'bathroom', 'roof', 'garden', 'street view', 'entrance'],
            ListingType::Apartment => ['building', 'living room', 'kitchen', 'master bedroom', 'bedroom', 'bathroom', 'balcony', 'street view'],
            ListingType::Land => ['plot', 'street view', 'aerial view', 'corner'],
            ListingType::Commercial => ['frontage', 'interior', 'parking', 'street view'],
        };
    }

    private function describe(ListingType $type, string $district, int $area, ?int $bedrooms): string
    {
        $near = in_array($type, [ListingType::Villa, ListingType::Apartment, ListingType::Land], true) ? 'قريبة' : 'قريب';
        $touch = $this->any([$near.' من المسجد والخدمات', 'على شارع 20 متر', $near.' من المدارس والحدائق', 'بواجهة شمالية', 'في شارع هادئ', $near.' من الطرق الرئيسية']);
        $rooms = $bedrooms === 2 ? 'غرفتان' : $bedrooms.' غرف';

        return match ($type) {
            ListingType::Villa => 'فيلا '.$this->any(['دوبلكس', 'حديثة البناء', 'بتصميم عصري', 'زاوية على شارعين']).' في حي '.$district.' بمساحة '.$area.' م²، '.$bedrooms.' غرف نوم ومجلس رجال وصالة عائلية ومطبخ، '.$touch.'.',
            ListingType::Apartment => 'شقة في الدور '.$this->any(['الأول', 'الثاني', 'الثالث']).' بعمارة حديثة في حي '.$district.'، '.$rooms.' وصالة ومطبخ، '.$touch.'.',
            ListingType::Floor => 'دور '.$this->any(['أرضي', 'علوي']).' بمدخل مستقل في حي '.$district.'، '.$rooms.' ومجلس وصالة، مناسب للعائلات و'.$touch.'.',
            ListingType::Land => 'أرض سكنية '.$this->any(['على شارعين', 'زاوية', 'مستوية وجاهزة للبناء']).' في حي '.$district.' بمساحة '.$area.' م²، '.$touch.'، والصك جاهز للإفراغ.',
            ListingType::Commercial => $this->any(['معرض تجاري', 'مكتب إداري', 'محل تجاري']).' على شارع تجاري في حي '.$district.' بمساحة '.$area.' م²، واجهة زجاجية ومواقف أمامية، '.$touch.'.',
        };
    }

    /**
     * @return list<string>
     */
    private function inquiryMessages(ListingPurpose $purpose): array
    {
        return [
            'هل السعر قابل للتفاوض؟',
            'متى يمكن المعاينة؟',
            'هل العقار ما زال متاحاً؟',
            'أرغب بمعرفة تفاصيل أكثر',
            ...($purpose === ListingPurpose::Rent
                ? ['هل يقبل الإيجار على دفعتين؟', 'هل الإيجار شامل الكهرباء والماء؟']
                : ['هل يمكن الشراء بتمويل بنكي؟', 'هل الصك إلكتروني؟']),
        ];
    }

    /**
     * @return list<string>
     */
    private function notesFor(Listing $listing): array
    {
        $common = ['أفضل وقت للتواصل بعد صلاة العصر.', 'توصية من عميل سابق.', 'التواصل بالواتساب فقط، الاتصالات بلا رد غالباً.'];

        if (in_array($listing->type, [ListingType::Land, ListingType::Commercial], true)) {
            return [...$common, 'الهدف استثماري، العائد أهم من التشطيب.', 'المقارنة جارية بين أكثر من عرض في نفس الحي.'];
        }

        return [...$common, 'الأولوية لموقع قريب من المدارس.', 'عائلة كبيرة، الحاجة لخمس غرف على الأقل.', ...($listing->purpose === ListingPurpose::Rent
            ? ['الأفضل عقد سنوي بدفعتين.', 'السكن مطلوب قبل بداية العام الدراسي.', 'الدور الأرضي أنسب بسبب كبار السن في العائلة.']
            : ['موافقة تمويل مبدئية من البنك.', 'الدفع كاش إذا كان السعر مناسباً.', 'المطلوب صورة الصك والمخطط قبل المعاينة.'])];
    }

    /**
     * @return array{workspace_id: int, listing_id: ?int, type: string, source: string, occurred_at: string}
     */
    private function event(int $workspaceId, ?int $listingId, SiteEventType $type, CarbonInterface $at): array
    {
        return [
            'workspace_id' => $workspaceId,
            'listing_id' => $listingId,
            'type' => $type->value,
            'source' => $this->pick(self::SOURCE_WEIGHTS)->value,
            'occurred_at' => $at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertEvents(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            SiteEvent::insert($chunk);
        }
    }

    /**
     * A moment between two others; a bias below one leans towards the later end.
     */
    private function momentBetween(CarbonInterface $from, CarbonInterface $to, float $bias = 1.0): Carbon
    {
        $span = max(1, (int) $from->diffInMinutes($to, true));

        return Carbon::parse($from)->addMinutes((int) ($span * (mt_rand(0, 10000) / 10000) ** $bias));
    }

    /**
     * Move a moment that falls at night into office hours, keeping it inside the range.
     */
    private function daytime(Carbon $moment, CarbonInterface $from, CarbonInterface $to): Carbon
    {
        if ($moment->hour < 8) {
            $moment->setTime(mt_rand(9, 13), mt_rand(0, 59));
        }

        return $moment->max($from)->min($to);
    }

    private function momentOn(CarbonInterface $day, CarbonInterface $until): Carbon
    {
        $moment = Carbon::parse($day)->setTime(mt_rand(8, 23), mt_rand(0, 59), mt_rand(0, 59));

        return $moment->gt($until) ? Carbon::parse($until)->subMinutes(mt_rand(1, 120)) : $moment;
    }

    private function between(int $min, int $max, int $step): int
    {
        return (int) (round(mt_rand($min, $max) / $step) * $step);
    }

    private function round(float $amount): int
    {
        return (int) (round($amount / ($amount >= 1000000 ? 10000 : 1000)) * ($amount >= 1000000 ? 10000 : 1000));
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $items
     * @return T
     */
    private function any(array $items): mixed
    {
        $items = array_values($items);

        return $items[mt_rand(0, count($items) - 1)];
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    private function some(array $items, int $count): array
    {
        for ($index = count($items) - 1; $index > 0; $index--) {
            $swap = mt_rand(0, $index);
            [$items[$index], $items[$swap]] = [$items[$swap], $items[$index]];
        }

        return array_slice($items, 0, min($count, count($items)));
    }

    /**
     * @template T
     *
     * @param  list<array{0: T, 1: int}>  $weights
     * @return T
     */
    private function pick(array $weights): mixed
    {
        $roll = mt_rand(1, array_sum(array_column($weights, 1)));

        foreach ($weights as [$value, $weight]) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $value;
            }
        }

        return $weights[0][0];
    }
}
