<?php

namespace Tests\Feature\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskStoreWithoutCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_task_without_latitude_or_longitude_succeeds(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'department_id' => $department->id,
        ]);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Adresle görev',
            'department_id' => $department->id,
            'location' => 'Kırklareli merkez',
            'priority' => TaskPriority::Normal->value,
            'status' => TaskStatus::Bekliyor->value,
            'arrival_photos' => ['task-arrival-photos/demo.jpg'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('tasks', [
            'title' => 'Adresle görev',
            'location' => 'Kırklareli merkez',
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}
