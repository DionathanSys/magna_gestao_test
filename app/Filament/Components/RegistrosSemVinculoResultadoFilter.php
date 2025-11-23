<?php

namespace App\Filament\Components;

use Filament\Tables\Filters\Filter;

class RegistrosSemVinculoResultadoFilter
{
    public static function make(): Filter
    {
        return Filter::make('sem_vinculo_resultado')
            ->label('Sem vínculo com Resultados')
            ->toggle()
            ->query(fn ($query) => $query->whereNull('resultado_periodo_id'));
    }
}