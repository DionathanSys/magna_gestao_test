<?php

namespace App\Enum\Agendamento;

enum CategoriaAgendamentoEnum: string
{
    case MANUAL = 'MANUAL';
    case CHECKLIST = 'CHECKLIST';
    case REAGENDAMENTO = 'REAGENDAMENTO';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }
}
