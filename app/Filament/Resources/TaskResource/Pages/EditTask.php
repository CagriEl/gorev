<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
