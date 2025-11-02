<?php

namespace App\Enum\Pneu;

enum EstadoPneuEnum: string
{
    case NOVO     = 'NOVO';
    case RECAPADO = 'RECAPADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
