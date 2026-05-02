<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => 'Müdürlük '.fake()->unique()->city(),
            'manager_name' => fake()->name(),
            'manager_phone' => fake()->numerify('0### ### ## ##'),
            'foreman_name' => fake()->name(),
            'foreman_phone' => fake()->numerify('0### ### ## ##'),
            'staff_count' => fake()->numberBetween(5, 120),
            'vice_mayor_id' => null,
        ];
    }
}
