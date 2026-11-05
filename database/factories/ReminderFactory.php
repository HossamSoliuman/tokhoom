<?php

namespace Database\Factories;

use App\Enums\ReminderChannel;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'title' => 'اتصال متابعة',
            'channel' => ReminderChannel::Call,
            'due_at' => now()->addHours(2),
        ];
    }
}
