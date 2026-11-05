<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Enums\LeadSource;
use App\Support\Arabic;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['workspace_id', 'agent_id', 'name', 'mobile', 'type', 'source'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'source' => LeadSource::class,
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
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return HasMany<ClientRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(ClientRequest::class)->latest('id');
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * The earliest follow-up that is still open.
     *
     * @return HasOne<Reminder, $this>
     */
    public function nextReminder(): HasOne
    {
        return $this->hasOne(Reminder::class)->ofMany(['due_at' => 'min', 'id' => 'min'], function (Builder $query): void {
            $query->whereNull('completed_at');
        });
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
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    #[Scope]
    protected function visibleTo(Builder $query, Membership $membership): void
    {
        $query->where('workspace_id', $membership->workspace_id);

        if (! $membership->seesWholeWorkspace()) {
            $query->where('agent_id', $membership->user_id);
        }
    }

    public function initial(): string
    {
        return Arabic::initial($this->name);
    }
}
