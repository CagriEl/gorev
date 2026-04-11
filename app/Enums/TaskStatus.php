<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasLabel
{
    case Bekliyor = 'bekliyor';
    case Yonlendirildi = 'yonlendirildi';
    case Sahada = 'sahada';
    case Tamamlandi = 'tamamlandi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Bekliyor => 'Bekliyor',
            self::Yonlendirildi => 'Yönlendirildi',
            self::Sahada => 'Sahada',
            self::Tamamlandi => 'Tamamlandı',
        };
    }
}
