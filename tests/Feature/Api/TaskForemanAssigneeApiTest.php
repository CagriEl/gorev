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

class TaskForemanAssigneeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_sets_assignee_to_department_foreman_when_omitted(): void
    {
        $department = Department::factory()->create();
        $foreman = User::factory()->create([
            'role' => UserRole::Staff,
            'department_id' => $department->id,
        ]);
        $department->update(['foreman_user_id' => $foreman->id]);

        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'department_id' => $department->id,
        ]);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Şef otomatik',
            'department_id' => $department->id,
            'priority' => TaskPriority::Normal->value,
            'status' => TaskStatus::Bekliyor->value,
            'arrival_photos' => ['task-arrival-photos/a.jpg'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('tasks', [
            'title' => 'Şef otomatik',
            'assignee_id' => $foreman->id,
        ]);
    }

    public function test_store_rejects_assignee_not_matching_foreman(): void
    {
        $department = Department::factory()->create();
        $foreman = User::factory()->create([
            'role' => UserRole::Staff,
            'department_id' => $department->id,
        ]);
        $otherStaff = User::factory()->create([
            'role' => UserRole::Staff,
            'department_id' => $department->id,
        ]);
        $department->update(['foreman_user_id' => $foreman->id]);

        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'department_id' => $department->id,
        ]);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Yanlış atanan',
            'department_id' => $department->id,
            'assignee_id' => $otherStaff->id,
            'priority' => TaskPriority::Normal->value,
            'status' => TaskStatus::Bekliyor->value,
            'arrival_photos' => ['task-arrival-photos/b.jpg'],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['assignee_id']);
    }
}
