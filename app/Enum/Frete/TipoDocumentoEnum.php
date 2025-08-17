<?php

namespace App\Enum\Frete;

enum TipoDocumentoEnum: string
{
    case CTE = 'CTe';
    case NFS = 'NFSe';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->name => $item->value])
            ->toArray();
    }
}
