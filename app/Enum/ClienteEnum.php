<?php

namespace App\Enum;

enum ClienteEnum: string
{
    case BRF_CCO = 'BRF Chapecó';
    case BRF_CTV = 'BRF Catanduvas';
    case BRF_FBT = 'BRF Francisco Beltrão';
    case BRF_CNC = 'BRF Concórdia';
    case BUGIU   = 'Bugiu';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
