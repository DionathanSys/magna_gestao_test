<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

class AnaliseGarantiasResultadoPeriodo extends AnaliseResultadoPeriodo
{
    protected string $view = 'filament.resources.resultado-periodos.pages.analise-garantias-resultado-periodo';

    public function getTitle(): string
    {
        return 'Garantias de serviços';
    }
}
