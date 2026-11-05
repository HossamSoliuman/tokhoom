<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'role' => MemberRole::Agent,
            'fal_number' => fake()->numerify('1100######'),
            'fal_expires_on' => now()->addYear()->toDateString(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MemberRole::Owner]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MemberRole::Manager]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => ['deactivated_at' => now()]);
    }
}
