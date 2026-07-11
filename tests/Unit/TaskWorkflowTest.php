<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskWorkflow;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    public function test_invalid_status_transition_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);
        TaskWorkflow::assertTransition(TaskStatus::Bekliyor, TaskStatus::Kapatildi);
    }

    public function test_requires_assignee_when_status_is_yonlendirildi(): void
    {
        $this->expectException(ValidationException::class);
        TaskWorkflow::assertRequiredFields([
            'status' => TaskStatus::Yonlendirildi->value,
            'assignee_id' => null,
        ]);
    }

    public function test_requires_arrival_photo_on_create(): void
    {
        $this->expectException(ValidationException::class);
        TaskWorkflow::assertRequiredFields([
            'status' => TaskStatus::Bekliyor->value,
            'title' => 'Deneme görev',
        ], null);
    }

    public function test_requires_evidence_for_closure_states(): void
    {
        $task = new Task();
        $task->status = TaskStatus::Sahada;

        $this->expectException(ValidationException::class);
        TaskWorkflow::assertRequiredFields([
            'status' => TaskStatus::Kapatildi->value,
            'resolved_at' => now(),
            'solution_note' => 'Çözüldü',
            'arrival_photos' => ['arrival-1.jpg'],
            'completion_photos' => [],
        ], $task);
    }

    public function test_allows_kapatildi_when_closure_fields_exist_on_task(): void
    {
        $task = new Task();
        $task->status = TaskStatus::Cozuldu;
        $task->resolved_at = now();
        $task->solution_note = 'Çözüldü';
        $task->arrival_photos = ['arrival-1.jpg'];
        $task->completion_photos = ['completion-1.jpg'];
        $task->assigned_at = now()->subHours(2);
        $task->dispatched_at = now()->subHour();

        TaskWorkflow::assertRequiredFields([
            'status' => TaskStatus::Kapatildi->value,
        ], $task);

        $this->assertTrue(true);
    }

    public function test_requires_arrival_photo_for_sahada_status(): void
    {
        $task = new Task();
        $task->status = TaskStatus::Yonlendirildi;

        $this->expectException(ValidationException::class);
        TaskWorkflow::assertRequiredFields([
            'status' => TaskStatus::Sahada->value,
            'dispatched_at' => now(),
            'arrival_photos' => [],
        ], $task);
    }
}
