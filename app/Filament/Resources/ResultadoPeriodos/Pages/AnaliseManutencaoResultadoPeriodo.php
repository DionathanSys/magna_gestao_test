<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ManutencaoLancamentos\ManutencaoLancamentoResource;
use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class AnaliseManutencaoResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-manutencao-resultado-periodo';

    public function getTitle(): string
    {
        return 'Custos de manutenção';
    }

    public function table(Table $table): Table
    {
        return ManutencaoLancamentoResource::table($table)
            ->query($this->getRecord()->manutencaoLancamentos()->getQuery())
            ->groups([
                Group::make('ordemServico.id')
                    ->label('Ordem interna')
                    ->collapsible(),
            ])
            ->defaultGroup('ordemServico.id');
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
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
