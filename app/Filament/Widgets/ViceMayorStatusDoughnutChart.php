<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Support\ReportScope;
use Filament\Widgets\ChartWidget;

class ViceMayorStatusDoughnutChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Görev durum dağılımı';

    protected static ?string $description = 'Bekliyor, yönlendirildi, sahada ve tamamlandı';

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
        $tamamlandi = (int) ReportScope::scopedTaskQuery()
            ->where('status', TaskStatus::Tamamlandi)
            ->count();

        return [
            'datasets' => [
                [
                    'data' => [$bekliyor, $yonlendirildi, $sahada, $tamamlandi],
                    'backgroundColor' => [
                        '#64748b',
                        '#8b5cf6',
                        '#ef4444',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                TaskStatus::Bekliyor->getLabel(),
                TaskStatus::Yonlendirildi->getLabel(),
                TaskStatus::Sahada->getLabel(),
                TaskStatus::Tamamlandi->getLabel(),
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
