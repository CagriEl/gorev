<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasLabel
{
    case Bekliyor = 'bekliyor';
    case Yonlendirildi = 'yonlendirildi';
    case Sahada = 'sahada';
    case Cozuldu = 'cozuldu';
    case OnayBekliyor = 'onay_bekliyor';
    case Kapatildi = 'kapatildi';
    case Tamamlandi = 'tamamlandi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Bekliyor => 'Bekliyor',
            self::Yonlendirildi => 'Yönlendirildi',
            self::Sahada => 'Sahada',
            self::Cozuldu => 'Çözüldü',
            self::OnayBekliyor => 'Onay bekliyor',
            self::Kapatildi => 'Kapatıldı',
            self::Tamamlandi => 'Tamamlandı',
        };
    }
}
