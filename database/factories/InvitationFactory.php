<?php

namespace Database\Factories;

use App\Enums\InvitationChannel;
use App\Enums\MemberRole;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'channel' => InvitationChannel::Email,
            'contact' => fake()->unique()->safeEmail(),
            'role' => MemberRole::Agent,
            'sent_at' => now(),
        ];
    }
}
