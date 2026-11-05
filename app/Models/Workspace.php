<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\DomainStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\RejectionReason;
use App\Enums\WorkspaceKind;
use App\Support\Arabic;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'kind', 'name', 'slug', 'city', 'cr_number', 'fal_number', 'license_expires_on',
    'brand_color', 'about', 'logo_path', 'cover_path',
    'whatsapp', 'phone', 'x_handle', 'instagram', 'snapchat', 'tiktok',
    'plan', 'seats', 'trial_ends_at', 'custom_domain',
])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => WorkspaceKind::class,
            'approval_status' => ApprovalStatus::class,
            'rejection_reason' => RejectionReason::class,
            'plan' => Plan::class,
            'domain_status' => DomainStatus::class,
            'license_expires_on' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'suspended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'domain_checked_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['role', 'deactivated_at'])
            ->wherePivotNull('deactivated_at');
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * @return HasMany<SiteEvent, $this>
     */
    public function siteEvents(): HasMany
    {
        return $this->hasMany(SiteEvent::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<CommissionTarget, $this>
     */
    public function commissionTargets(): HasMany
    {
        return $this->hasMany(CommissionTarget::class);
    }

    #[Scope]
    protected function pendingApproval(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Pending);
    }

    public function isOffice(): bool
    {
        return $this->kind === WorkspaceKind::Office;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved;
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at?->isFuture() ?? false;
    }

    public function trialDaysLeft(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return max(1, (int) today()->diffInDays($this->trial_ends_at->copy()->startOfDay(), true));
    }

    public function siteHost(): string
    {
        if ($this->custom_domain && $this->domain_status === DomainStatus::Active) {
            return $this->custom_domain;
        }

        return $this->slug.'.'.config('tokhoom.site_domain');
    }

    /**
     * The workspace whose public site lives on this host: {slug}.{site_domain}
     * or an active custom domain.
     */
    public static function forSiteHost(string $host): ?self
    {
        $host = strtolower($host);
        $suffix = '.'.config('tokhoom.site_domain');

        if (str_ends_with($host, $suffix)) {
            $slug = substr($host, 0, -strlen($suffix));

            return str_contains($slug, '.') ? null : self::where('slug', $slug)->first();
        }

        return self::where('custom_domain', $host)->where('domain_status', DomainStatus::Active)->first();
    }

    public function siteUrl(string $path = ''): string
    {
        return 'https://'.$this->siteHost().($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function owner(): ?User
    {
        return once(fn () => $this->members()->wherePivot('role', MemberRole::Owner)->orderBy('memberships.id')->first());
    }

    /**
     * WhatsApp number visitors reach: the one set in site settings, or the owner's mobile.
     */
    public function contactWhatsapp(): ?string
    {
        return $this->whatsapp ?: $this->owner()?->mobile;
    }

    /**
     * Number visitors call: the site's phone, or the WhatsApp number.
     */
    public function contactPhone(): ?string
    {
        return $this->phone ?: $this->contactWhatsapp();
    }

    /**
     * The letter shown in place of a logo.
     */
    public function initial(): string
    {
        return Arabic::initial(preg_replace('/^مكتب\s+/u', '', $this->name) ?? $this->name);
    }

    /**
     * Seats the subscription pays for: never fewer than the plan includes.
     */
    public function paidSeats(): int
    {
        return max($this->seats, $this->plan->includedSeats());
    }

    public function extraSeats(): int
    {
        return max(0, $this->paidSeats() - $this->plan->includedSeats());
    }

    public function monthlyTotal(): int
    {
        return $this->plan->monthlyPrice() + $this->extraSeats() * Plan::EXTRA_SEAT_PRICE;
    }

    public function nextChargeDate(): Carbon
    {
        if ($this->isOnTrial()) {
            return $this->trial_ends_at->copy();
        }

        return Carbon::now()->addMonthNoOverflow()->startOfMonth();
    }

    /**
     * Active members plus invitations that still hold a seat.
     */
    public function usedSeats(): int
    {
        return $this->memberships()->whereNull('deactivated_at')->count()
            + $this->invitations()->whereNull('accepted_at')->count();
    }

    public function hasFreeSeat(): bool
    {
        return $this->usedSeats() < $this->paidSeats();
    }
}
