<?php

namespace App\Enum\Frete;

enum TipoDocumentoEnum: string
{
    case NFS             = 'NFSe';
    case CTE             = 'CTe';
    case CTE_COMPLEMENTO = 'CTe Complemento';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
