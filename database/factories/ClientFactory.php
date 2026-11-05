<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Enums\LeadSource;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'agent_id' => User::factory(),
            'name' => fake()->name(),
            'mobile' => '05'.fake()->unique()->numerify('########'),
            'type' => ClientType::Buyer,
            'source' => LeadSource::Direct,
        ];
    }
}
