<?php

namespace App\Enum;

enum ClienteEnum: string
{
    case BRF_CCO        = 'BRF S.A. CHAPECO/SC';
    case BRF_CTV        = 'BRF S.A. CATANDUVAS/SC';
    case BRF_FBT        = 'BRF S.A. FRANCISCO BELTRÃO/PR';
    case BRF_CNC        = 'BRF S.A. CONCÓRDIA/SC';
    case BUGIO          = 'Bugio';
    case BUGIO_NUTRI    = 'Bugio NUTR';
    case NUTREPAMPA_RS  = 'Nutrepampa RS';
    case NUTREPAMPA_SC  = 'Nutrepampa SC';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }

    public function prefixoViagem(): string
    {
        return match ($this) {
            self::BRF_CCO => 'BRFCCO',
            self::BRF_CTV => 'BRFCTV',
            self::BRF_FBT => 'BRFFBT',
            self::BRF_CNC => 'BRFCNC',
            self::BUGIO => 'BG',
            self::BUGIO_NUTRI => 'BGNUTR',
            self::NUTREPAMPA_RS => 'NPRS',
            self::NUTREPAMPA_SC => 'NPSC',
        };  
    }
}
