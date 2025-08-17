<?php

namespace App\Enum\Viagem;

enum StatusViagemEnum: string
{
    case PENDENTE   = 'PENDENTE';
    case CANCELADO  = 'CANCELADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
