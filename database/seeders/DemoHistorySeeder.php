<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\SiteEventType;
use App\Models\CommissionTarget;
use App\Models\Workspace;
use Database\Seeders\Concerns\GeneratesDemoActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The months before the demo office's designed snapshot: a fuller catalogue,
 * older clients and closed deals, earlier targets and traffic back to the day
 * it joined. The last few weeks stay as designed. Also fills the Jeddah branch.
 */
class DemoHistorySeeder extends Seeder
{
    use GeneratesDemoActivity;

    public function run(): void
    {
        $office = Workspace::where('slug', 'alrashid')->first();
        $branch = Workspace::where('slug', 'alrashid-jeddah')->first();

        if (! $office || ! $branch) {
            $this->command?->warn('Demo office not found, run DemoOfficeSeeder first.');

            return;
        }

        if ($office->siteEvents()->where('occurred_at', '<', today()->subDays(40))->exists()) {
            $this->command?->warn('Demo history already seeded, skipping.');

            return;
        }

        mt_srand(11);

        DB::transaction(function () use ($office, $branch): void {
            $this->seedOfficeHistory($office);
            $this->seedBranch($branch);
        });
    }

    private function seedOfficeHistory(Workspace $office): void
    {
        $designed = $office->listings()->whereNotNull('published_at')->get();
        $agents = $office->members()->orderBy('memberships.id')->get()->all();
        $joined = $office->created_at->copy()->addDays(3);
        $snapshot = now()->subDays(26);
        $monthAgo = today()->subDays(31)->endOfDay();

        $listings = $this->generateListings($office, $agents, 16, $joined, $snapshot->copy()->subDays(10));
        $this->generateLeads($office, $listings, 44, $joined->copy()->addDays(4), $snapshot);
        $this->generateListingTraffic($listings);
        $this->generateVisits($office, $joined, $monthAgo, 70, 210);

        foreach ($designed as $listing) {
            if ($listing->published_at->gte($monthAgo)) {
                continue;
            }

            $recentViews = $listing->siteEvents()->where('type', SiteEventType::ListingView)->count();
            $closed = in_array($listing->status, [ListingStatus::Sold, ListingStatus::Rented], true);

            $this->generateListingEvents($listing, $listing->published_at, $monthAgo, $recentViews > 0 ? $recentViews / 30 * 0.85 : ($closed ? 9 : 0));
        }

        $targets = CommissionTarget::where('workspace_id', $office->id)->whereDate('month', now()->startOfMonth())->get();

        foreach ([1, 2, 3] as $monthsAgo) {
            foreach ($targets as $target) {
                CommissionTarget::create([
                    'workspace_id' => $office->id,
                    'user_id' => $target->user_id,
                    'month' => now()->subMonthsNoOverflow($monthsAgo)->startOfMonth()->toDateString(),
                    'commission_target' => $target->commission_target,
                    'deals_target' => $target->deals_target,
                ]);
            }
        }
    }

    private function seedBranch(Workspace $branch): void
    {
        if ($branch->approved_at && $branch->created_at->gt($branch->approved_at)) {
            $branch->forceFill(['created_at' => $branch->approved_at->copy()->subDay()])->save();
        }

        $existing = $branch->listings()->whereNotNull('published_at')->get()->all();
        $listings = $this->generateListings($branch, $branch->members()->get()->all(), 6, now()->subDays(60), now()->subDays(4));

        $this->generateLeads($branch, [...$listings, ...$existing], 12, now()->subDays(55), now()->subHours(20));
        $this->generateListingTraffic([...$listings, ...$existing], 0.6);
        $this->generateVisits($branch, today()->subDays(60), now(), 8, 30);
    }
}
