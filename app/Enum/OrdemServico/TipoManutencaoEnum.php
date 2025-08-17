<?php

namespace App\Enum\OrdemServico;

enum TipoManutencaoEnum: string
{
    case CORRETIVA       = 'CORRETIVA';
    case PREVENTIVA      = 'PREVENTIVA';
    case SOCORRO         = 'SOCORRO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
