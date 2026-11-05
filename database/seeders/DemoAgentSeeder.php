<?php

namespace Database\Seeders;

use App\Enums\ClientType;
use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\ReminderChannel;
use App\Enums\WorkspaceKind;
use App\Models\Activity;
use App\Models\CommissionTarget;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\AdLicenseExpired;
use App\Notifications\ListingMatchesRequest;
use App\Notifications\NewInquiry;
use App\Notifications\ReminderDue;
use App\Notifications\WorkspaceApproved;
use App\Support\Arabic;
use App\Support\Matcher;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\GeneratesDemoActivity;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * An independent agent, يوسف العنزي, five months into using Tokhoom: his own
 * site, listings, clients and deals, with today's follow-ups, fresh inquiries
 * and an expired ad license waiting for him.
 */
class DemoAgentSeeder extends Seeder
{
    use GeneratesDemoActivity;

    private User $agent;

    private Workspace $workspace;

    public function run(): void
    {
        if (User::where('email', 'yousef@example.com')->exists()) {
            $this->command?->warn('Demo agent already seeded, skipping.');

            return;
        }

        mt_srand(23);

        DB::transaction(function (): void {
            $this->seedWorkspace();

            $joined = $this->workspace->created_at->copy()->addDays(2);
            $listings = $this->generateListings($this->workspace, [$this->agent], 15, $joined, now()->subDays(3));

            $this->generateLeads($this->workspace, $listings, 55, $joined->copy()->addDays(3), now()->subHours(6));
            $this->generateListingTraffic($listings, 0.7);
            $this->generateVisits($this->workspace, $joined, now(), 12, 55);

            $this->seedDraft();
            $this->seedToday();
            $this->seedBilling();
        });
    }

