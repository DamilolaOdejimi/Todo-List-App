<?php

namespace Database\Factories;

use App\Enums\PriorityLevelStatus;
use App\Enums\TaskStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->name(),
            'due_date' => now()->addDay(),
            'priority_level' => (PriorityLevelStatus::cases()[rand(0, 2)])->value,
            'status' => (TaskStatuses::cases()[rand(0, 2)])->value,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
