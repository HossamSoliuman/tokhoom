<?php

namespace App\Models;

use App\Enums\DealStage;
use App\Enums\ListingPurpose;
use App\Enums\LostReason;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'client_id', 'listing_id', 'agent_id', 'stage', 'furthest_stage',
    'value', 'final_price', 'commission', 'lost_reason', 'closed_at', 'created_at',
])]
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'furthest_stage' => DealStage::class,
            'lost_reason' => LostReason::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    #[Scope]
    protected function visibleTo(Builder $query, Membership $membership): void
    {
        $query->where('workspace_id', $membership->workspace_id);

        if (! $membership->seesWholeWorkspace()) {
            $query->where('agent_id', $membership->user_id);
        }
    }

    public function isRental(): bool
    {
        return $this->listing?->purpose === ListingPurpose::Rent;
    }

    /**
     * Commission suggested when closing: 5% of a year's rent or 2.5% of a sale price.
     */
    public function suggestedCommission(int $price): int
    {
        return (int) round($price * ($this->isRental() ? 0.05 : 0.025));
    }

    /**
     * Move to another stage, remembering the furthest stage reached for the funnel.
     */
    public function moveTo(DealStage $stage): void
    {
        $this->stage = $stage;

        if ($stage->rank() > $this->furthest_stage->rank()) {
            $this->furthest_stage = $stage;
        }

        $this->closed_at = $stage->isClosed() ? now() : null;
    }
}
