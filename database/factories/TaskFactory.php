<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'task_code' => 'KRL-'.fake()->unique()->numberBetween(1000, 9000),
            'title' => fake()->sentence(4),
            'department_id' => Department::factory(),
            'assignee_id' => User::factory(),
            'location' => fake()->streetAddress(),
            'latitude' => 41.7333,
            'longitude' => 27.2167,
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::Bekliyor,
            'description' => fake()->sentence(),
            'assigned_at' => now()->subHour(),
            'dispatched_at' => null,
            'resolved_at' => null,
            'resolve_minutes' => null,
        ];
    }
}
