<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\WorkspaceKind;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => WorkspaceKind::Office,
            'name' => 'مكتب '.fake()->unique()->lastName(),
            'slug' => Str::lower(fake()->unique()->lexify('office-??????')),
            'city' => 'الرياض',
            'cr_number' => fake()->numerify('1010######'),
            'fal_number' => fake()->numerify('1200######'),
            'license_expires_on' => now()->addYear()->toDateString(),
            'plan' => Plan::Office,
            'seats' => 5,
            'trial_ends_at' => now()->addDays(9),
        ];
    }

    /**
     * Approval status is not mass assignable, so states set it after making.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Workspace $workspace): void {
            $workspace->approval_status ??= ApprovalStatus::Approved;
        });
    }

    public function pending(): static
    {
        return $this->afterMaking(function (Workspace $workspace): void {
            $workspace->approval_status = ApprovalStatus::Pending;
        });
    }

    public function suspended(): static
    {
        return $this->afterMaking(function (Workspace $workspace): void {
            $workspace->suspended_at = now();
        });
    }

    public function independentAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => WorkspaceKind::Agent,
            'cr_number' => null,
            'plan' => Plan::Agent,
            'seats' => 1,
        ]);
    }

    /**
     * Add a member with the given role once the workspace exists.
     */
    public function withMember(User $user, MemberRole $role = MemberRole::Owner): static
    {
        return $this->afterCreating(function (Workspace $workspace) use ($user, $role): void {
            Membership::factory()->for($workspace)->for($user)->create(['role' => $role]);
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        });
    }
}
