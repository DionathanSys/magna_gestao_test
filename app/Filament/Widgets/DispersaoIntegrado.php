<?php

namespace App\Filament\Widgets;

use App\Models\CargaViagem;
use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DispersaoIntegrado extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $placa = $this->pageFilters['placa'] ?? null;
        $dataCompetencia = $this->pageFilters['data_competencia'] ?? null;

        return $table
            ->query(
                CargaViagem::query()
                    ->select(
                        'integrados.id as integrado_id',
                        'integrados.nome as integrado_nome',
                        'integrados.municipio as integrado_municipio',
                        DB::raw('COALESCE(SUM(cargas_viagem.km_dispersao), 0) as total_km_dispersao'),
                        DB::raw('COUNT(cargas_viagem.id) as total_cargas'),
                        DB::raw('MIN(cargas_viagem.km_dispersao) as min_km_dispersao'),
                        DB::raw('MAX(cargas_viagem.km_dispersao) as max_km_dispersao'),
                        DB::raw('AVG(cargas_viagem.km_dispersao) as avg_km_dispersao'),
                        DB::raw('CASE WHEN COUNT(cargas_viagem.id) > 0 THEN COALESCE(SUM(cargas_viagem.km_dispersao), 0) / COUNT(cargas_viagem.id) ELSE 0 END as km_dispersao_per_carga'),
                        DB::raw('CASE WHEN SUM(viagens.km_pago) > 0 THEN (COALESCE(SUM(cargas_viagem.km_dispersao), 0) / SUM(viagens.km_pago)) * 100 ELSE 0 END as dispersao_percentage')
                    )
                    ->join('integrados', 'cargas_viagem.integrado_id', '=', 'integrados.id')
                    ->join('viagens', 'cargas_viagem.viagem_id', '=', 'viagens.id')
                    ->when($placa, function (Builder $query, $placa) {
                        return $query->where('viagens.placa', $placa);
                    })
                    ->when($dataCompetencia, function (Builder $query, $dataCompetencia) {
                        if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                            return $query->whereBetween('viagens.data_competencia', [
                                $dataCompetencia[0],
                                $dataCompetencia[1]
                            ]);
                        }
                        return $query;
                    })
                    ->groupBy('integrados.id', 'integrados.nome', 'integrados.municipio')
                    ->orderByDesc('total_km_dispersao')
            )
            ->columns([
                TextColumn::make('integrado_nome')
                    ->label('Integrado')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('integrado_municipio')
                    ->label('Município')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_km_dispersao')
                    ->label('Km Dispersão Total')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('total_cargas')
                    ->label('Total Cargas')
                    ->numeric(0)
                    ->sortable(),
                TextColumn::make('min_km_dispersao')
                    ->label('Km Mín.')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('max_km_dispersao')
                    ->label('Km Máx.')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('avg_km_dispersao')
                    ->label('Km Médio')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('km_dispersao_per_carga')
                    ->label('Km/Carga')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('dispersao_percentage')
                    ->label('% Dispersão/Km Pago')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable(),
            ])
            ->defaultSort('total_km_dispersao', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
