<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Enums\MemberRole;
use App\Models\Activity;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\AdLicenseExpired;
use App\Notifications\FalLicenseExpiring;
use App\Notifications\WorkspaceLicenseExpiring;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Daily: listings whose ad license ran out are hidden from the public site
 * (they stop being "live"), and expiring FAL licenses are flagged ahead of time.
 */
#[Signature('tokhoom:check-licenses')]
#[Description('Flag expired ad licenses and warn about expiring FAL licenses')]
class CheckLicenses extends Command
{
    /**
     * Days before expiry on which warnings go out.
     *
     * @var list<int>
     */
    private const WARN_DAYS = [60, 30, 7];

    public function handle(): int
    {
        $expired = $this->expiredAdLicenses();
        $members = $this->expiringMemberLicenses();
        $workspaces = $this->expiringWorkspaceLicenses();

        $this->info("Expired ad licenses: {$expired}. Member FAL warnings: {$members}. Workspace license warnings: {$workspaces}.");

        return self::SUCCESS;
    }

    private function expiredAdLicenses(): int
    {
        $count = 0;

        Listing::whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])
            ->whereDate('ad_license_expires_on', '<', today())
            ->whereDoesntHave('activities', fn ($query) => $query
                ->where('type', 'listing.ad_license_expired')
                ->whereColumn('occurred_at', '>=', 'listings.ad_license_expires_on'))
            ->with(['agent', 'workspace'])
            ->chunkById(200, function ($listings) use (&$count): void {
                foreach ($listings as $listing) {
                    Activity::record($listing, 'listing.ad_license_expired');
                    Notification::send($this->recipients($listing->workspace, $listing->agent), new AdLicenseExpired($listing));
                    $count++;
                }
            });

        return $count;
    }

    private function expiringMemberLicenses(): int
    {
        $memberships = Membership::active()
            ->where(fn ($query) => $this->onWarningDays($query, 'fal_expires_on'))
            ->with(['user', 'workspace'])
            ->get();

        foreach ($memberships as $membership) {
            Notification::send($this->recipients($membership->workspace, $membership->user), new FalLicenseExpiring($membership));
        }

        return $memberships->count();
    }

    private function expiringWorkspaceLicenses(): int
    {
        $workspaces = Workspace::where(fn ($query) => $this->onWarningDays($query, 'license_expires_on'))->get();
        $admins = User::where('is_platform_admin', true)->get();

        foreach ($workspaces as $workspace) {
            Notification::send($admins, new WorkspaceLicenseExpiring($workspace));
        }

        return $workspaces->count();
    }

    /**
     * Match dates that fall exactly on one of the warning days.
     */
    private function onWarningDays(Builder $query, string $column): void
    {
        foreach (self::WARN_DAYS as $days) {
            $query->orWhereDate($column, today()->addDays($days));
        }
    }

    /**
     * The person concerned plus the workspace's owners and managers.
     *
     * @return Collection<int, User>
     */
    private function recipients(Workspace $workspace, ?User $person): Collection
    {
        return $workspace->memberships()->active()
            ->whereIn('role', [MemberRole::Owner, MemberRole::Manager])
            ->with('user')
            ->get()
            ->pluck('user')
            ->push($person)
            ->filter()
            ->unique('id')
            ->values();
    }
}
