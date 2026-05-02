<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminTechnicalGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Yönetici/Teknik Rehber';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 45;

    protected static string $view = 'filament.pages.admin-technical-guide';
}
