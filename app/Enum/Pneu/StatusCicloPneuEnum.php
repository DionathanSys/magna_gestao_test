<?php

namespace App\Enum\Pneu;

enum StatusCicloPneuEnum: string
{
    case ABERTO = 'ABERTO';
    case ENCERRADO = 'ENCERRADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }
}
