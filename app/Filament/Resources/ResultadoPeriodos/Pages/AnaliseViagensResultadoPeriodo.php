<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

class AnaliseViagensResultadoPeriodo extends AnaliseResultadoPeriodo
{
    protected string $view = 'filament.resources.resultado-periodos.pages.analise-viagens-resultado-periodo';

    public function getTitle(): string
    {
        return 'Viagens do período';
    }
}
