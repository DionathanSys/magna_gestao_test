<?php

namespace App\Enum;

enum PrioridadeEnum: string
{
    case BAIXA      = 'BAIXA';
    case MEDIA      = 'MEDIA';
    case ALTA       = 'ALTA';
    case URGENTE    = 'URGENTE';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
