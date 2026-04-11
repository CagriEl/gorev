<?php

namespace App\Filament\Pages;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use Filament\Pages\Page;

class MapOperationPage extends Page
{
    protected static ?string $slug = 'saha-operasyonu';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.map-operation';

    protected static ?string $navigationLabel = 'Saha Operasyonu';

    protected static ?string $title = 'Saha Operasyonu';

    protected static ?int $navigationSort = 10;

    /** @var array<int, array<string, mixed>> */
    public array $mapTasks = [];

    public function mount(): void
    {
        $query = Task::query()
            ->where('status', '!=', TaskStatus::Tamamlandi)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $user = auth()->user();
        if ($user?->role === UserRole::ViceMayor) {
            $query->whereIn('department_id', $user->managedDepartments()->pluck('id'));
        }

        $this->mapTasks = $query
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'lat' => (float) $t->latitude,
                'lng' => (float) $t->longitude,
                'status' => $t->status->value,
                'statusLabel' => $t->status->getLabel(),
            ])
            ->values()
            ->all();
    }
}
