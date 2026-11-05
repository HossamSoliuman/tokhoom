<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Client;
use App\Models\Deal;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => Client::factory(),
            'agent_id' => User::factory(),
            'stage' => DealStage::New,
            'furthest_stage' => DealStage::New,
            'value' => 1150000,
        ];
    }

    public function inStage(DealStage $stage): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => $stage,
            'furthest_stage' => $stage === DealStage::Lost ? DealStage::Contacted : $stage,
            'closed_at' => $stage->isClosed() ? now() : null,
        ]);
    }
}
