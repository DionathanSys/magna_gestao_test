<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\AnaliseServicosOrdemServicos\AnaliseServicosOrdemServicoResource;
use App\Models\ItemOrdemServico;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnaliseServicosResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-servicos-resultado-periodo';

    public function getTitle(): string
    {
        return 'Serviços das ordens internas';
    }

    public function table(Table $table): Table
    {
        $record = $this->getRecord();

        return AnaliseServicosOrdemServicoResource::table($table)
            ->query(ItemOrdemServico::query()->whereHas('ordemServico', fn (Builder $query): Builder => $query
                ->where('veiculo_id', $record->veiculo_id)
                ->whereBetween('data_inicio', [$record->data_inicio, $record->data_fim])))
            ->groups([
                Group::make('ordem_servico_id')
                    ->label('Ordem interna')
                    ->collapsible(),
            ])
            ->defaultGroup('ordem_servico_id');
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
