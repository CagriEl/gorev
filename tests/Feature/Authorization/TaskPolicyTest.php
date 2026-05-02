<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_vice_mayor_cannot_view_task_outside_managed_departments(): void
    {
        $managed = Department::factory()->create();
        $other = Department::factory()->create();
        $viceMayor = User::factory()->create(['role' => UserRole::ViceMayor]);
        $managed->update(['vice_mayor_id' => $viceMayor->id]);
        $taskInOtherDepartment = Task::factory()->create(['department_id' => $other->id]);

        $this->assertFalse($viceMayor->can('view', $taskInOtherDepartment));
    }

    public function test_manager_can_update_task_in_own_department(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'department_id' => $department->id,
        ]);
        $task = Task::factory()->create(['department_id' => $department->id]);

        $this->assertTrue($manager->can('update', $task));
    }

    public function test_staff_cannot_update_unassigned_task(): void
    {
        $department = Department::factory()->create();
        $staff = User::factory()->create([
            'role' => UserRole::Staff,
            'department_id' => $department->id,
        ]);
        $task = Task::factory()->create([
            'department_id' => $department->id,
            'assignee_id' => User::factory()->create(['role' => UserRole::Staff])->id,
        ]);

        $this->assertFalse($staff->can('update', $task));
    }
}
