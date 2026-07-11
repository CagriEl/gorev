<?php

namespace App\Filament\Resources\ClassifierTrainingSampleResource\Pages;

use App\Filament\Resources\ClassifierTrainingSampleResource;
use Filament\Resources\Pages\EditRecord;

class EditClassifierTrainingSample extends EditRecord
{
    protected static string $resource = ClassifierTrainingSampleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ClassifierTrainingSampleResource::mutateBeforeSave($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
