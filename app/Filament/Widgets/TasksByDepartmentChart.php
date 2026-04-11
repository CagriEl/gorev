<?php

namespace App\Filament\Widgets;

use App\Support\ReportScope;
use Filament\Widgets\ChartWidget;

class TasksByDepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'Bölümlere göre görev dağılımı';

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = ReportScope::scopedDepartmentQuery()
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Görev sayısı',
                    'data' => $rows->pluck('tasks_count')->map(fn ($c) => (int) $c)->all(),
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }
}
