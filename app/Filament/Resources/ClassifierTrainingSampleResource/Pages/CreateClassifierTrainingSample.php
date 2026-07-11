<?php

namespace App\Filament\Resources\ClassifierTrainingSampleResource\Pages;

use App\Filament\Resources\ClassifierTrainingSampleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassifierTrainingSample extends CreateRecord
{
    protected static string $resource = ClassifierTrainingSampleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = ClassifierTrainingSampleResource::mutateBeforeCreate($data);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