    private function seedWorkspace(): void
    {
        $this->agent = User::create([
            'name' => 'يوسف العنزي',
            'email' => 'yousef@example.com',
            'mobile' => '0538812046',
            'gender' => 'male',
            'password' => 'password',
        ]);

        $joinedAt = now()->subDays(150)->setTime(20, 40);

        $this->workspace = new Workspace([
            'kind' => WorkspaceKind::Agent,
            'name' => 'يوسف العنزي',
            'slug' => 'yousef',
            'city' => 'الرياض',
            'fal_number' => '1100087342',
            'license_expires_on' => now()->addMonths(9)->toDateString(),
            'brand_color' => '#1F3A5F',
            'about' => 'وسيط عقاري مرخّص في الرياض، أساعد العائلات في إيجاد الشقة أو الفيلا المناسبة للشراء أو الإيجار، وأتابع معهم حتى توقيع العقد.',
            'whatsapp' => '0538812046',
            'x_handle' => '@yousef_aqar',
            'instagram' => '@yousef.aqar',
            'plan' => Plan::Agent,
            'seats' => 1,
            'trial_ends_at' => $joinedAt->copy()->addDays(14),
        ]);
        $this->workspace->forceFill([
            'approval_status' => 'approved',
            'approved_at' => $joinedAt->copy()->addHours(15),
            'card_brand' => 'Visa',
            'card_last_four' => '1934',
            'card_expiry' => '11/2027',
            'created_at' => $joinedAt,
        ])->save();

        Membership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->agent->id,
            'role' => MemberRole::Owner,
            'fal_number' => $this->workspace->fal_number,
            'fal_expires_on' => $this->workspace->license_expires_on,
        ]);

        $this->agent->forceFill(['current_workspace_id' => $this->workspace->id])->save();

        foreach ([0, 1, 2, 3] as $monthsAgo) {
            CommissionTarget::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->agent->id,
                'month' => now()->subMonthsNoOverflow($monthsAgo)->startOfMonth()->toDateString(),
                'commission_target' => 25000,
                'deals_target' => 2,
            ]);
        }

        $this->notify(new WorkspaceApproved($this->workspace), $this->workspace->approved_at, read: true);
    }

    /**
     * A land listing still being prepared: no ad license yet and too few photos.
     */
    private function seedDraft(): void
    {
        $listing = new Listing([
            'workspace_id' => $this->workspace->id,
            'agent_id' => $this->agent->id,
            'purpose' => ListingPurpose::Sale,
            'type' => ListingType::Land,
            'status' => ListingStatus::Draft,
            'price' => 1350000,
            'area' => 625,
            'city' => 'الرياض',
            'district' => 'الرمال',
            'features' => [],
            'description' => 'أرض سكنية على شارع 20 متر في حي الرمال، قريبة من الخدمات.',
        ]);
        $listing->created_at = now()->subDay()->setTime(21, 5);
        $listing->save();

        foreach (['land plot', 'street view'] as $position => $caption) {
            $listing->photos()->create(['path' => 'sample:'.$caption, 'position' => $position]);
        }
    }

    /**
     * What waits for him today: fresh inquiries, due follow-ups, a match to
     * send and an ad license that ran out.
     */
    private function seedToday(): void
    {
        $live = $this->workspace->listings()
            ->where('status', ListingStatus::Available)
            ->whereDate('ad_license_expires_on', '>=', today())
            ->latest('published_at')
            ->get();

        $fresh = [
            [$live[0], LeadSource::Site, now()->subMinutes(12), 'هل السعر قابل للتفاوض؟', true],
            [$live[1], LeadSource::Qr, now()->subMinutes(47), null, false],
        ];

        foreach ($fresh as [$listing, $source, $at, $message, $withDeal]) {
            $client = $this->newClient($this->workspace, $this->agent, $listing->purpose === ListingPurpose::Rent ? ClientType::Tenant : ClientType::Buyer, $source, $at, female: $this->chance(40));

            $inquiry = Inquiry::create([
                'workspace_id' => $this->workspace->id,
                'listing_id' => $listing->id,
                'client_id' => $client->id,
                'agent_id' => $this->agent->id,
                'name' => $client->name,
                'mobile' => $client->mobile,
                'message' => $message,
                'source' => $source,
                'created_at' => $at,
            ]);

            Activity::create([
                'workspace_id' => $this->workspace->id,
                'subject_type' => $client->getMorphClass(),
                'subject_id' => $client->id,
                'type' => 'client.inquiry',
                'properties' => ['source' => $source->label(), 'listing' => $listing->title],
                'occurred_at' => $at,
            ]);

            if ($withDeal) {
                Deal::create([
                    'workspace_id' => $this->workspace->id,
                    'client_id' => $client->id,
                    'listing_id' => $listing->id,
                    'agent_id' => $this->agent->id,
                    'stage' => DealStage::New,
                    'furthest_stage' => DealStage::New,
                    'value' => $listing->price,
                    'created_at' => $at,
                ]);
            }

            $this->notify(new NewInquiry($inquiry), $at);
        }

        $open = Deal::where('workspace_id', $this->workspace->id)
            ->whereIn('stage', [DealStage::Viewing, DealStage::Negotiation, DealStage::Contacted])
            ->with('client')
            ->latest()
            ->take(3)
            ->get();

        $due = [
            ['تأكيد موعد المعاينة', ReminderChannel::Whatsapp, today()->setTime(11, 0), true],
            ['متابعة رد المالك على العرض', ReminderChannel::Call, today()->setTime(14, 0), false],
            ['إرسال صور العقار', ReminderChannel::Whatsapp, today()->setTime(18, 30), false],
        ];

        foreach ($open as $index => $deal) {
            [$title, $channel, $at, $done] = $due[$index];

            $reminder = Reminder::create([
                'workspace_id' => $this->workspace->id,
                'client_id' => $deal->client_id,
                'user_id' => $this->agent->id,
                'title' => $title,
                'channel' => $channel,
                'due_at' => $at,
                'completed_at' => $done && $at->isPast() ? $at->copy()->addMinutes(8) : null,
            ]);

            if ($index === 1) {
                $this->notify(new ReminderDue($reminder), now()->subMinutes(30));
            }
        }

        $expired = $this->workspace->listings()
            ->where('status', ListingStatus::Available)
            ->where('published_at', '<', now()->subDays(45))
            ->oldest('published_at')
            ->first();

        if ($expired) {
            $expired->forceFill(['ad_license_expires_on' => today()->subDays(3)->toDateString()])->save();

            Activity::create([
                'workspace_id' => $this->workspace->id,
                'subject_type' => $expired->getMorphClass(),
                'subject_id' => $expired->id,
                'type' => 'listing.ad_license_expired',
                'properties' => [],
                'occurred_at' => today()->subDays(2)->setTime(6, 0),
            ]);

            $this->notify(new AdLicenseExpired($expired), today()->subDays(2)->setTime(6, 0), read: true);
        }

        $match = (new Matcher)->forMember($this->workspace->memberships()->first(), 1)->first();

        if ($match) {
            $this->notify(new ListingMatchesRequest($match['listing'], $match['client']), now()->subHours(2));
        }

        Inquiry::where('workspace_id', $this->workspace->id)
            ->whereNotNull('responded_at')
            ->where('created_at', '>=', now()->subDays(4))
            ->latest()
            ->take(3)
            ->get()
            ->each(fn (Inquiry $inquiry) => $this->notify(new NewInquiry($inquiry), $inquiry->created_at, read: true));
    }

    private function seedBilling(): void
    {
        foreach ([1, 2, 3, 4] as $monthsAgo) {
            $issued = now()->subMonthsNoOverflow($monthsAgo)->startOfMonth();

            if ($issued->lt($this->workspace->trial_ends_at)) {
                continue;
            }

            $this->workspace->invoices()->create([
                'number' => 'TKH-'.$issued->format('Ym').'-0218',
                'description' => 'اشتراك خطة الوسيط — '.Arabic::month($issued->month).' '.$issued->year,
                'amount' => Plan::Agent->monthlyPrice(),
                'issued_on' => $issued->toDateString(),
                'paid_at' => $issued->copy()->addHours(3),
            ]);
        }
    }

    /**
     * Store a database notification with a chosen timestamp.
     */
    private function notify(Notification $notification, CarbonInterface $at, bool $read = false): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'notifiable_type' => $this->agent->getMorphClass(),
            'notifiable_id' => $this->agent->id,
            'data' => $notification->toArray($this->agent),
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
