<?php

namespace App\Filament\Resources\ClassifierTrainingSampleResource\Pages;

use App\Filament\Resources\ClassifierTrainingSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassifierTrainingSamples extends ListRecords
{
    protected static string $resource = ClassifierTrainingSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
