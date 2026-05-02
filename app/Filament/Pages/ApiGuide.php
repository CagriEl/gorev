<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ApiGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationLabel = 'API Rehberi';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.api-guide';
}
