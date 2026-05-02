<?php

namespace App\Filament\Widgets;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\ReportScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BelStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Açık görevler',
                (string) ReportScope::scopedTaskQuery()
                    ->whereNotIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                    ->count(),
            )
                ->description('Tamamlanmamış tüm görevler')
                ->color('primary'),
            Stat::make(
                'Acil müdahale',
                (string) ReportScope::scopedTaskQuery()
                    ->where('priority', TaskPriority::Kritik)
                    ->whereNotIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                    ->count(),
            )
                ->description('Kritik öncelikli açık görevler')
                ->color('danger'),
            Stat::make(
                'Çözülen görevler',
                (string) ReportScope::scopedTaskQuery()
                    ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                    ->count(),
            )
                ->description('Tamamlanan görevler')
                ->color('success'),
            Stat::make(
                'Sahadaki ekipler',
                (string) ReportScope::scopedTaskQuery()
                    ->where('status', TaskStatus::Sahada)
                    ->count(),
            )
                ->description('Sahada durumundaki görevler')
                ->color('warning'),
        ];
    }
}
