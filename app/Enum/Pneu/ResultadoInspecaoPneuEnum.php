<?php

namespace App\Enum\Pneu;

enum ResultadoInspecaoPneuEnum: string
{
    case APROVADO = 'APROVADO';
    case MONITORAR = 'MONITORAR DESGASTE';
    case APTO_RECAPAGEM = 'APTO PARA RECAPAGEM';
    case AGUARDANDO_CONSERTO = 'AGUARDANDO CONSERTO';
    case REPROVADO = 'REPROVADO';
    case CONDENADO = 'CONDENADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }
}
