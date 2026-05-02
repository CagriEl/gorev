<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\ReportScope;
use Filament\Widgets\ChartWidget;

class SlaSuccessLineChart extends ChartWidget
{
    protected static ?string $heading = 'SLA başarı oranı';

    protected static ?string $description = 'Tamamlanan görevlerde hedef süre (120 dk) altında çözüm yüzdesi';

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $rates = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $tasks = ReportScope::scopedTaskQuery()
                ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                ->whereYear('resolved_at', $month->year)
                ->whereMonth('resolved_at', $month->month)
                ->get();

            $total = $tasks->count();
            if ($total === 0) {
                $rates[] = 0;

                continue;
            }

            $ok = $tasks->filter(fn (Task $t): bool => (bool) $t->slaWithinTarget(Task::SLA_TARGET_MINUTES))->count();
            $rates[] = round(100 * $ok / $total, 1);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Başarı oranı (%)',
                    'data' => $rates,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
