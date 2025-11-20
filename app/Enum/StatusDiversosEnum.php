<?php

namespace App\Enum\OrdemServico;

enum StatusDiversosEnum: string
{
    case PENDENTE   = 'PENDENTE';
    case ENCERRADO  = 'ENCERRADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
