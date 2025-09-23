<?php

namespace App\Enum\Import;

enum StatusImportacaoEnum: string
{
    case PENDENTE               = 'PENDENTE';
    case PROCESSANDO            = 'PROCESSANDO';
    case CONCLUIDO              = 'CONCLUIDO';
    case CONCLUIDO_COM_ERROS    = 'CONCLUIDO COM ERROS';
    case FALHOU                 = 'FALHOU';
    case CANCELADO              = 'CANCELADO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->name => $item->value])
            ->toArray();
    }
}
