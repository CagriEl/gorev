<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Filament\Resources\TaskResource;
use App\Models\Department;
use App\Models\Task;
use App\Support\TaskForemanAssignee;
use App\Support\TaskWorkflow;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterFill(): void
    {
        $user = auth()->user();
        if ($user?->role === UserRole::Manager && $user->department_id) {
            $foremanId = Department::query()
                ->whereKey((string) $user->department_id)
                ->value('foreman_user_id');
            $this->form->fill([
                'department_id' => $user->department_id,
                'assignee_id' => $foremanId !== null ? (string) $foremanId : null,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['task_code'] = Task::generateTaskCode();
        $data = TaskForemanAssignee::syncFromDepartment($data);
        TaskWorkflow::assertRequiredFields($data);

        return $this->resolveMinutes($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveMinutes(array $data): array
    {
        if (in_array(($data['status'] ?? null), [
            TaskStatus::Tamamlandi->value,
            TaskStatus::Cozuldu->value,
            TaskStatus::OnayBekliyor->value,
            TaskStatus::Kapatildi->value,
        ], true)
            && filled($data['assigned_at'] ?? null)
            && filled($data['resolved_at'] ?? null)) {
            $data['resolve_minutes'] = (int) Carbon::parse($data['assigned_at'])
                ->diffInMinutes(Carbon::parse($data['resolved_at']));
        }

        return $data;
    }
}
