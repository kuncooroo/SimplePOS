<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => ActivityAction::UserCreated,
            'subject_type' => 'User',
            'subject_id' => User::factory(),
            'old_values' => null,
            'new_values' => [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'role' => 'CASHIER',
                'active' => true,
            ],
            'context' => null,
            'occurred_at' => now(),
        ];
    }
}
