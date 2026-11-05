<?php

namespace Database\Factories;

use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'agent_id' => User::factory(),
            'purpose' => ListingPurpose::Sale,
            'type' => ListingType::Apartment,
            'status' => ListingStatus::Available,
            'price' => fake()->numberBetween(500, 3000) * 1000,
            'area' => fake()->numberBetween(100, 600),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 6),
            'city' => 'الرياض',
            'district' => fake()->randomElement(['الملقا', 'النرجس', 'حطين', 'الياسمين', 'العارض']),
            'features' => [],
            'ad_license_number' => fake()->numerify('72000#####'),
            'ad_license_expires_on' => now()->addMonths(6)->toDateString(),
            'published_at' => now()->subDays(10),
            'price_changed_at' => now()->subDays(10),
        ];
    }

    public function forRent(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => ListingPurpose::Rent,
            'price' => fake()->numberBetween(30, 200) * 1000,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Draft,
            'ad_license_number' => null,
            'published_at' => null,
        ]);
    }

    public function adLicenseExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_license_expires_on' => now()->subDays(3)->toDateString(),
        ]);
    }
}
