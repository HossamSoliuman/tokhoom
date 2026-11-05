<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Models\Inquiry;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->name(),
            'mobile' => '05'.fake()->numerify('########'),
            'source' => LeadSource::Site,
        ];
    }
}
