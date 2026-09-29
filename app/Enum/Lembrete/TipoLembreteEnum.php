<?php

namespace App\Enum\Lembrete;

enum TipoLembreteEnum: string
{
    case ALERTA = 'alerta';
    case LEMBRETE = 'lembrete';

    public function label(): string
    {
        return match ($this) {
            self::ALERTA => 'Alerta',
            self::LEMBRETE => 'Lembrete',
        };
    }
}
