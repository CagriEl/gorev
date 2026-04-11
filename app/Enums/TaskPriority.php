<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPriority: string implements HasLabel
{
    case Normal = 'normal';
    case Yuksek = 'yuksek';
    case Kritik = 'kritik';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Yuksek => 'Yüksek',
            self::Kritik => 'Kritik',
        };
    }
}
