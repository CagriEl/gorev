<?php

namespace App\Filament\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Support\ReportScope;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class MapOperationPage extends Page
{
    protected static ?string $slug = 'saha-haritasi';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.map-operation';

    protected static ?string $navigationLabel = 'Saha Haritası';

    protected static ?string $title = 'Saha Haritası';

    protected static ?int $navigationSort = 10;

    public function getHeading(): string
    {
        return '';
    }

    /** @var array<int, array<string, mixed>> */
    public array $mapTasks = [];

    public function mount(): void
    {
        $this->mapTasks = ReportScope::scopedTaskQuery()
            ->with([
                'department:id,name',
                'assignee:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'task_code' => $t->task_code,
                'title' => $t->title,
                'lat' => $t->latitude !== null ? (float) $t->latitude : null,
                'lng' => $t->longitude !== null ? (float) $t->longitude : null,
                'status' => $t->status->value,
                'statusLabel' => $t->status->getLabel(),
                'isOpen' => ! $t->isClosed(),
                'priority' => $t->priority->value,
                'priorityLabel' => $t->priority->getLabel(),
                'assignee_name' => $t->assignee?->name ?? '—',
                'department' => $t->department?->name ?? '—',
                'location' => $t->location,
                'description' => Str::limit((string) ($t->description ?? ''), 160),
                'url' => TaskResource::getUrl('view', ['record' => $t]),
            ])
            ->values()
            ->all();
    }
}
