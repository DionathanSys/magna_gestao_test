<?php

namespace App\Enum\Pneu;

enum MotivoMovimentoPneuEnum: string
{
    case RODIZIO   = 'RODIZIO';
    case ESTEPE    = 'ESTEPE';
    case RECAPAGEM = 'RECAPAGEM';
    case CONSERTO  = 'CONSERTO';
    case SUCATEAR  = 'SUCATEAR';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->name => $item->value])
            ->toArray();
    }
}
