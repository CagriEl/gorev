<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Models\ApprovalRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(false),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if (($data['vice_mayor_id'] ?? null) !== $this->record->vice_mayor_id && ! $user?->isAdmin()) {
            ApprovalRequest::query()->create([
                'type' => 'department_vice_mayor_change',
                'status' => ApprovalRequest::STATUS_PENDING,
                'approvable_type' => $this->record->getMorphClass(),
                'approvable_id' => $this->record->id,
                'requested_by' => $user->id,
                'reason' => 'Müdürlük sorumluluk değişimi ikinci onay gerektirir.',
                'payload' => [
                    'from_vice_mayor_id' => $this->record->vice_mayor_id,
                    'to_vice_mayor_id' => $data['vice_mayor_id'],
                ],
            ]);
            $data['vice_mayor_id'] = $this->record->vice_mayor_id;
            Notification::make()
                ->title('Sorumluluk değişikliği onaya gönderildi')
                ->warning()
                ->send();
        }

        return $data;
    }
}
