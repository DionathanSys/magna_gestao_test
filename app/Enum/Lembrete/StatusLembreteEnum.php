<?php

namespace App\Enum\Lembrete;

enum StatusLembreteEnum: string
{
    case PENDENTE = 'pendente';
    case ENVIANDO = 'enviando';
    case ENVIADO = 'enviado';
    case FALHOU = 'falhou';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::ENVIANDO => 'Enviando',
            self::ENVIADO => 'Enviado',
            self::FALHOU => 'Falhou',
            self::CANCELADO => 'Cancelado',
        };
    }
}
