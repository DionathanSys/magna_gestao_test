<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

class AnaliseAbastecimentosResultadoPeriodo extends AnaliseResultadoPeriodo
{
    protected string $view = 'filament.resources.resultado-periodos.pages.analise-abastecimentos-resultado-periodo';

    public function getTitle(): string
    {
        return 'Abastecimentos do período';
    }
}
