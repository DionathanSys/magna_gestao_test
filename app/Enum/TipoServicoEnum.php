<?php

namespace App\Enum;

enum TipoServicoEnum: string
{
    case PREVENTIVA      = 'PREVENTIVA';
    case CORRETIVA       = 'CORRETIVA';
    case LIMPEZA         = 'LIMPEZA';
    case INSPECAO        = 'INSPEÇÃO';
    

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
