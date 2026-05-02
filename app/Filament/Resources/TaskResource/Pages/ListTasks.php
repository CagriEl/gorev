<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tüm görevler'),
            'resolved' => Tab::make('Çözümlenen görevler')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [
                    TaskStatus::Cozuldu,
                    TaskStatus::OnayBekliyor,
                    TaskStatus::Kapatildi,
                    TaskStatus::Tamamlandi,
                ])),
        ];
    }
}
