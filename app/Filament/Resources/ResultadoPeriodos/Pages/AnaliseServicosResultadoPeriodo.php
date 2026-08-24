<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

class AnaliseServicosResultadoPeriodo extends AnaliseResultadoPeriodo
{
    protected string $view = 'filament.resources.resultado-periodos.pages.analise-servicos-resultado-periodo';

    public function getTitle(): string
    {
        return 'Serviços das ordens internas';
    }
}
