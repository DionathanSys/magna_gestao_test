<?php

namespace App\Enum\Pneu;

enum LocalPneuEnum: string
{
    case FROTA       = 'FROTA';
    case ESTOQUE_CCO = 'ESTOQUE CCO';
    case ESTOQUE_CTV = 'ESTOQUE CTV';
    case MANUTENCAO  = 'MANUTENÇÃO';
    case SUCATA      = 'SUCATA';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
