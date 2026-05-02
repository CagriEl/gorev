<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use App\Support\TaskForemanAssignee;
use App\Support\TaskWorkflow;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return TaskForemanAssignee::syncFromDepartment($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = TaskForemanAssignee::syncFromDepartment($data);

        $oldStatus = $this->record->status;
        $newStatus = TaskStatus::tryFrom((string) ($data['status'] ?? ''));
        if ($newStatus) {
            TaskWorkflow::assertTransition($oldStatus, $newStatus);
        }
        TaskWorkflow::assertRequiredFields($data, $this->record);

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
