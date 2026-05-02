<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ApprovalRequest;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(false),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if (($data['role'] ?? null) !== $this->record->role->value && ! $user?->isAdmin()) {
            ApprovalRequest::query()->create([
                'type' => 'user_role_change',
                'status' => ApprovalRequest::STATUS_PENDING,
                'approvable_type' => $this->record->getMorphClass(),
                'approvable_id' => $this->record->id,
                'requested_by' => $user->id,
                'reason' => 'Rol değişikliği ikinci onay gerektirir.',
                'payload' => [
                    'from_role' => $this->record->role->value,
                    'to_role' => $data['role'],
                ],
            ]);
            $data['role'] = $this->record->role->value;
            Notification::make()
                ->title('Rol değişikliği onaya gönderildi')
                ->warning()
                ->send();
        }

        return $data;
    }
}
