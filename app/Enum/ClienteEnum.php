<?php

namespace App\Enum;

enum ClienteEnum: string
{
    case BRF_CCO = 'BRF S.A. CHAPECO/SC';
    case BRF_CTV = 'BRF S.A. CATANDUVAS/SC';
    case BRF_FBT = 'BRF S.A. FRANCISCO BELTRÃO/PR';
    case BRF_CNC = 'BRF S.A. CONCÓRDIA/SC';
    case BUGIO   = 'Bugio';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
