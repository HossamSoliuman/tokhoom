<?php

namespace App\Models;

use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Support\Arabic;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[Fillable([
    'workspace_id', 'agent_id', 'purpose', 'type', 'status', 'price', 'area', 'bedrooms',
    'bathrooms', 'age', 'floors', 'city', 'district', 'latitude', 'longitude', 'features',
    'description', 'ad_license_number', 'ad_license_expires_on', 'brokerage_contract_number',
    'tour_url', 'video_url', 'published_at', 'price_changed_at',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const RECOMMENDED_PHOTOS = 8;

    public const MINIMUM_PHOTOS = 6;

    public const MAX_PHOTOS = 30;

    public const STALE_PRICE_DAYS = 60;

    protected static function booted(): void
    {
        static::creating(function (Listing $listing): void {
            $listing->short_code ??= self::uniqueShortCode();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => ListingPurpose::class,
            'type' => ListingType::class,
            'status' => ListingStatus::class,
            'features' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'ad_license_expires_on' => 'date',
            'published_at' => 'datetime',
            'price_changed_at' => 'datetime',
        ];
    }

    public static function uniqueShortCode(): string
    {
        do {
            $code = Str::random(6);
        } while (self::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return HasMany<ListingPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasOne<ListingPhoto, $this>
     */
    public function coverPhoto(): HasOne
    {
        return $this->hasOne(ListingPhoto::class)->ofMany(['position' => 'min', 'id' => 'min']);
    }

    /**
     * @return HasMany<SiteEvent, $this>
     */
    public function siteEvents(): HasMany
    {
        return $this->hasMany(SiteEvent::class);
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Agents only see their own listings; owners and managers see the whole workspace.
     */
    #[Scope]
    protected function visibleTo(Builder $query, Membership $membership): void
    {
        $query->where('workspace_id', $membership->workspace_id);

        if (! $membership->seesWholeWorkspace()) {
            $query->where('agent_id', $membership->user_id);
        }
    }

    /**
     * "فيلا للبيع — حي الملقا"
     */
    protected function title(): Attribute
    {
        return Attribute::get(function (): string {
            $title = $this->type->label().' '.$this->purpose->label();

            return $this->district ? $title.' — حي '.$this->district : $title;
        });
    }

    /**
     * "3,200,000 ريال" or "65,000 ريال سنوياً" for rentals.
     */
    public function priceLabel(): string
    {
        if ($this->price === null) {
            return '—';
        }

        return Arabic::money($this->price, $this->purpose === ListingPurpose::Rent ? 'سنوياً' : '');
    }

    public function isAdLicenseExpired(): bool
    {
        return $this->ad_license_expires_on !== null && $this->ad_license_expires_on->isBefore(today());
    }

    public function isPublished(): bool
    {
        return $this->status !== ListingStatus::Draft;
    }

    /**
     * Whether visitors can see the listing on the public site right now.
     */
    public function isLive(): bool
    {
        return $this->status->isPublic() && ! $this->isAdLicenseExpired();
    }

    public function daysSincePriceChange(): ?int
    {
        $since = $this->price_changed_at ?? $this->published_at;

        return $since ? (int) floor($since->diffInDays(now(), true)) : null;
    }

    public function publicUrl(): string
    {
        return $this->workspace->siteUrl('listings/'.$this->short_code);
    }

    public function shortUrl(): string
    {
        return 'https://'.$this->shortLink();
    }

    /**
     * The short link as shown to people: "tkh.sa/vi8KX2".
     */
    public function shortLink(): string
    {
        return config('tokhoom.short_domain').'/'.$this->short_code;
    }
}
