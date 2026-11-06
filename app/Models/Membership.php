<?php

namespace App\Models;

use App\Enums\MemberRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'user_id', 'role', 'fal_number', 'fal_expires_on', 'deactivated_at'])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'fal_expires_on' => 'date',
            'deactivated_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    public function roleLabel(): string
    {
        return $this->role->label($this->user->gender, $this->workspace->kind);
    }

    public function seesWholeWorkspace(): bool
    {
        return $this->role->seesWholeWorkspace();
    }

    /**
     * The public page this member shares: an office agent's own page, or the
     * whole site for owners, managers and independent agents.
     */
    public function publicUrl(): string
    {
        return $this->role === MemberRole::Agent && $this->workspace->isOffice()
            ? $this->workspace->siteUrl('/agents/'.$this->user_id)
            : $this->workspace->siteUrl();
    }

    /**
     * Whether the member's FAL license runs out within the given number of days.
     */
    public function falExpiresWithin(int $days): bool
    {
        return $this->fal_expires_on !== null && $this->fal_expires_on->lte(now()->addDays($days));
    }
}
