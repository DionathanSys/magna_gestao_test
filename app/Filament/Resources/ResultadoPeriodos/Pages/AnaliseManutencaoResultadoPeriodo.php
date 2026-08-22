<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use Filament\Actions\Action;

class AnaliseManutencaoResultadoPeriodo extends AnaliseResultadoPeriodo
{
    protected string $view = 'filament.resources.resultado-periodos.pages.analise-manutencao-resultado-periodo';

    public function getTitle(): string
    {
        return 'Custos de manutenção';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analise')
                ->label('Voltar à análise')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('analise', ['record' => $this->recordId])),
            Action::make('editar')
                ->label('Editar resultado')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('edit', ['record' => $this->recordId])),
        ];
    }
}
