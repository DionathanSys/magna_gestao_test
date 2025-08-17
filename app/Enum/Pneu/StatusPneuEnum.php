<?php

namespace App\Enum\Pneu;

enum StatusPneuEnum: string
{
    case DISPONIVEL     = 'DISPONIVEL';
    case EM_USO         = 'EM USO';
    case INDISPONIVEL   = 'INDISPONIVEL';
    case SUCATA         = 'SUCATA';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
