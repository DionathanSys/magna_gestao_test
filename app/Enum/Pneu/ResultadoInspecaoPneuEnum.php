<?php

namespace App\Enum\Pneu;

enum ResultadoInspecaoPneuEnum: string
{
    case APROVADO = 'APROVADO';
    case APROVADO_COM_RESSALVA = 'APROVADO COM RESSALVA';
    case REPROVADO = 'REPROVADO';
    case CONDENADO = 'CONDENADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }
}
