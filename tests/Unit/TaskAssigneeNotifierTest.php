<?php

namespace Tests\Unit;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\PushToken;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskAssigneeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskAssigneeNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_foreman_when_task_is_assigned(): void
    {
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200)]);

        $foreman = User::factory()->create();
        $department = Department::factory()->create(['foreman_user_id' => $foreman->id]);
        PushToken::query()->create([
            'user_id' => $foreman->id,
            'token' => 'ExponentPushToken[foreman]',
        ]);

        $task = Task::factory()->create([
            'department_id' => $department->id,
            'assignee_id' => $foreman->id,
            'status' => TaskStatus::Yonlendirildi,
            'priority' => TaskPriority::Normal,
            'assigned_at' => now(),
        ]);

        $this->assertSame(1, $foreman->fresh()->unreadNotifications()->count());
        $this->assertStringContainsString($task->task_code, (string) $foreman->fresh()->unreadNotifications()->first()->data['body']);

        Http::assertSent(fn ($request) => $request->url() === 'https://exp.host/--/api/v2/push/send');
    }

    public function test_does_not_notify_when_status_is_bekliyor(): void
    {
        $foreman = User::factory()->create();
        $department = Department::factory()->create(['foreman_user_id' => $foreman->id]);

        $task = Task::factory()->create([
            'department_id' => $department->id,
            'assignee_id' => $foreman->id,
            'status' => TaskStatus::Bekliyor,
            'priority' => TaskPriority::Normal,
        ]);

        TaskAssigneeNotifier::notifyIfAssigned($task, assigneeChanged: true, statusChanged: true);

        $this->assertSame(0, $foreman->fresh()->unreadNotifications()->count());
    }
}
