<?php

namespace App\Models;

use App\Enums\LeadSource;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A visitor's message from a public site, linked to the client it created.
 */
#[Fillable(['workspace_id', 'listing_id', 'client_id', 'agent_id', 'name', 'mobile', 'message', 'source', 'responded_at', 'created_at'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * Replies within this many minutes count as on time.
     */
    public const RESPONSE_TARGET_MINUTES = 15;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    #[Scope]
    protected function awaitingReply(Builder $query): void
    {
        $query->whereNull('responded_at');
    }

    #[Scope]
    protected function visibleTo(Builder $query, Membership $membership): void
    {
        $query->where('workspace_id', $membership->workspace_id);

        if (! $membership->seesWholeWorkspace()) {
            $query->where('agent_id', $membership->user_id);
        }
    }

    /**
     * Minutes the visitor has waited, or waited before the first reply.
     */
    public function waitMinutes(): int
    {
        return (int) floor($this->created_at->diffInMinutes($this->responded_at ?? now(), true));
    }
}
