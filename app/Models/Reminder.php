<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A follow-up an agent has to do with a client.
 */
#[Fillable(['workspace_id', 'client_id', 'user_id', 'title', 'channel', 'due_at', 'completed_at', 'notified_at'])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ReminderChannel::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    #[Scope]
    protected function visibleTo(Builder $query, Membership $membership): void
    {
        $query->where('workspace_id', $membership->workspace_id);

        if (! $membership->seesWholeWorkspace()) {
            $query->where('user_id', $membership->user_id);
        }
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Open and due today or earlier.
     */
    public function isDue(): bool
    {
        return ! $this->isDone() && $this->due_at->lte(now()->endOfDay());
    }
}
