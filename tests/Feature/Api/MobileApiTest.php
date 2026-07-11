<?php

namespace Tests\Feature\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_reports_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Staff]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/reports/dashboard')->assertForbidden();
    }

    public function test_manager_gets_scoped_dashboard(): void
    {
        $department = Department::factory()->create(['staff_count' => 10]);
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'department_id' => $department->id,
        ]);
        Task::factory()->create([
            'department_id' => $department->id,
            'status' => TaskStatus::Sahada,
            'priority' => TaskPriority::Normal,
        ]);
        Task::factory()->create([
            'department_id' => $department->id,
            'status' => TaskStatus::Kapatildi,
            'resolved_at' => now(),
        ]);

        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/v1/reports/dashboard');
        $response->assertOk()
            ->assertJsonPath('scope', 'manager')
            ->assertJsonPath('summary.open_tasks', 1)
            ->assertJsonPath('summary.in_field', 1)
            ->assertJsonPath('summary.resolved_today', 1);
    }

    public function test_staff_can_upload_task_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => UserRole::Staff]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('arrival.jpg');

        $response = $this->post('/api/v1/uploads/task-photo', [
            'photo' => $file,
            'type' => 'arrival',
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $path = $response->json('path');
        $this->assertStringStartsWith('task-arrival-photos/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_vice_mayor_lists_tasks_for_managed_departments_only(): void
    {
        $viceMayor = User::factory()->create(['role' => UserRole::ViceMayor]);
        $managed = Department::factory()->create(['vice_mayor_id' => $viceMayor->id]);
        $other = Department::factory()->create();

        $visible = Task::factory()->create([
            'department_id' => $managed->id,
            'status' => TaskStatus::Yonlendirildi,
            'priority' => TaskPriority::Normal,
        ]);
        Task::factory()->create([
            'department_id' => $other->id,
            'status' => TaskStatus::Yonlendirildi,
            'priority' => TaskPriority::Normal,
        ]);

        Sanctum::actingAs($viceMayor);

        $response = $this->getJson('/api/v1/tasks');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($visible->id, $ids);
        $this->assertCount(1, $ids);
    }
}
