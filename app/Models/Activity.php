<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Support\Arabic;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A line in a listing's history or a client's activity timeline.
 */
#[Fillable(['workspace_id', 'subject_type', 'subject_id', 'user_id', 'type', 'properties', 'occurred_at'])]
class Activity extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Record an activity on a listing or client.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(Listing|Client $subject, string $type, array $properties = [], ?User $user = null): self
    {
        return $subject->activities()->create([
            'workspace_id' => $subject->workspace_id,
            'user_id' => $user?->id ?? auth()->id(),
            'type' => $type,
            'properties' => $properties,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function text(): string
    {
        $p = $this->properties ?? [];

        return match ($this->type) {
            'listing.published' => 'نُشر العقار بواسطة '.($p['by'] ?? $this->user?->name ?? ''),
            'listing.price_changed' => 'تم تعديل السعر',
            'listing.status_changed' => 'تغيّرت الحالة إلى «'.(ListingStatus::tryFrom($p['status'] ?? '')?->label() ?? '').'»',
            'listing.ad_license_expired' => 'انتهى ترخيص الإعلان وأُخفي العقار من الموقع',
            'listing.views_milestone' => 'تجاوز العقار '.Arabic::number((int) ($p['views'] ?? 0)).' مشاهدة',
            'client.inquiry' => 'استفسار من '.($p['source'] ?? 'الموقع').' عن '.($p['listing'] ?? 'عقار'),
            'client.request_added' => 'أُضيف طلب: '.($p['summary'] ?? ''),
            'client.listing_sent' => 'أُرسل له «'.($p['listing'] ?? '').'» عبر واتساب',
            'client.viewing' => 'معاينة '.($p['listing'] ?? 'العقار').' مع '.($p['agent'] ?? ''),
            'client.deal_stage' => 'انتقلت الصفقة إلى مرحلة «'.($p['stage'] ?? '').'»',
            'client.deal_won' => 'أُغلقت الصفقة بنجاح على '.($p['listing'] ?? 'العقار'),
            'client.deal_lost' => 'أُغلقت الصفقة كخاسرة: '.($p['reason'] ?? ''),
            default => $this->type,
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'listing.published' => 'ti-upload',
            'listing.price_changed' => 'ti-cash',
            'listing.status_changed' => 'ti-tag',
            'listing.ad_license_expired' => 'ti-alert-circle',
            'listing.views_milestone', 'client.viewing' => 'ti-eye',
            'client.inquiry' => 'ti-world',
            'client.request_added' => 'ti-clipboard-list',
            'client.listing_sent' => 'ti-brand-whatsapp',
            'client.deal_stage' => 'ti-arrows-exchange',
            'client.deal_won' => 'ti-trophy',
            'client.deal_lost' => 'ti-circle-x',
            default => 'ti-point',
        };
    }
}
