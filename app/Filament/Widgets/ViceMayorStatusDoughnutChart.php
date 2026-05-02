<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Support\ReportScope;
use Filament\Widgets\ChartWidget;

class ViceMayorStatusDoughnutChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Görev durum dağılımı';

    protected static ?string $description = 'Bekliyor, yönlendirildi, sahada, onay bekleyen ve kapatılan';

    protected static ?string $maxHeight = '360px';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $bekliyor = (int) ReportScope::scopedTaskQuery()
            ->where('status', TaskStatus::Bekliyor)
            ->count();
        $yonlendirildi = (int) ReportScope::scopedTaskQuery()
            ->where('status', TaskStatus::Yonlendirildi)
            ->count();
        $sahada = (int) ReportScope::scopedTaskQuery()
            ->where('status', TaskStatus::Sahada)
            ->count();
        $onayBekliyor = (int) ReportScope::scopedTaskQuery()
            ->where('status', TaskStatus::OnayBekliyor)
            ->count();
        $kapatildi = (int) ReportScope::scopedTaskQuery()
            ->whereIn('status', [TaskStatus::Kapatildi, TaskStatus::Tamamlandi])
            ->count();

        return [
            'datasets' => [
                [
                    'data' => [$bekliyor, $yonlendirildi, $sahada, $onayBekliyor, $kapatildi],
                    'backgroundColor' => [
                        '#64748b',
                        '#8b5cf6',
                        '#ef4444',
                        '#f59e0b',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                TaskStatus::Bekliyor->getLabel(),
                TaskStatus::Yonlendirildi->getLabel(),
                TaskStatus::Sahada->getLabel(),
                TaskStatus::OnayBekliyor->getLabel(),
                TaskStatus::Kapatildi->getLabel(),
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
