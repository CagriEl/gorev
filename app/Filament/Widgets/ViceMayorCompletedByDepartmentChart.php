<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Department;
use App\Support\ReportScope;
use Filament\Widgets\ChartWidget;

class ViceMayorCompletedByDepartmentChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Müdürlüklere göre tamamlanan görevler';

    protected static ?string $maxHeight = '360px';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $departments = ReportScope::scopedDepartmentQuery()
            ->orderBy('name')
            ->get();

        if ($departments->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Tamamlanan',
                        'data' => [],
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#059669',
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => [],
            ];
        }

        $labels = $departments->pluck('name')->all();
        $data = $departments->map(function (Department $department): int {
            return (int) ReportScope::scopedTaskQuery()
                ->where('department_id', $department->id)
                ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                ->count();
        })->all();

        return [
            'datasets' => [
                [
                    'label' => 'Tamamlanan görevler',
                    'data' => $data,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
