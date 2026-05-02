<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Kullanıcı Rehberi';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.user-guide';
}
