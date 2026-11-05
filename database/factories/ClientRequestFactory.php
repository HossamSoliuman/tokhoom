<?php

namespace Database\Factories;

use App\Enums\ListingPurpose;
use App\Enums\ListingType;
use App\Models\Client;
use App\Models\ClientRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientRequest>
 */
class ClientRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'purpose' => ListingPurpose::Rent,
            'types' => [ListingType::Apartment->value],
            'districts' => ['النرجس'],
            'budget_min' => 50000,
            'budget_max' => 70000,
        ];
    }
}
