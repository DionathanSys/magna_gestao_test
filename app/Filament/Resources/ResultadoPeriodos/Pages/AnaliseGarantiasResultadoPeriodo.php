<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\GarantiaServicos\GarantiaServicoResource;
use App\Models\GarantiaServico;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;

class AnaliseGarantiasResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-garantias-resultado-periodo';

    public function getTitle(): string
    {
        return 'Garantias de serviços';
    }

    public function table(Table $table): Table
    {
        $record = $this->getRecord();

        return GarantiaServicoResource::table($table)
            ->query(GarantiaServico::query()
                ->where('veiculo_id', $record->veiculo_id)
                ->whereBetween('data_execucao', [
                    Carbon::parse($record->data_inicio)->startOfDay(),
                    Carbon::parse($record->data_fim)->endOfDay(),
                ]));
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
