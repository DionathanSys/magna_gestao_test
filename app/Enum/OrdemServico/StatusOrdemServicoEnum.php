<?php

namespace App\Enum\OrdemServico;

enum StatusOrdemServicoEnum: string
{
    case PENDENTE   = 'PENDENTE';
    case EXECUCAO   = 'EXECUÇÃO';
    case CONCLUIDO  = 'CONCLUÍDO';
    case ADIADO     = 'ADIADO';
    case VALIDAR    = 'VALIDAR';
    case CANCELADO  = 'CANCELADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
