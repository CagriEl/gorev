<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['task_code'] = Task::generateTaskCode();

        return $this->resolveMinutes($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveMinutes(array $data): array
    {
        if (($data['status'] ?? null) === TaskStatus::Tamamlandi->value
            && filled($data['assigned_at'] ?? null)
            && filled($data['resolved_at'] ?? null)) {
            $data['resolve_minutes'] = (int) Carbon::parse($data['assigned_at'])
                ->diffInMinutes(Carbon::parse($data['resolved_at']));
        }

        return $data;
    }
}
