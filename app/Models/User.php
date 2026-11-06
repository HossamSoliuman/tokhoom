<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ApprovalStatus;
use App\Notifications\ResetPasswordLink;
use App\Support\Arabic;
use App\Support\Phone;
use App\Support\Totp;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'mobile', 'gender', 'password', 'current_workspace_id'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
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
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'memberships')
            ->withPivot(['role', 'deactivated_at'])
            ->wherePivotNull('deactivated_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * The active membership in the workspace the user is working in.
     */
    public function currentMembership(): ?Membership
    {
        if (! $this->current_workspace_id) {
            return null;
        }

        return $this->memberships
            ->where('workspace_id', $this->current_workspace_id)
            ->whereNull('deactivated_at')
            ->first();
    }

    /**
     * Point the user at the given workspace, or at their first remaining one.
     */
    public function switchWorkspace(?Workspace $workspace = null): void
    {
        $workspace ??= $this->workspaces()->orderBy('memberships.id')->first();

        $this->forceFill(['current_workspace_id' => $workspace?->id])->save();
        $this->unsetRelation('currentWorkspace');
    }

    public function initials(): string
    {
        return Arabic::initial($this->name);
    }

    public function isPendingApproval(): bool
    {
        return $this->currentWorkspace?->approval_status === ApprovalStatus::Pending;
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    /**
     * Whether signing in also needs a code from an authenticator app.
     */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Check an authenticator code, or use up one of the recovery codes. An
     * accepted app code cannot be used again while it is still valid.
     */
    public function passesTwoFactor(string $code): bool
    {
        $appCode = Phone::digits($code);

        if (strlen($appCode) === 6 && Totp::verify((string) $this->two_factor_secret, $appCode)) {
            return Cache::add('two-factor-used:'.$this->id.':'.$appCode, true, now()->addSeconds(90));
        }

        foreach ($this->two_factor_recovery_codes ?? [] as $index => $hash) {
            if (Hash::check(Str::lower(trim($code)), $hash)) {
                $codes = $this->two_factor_recovery_codes;
                unset($codes[$index]);
                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * The number text messages go to.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->mobile;
    }
}
