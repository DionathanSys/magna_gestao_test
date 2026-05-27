<?php

namespace App\Enum\Pneu;

enum TipoInspecaoPneuEnum: string
{
    case CAMPO = 'CAMPO';
    case MOVIMENTACAO = 'MOVIMENTACAO';
    case RECEBIMENTO = 'RECEBIMENTO';
    case PRE_RECAPAGEM = 'PRE-RECAPAGEM';
    case POS_RECAPAGEM = 'POS-RECAPAGEM';
    case CONDENACAO = 'CONDENACAO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }
}
