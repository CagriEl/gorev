<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\ViceMayorCompletedByDepartmentChart;
use App\Filament\Widgets\ViceMayorStatsOverview;
use App\Filament\Widgets\ViceMayorStatusDoughnutChart;
use Filament\Pages\Page;

class ViceMayorReport extends Page
{
    protected static string $view = 'filament.pages.vice-mayor-report';

    protected static ?string $slug = 'baskan-yardimcisi-raporu';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Başkan yardımcısı raporu';

    protected static ?string $title = 'Başkan yardımcısı raporu';

    protected static ?string $navigationGroup = 'Raporlar';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && in_array($user->role, [UserRole::Admin, UserRole::ViceMayor], true);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ViceMayorStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 3;
    }

    protected function getFooterWidgets(): array
    {
        return [
            ViceMayorCompletedByDepartmentChart::class,
            ViceMayorStatusDoughnutChart::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|string|array
    {
        return 2;
    }
}
