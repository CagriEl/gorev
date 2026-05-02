<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Support\ReportScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ViceMayorStatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalStaff = (int) ReportScope::scopedDepartmentQuery()->sum('staff_count');

        $activeTasks = (int) ReportScope::scopedTaskQuery()
            ->whereNotIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
            ->count();

        $resolvedToday = (int) ReportScope::scopedTaskQuery()
            ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
            ->whereDate('resolved_at', today())
            ->count();

        return [
            Stat::make('Toplam personel', (string) $totalStaff)
                ->description('Bağlı müdürlüklerdeki personel sayısı toplamı')
                ->color('primary'),
            Stat::make('Aktif görev', (string) $activeTasks)
                ->description('Tamamlanmamış görevler')
                ->color('warning'),
            Stat::make('Bugün çözülen', (string) $resolvedToday)
                ->description('Bugün tamamlanan görevler')
                ->color('success'),
        ];
    }
}
