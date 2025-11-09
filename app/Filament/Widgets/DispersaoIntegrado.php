<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Viagems\ViagemResource;
use App\Models\CargaViagem;
use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DispersaoIntegrado extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        $veiculoId = $this->pageFilters['veiculo_id'] ?? null;
        $dataCompetencia = $this->pageFilters['data_competencia'] ?? null;

        return $table
            ->description('Top 20 Integrados por Km Dispersão - ' . $dataCompetencia ? $dataCompetencia : 'Período não definido')
            ->records(function () use ($veiculoId, $dataCompetencia): array {

                $kmRodadoTotal = Viagem::query()
                    ->when($veiculoId, function ($query, $veiculoId) {
                        return $query->where('veiculo_id', $veiculoId);
                    })
                    ->when($dataCompetencia, function ($query, $dataCompetencia) {
                        // data_competencia pode chegar como array ou como string no formato
                        // 'dd/mm/YYYY - dd/mm/YYYY'. Tratamos ambos os casos.
                        try {
                            if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                                return $query->whereBetween('data_competencia', [
                                    $dataCompetencia[0],
                                    $dataCompetencia[1]
                                ]);
                            }

                            if (is_string($dataCompetencia) && str_contains($dataCompetencia, ' - ')) {
                                [$start, $end] = array_map('trim', explode(' - ', $dataCompetencia, 2));

                                // converte 'dd/mm/YYYY' -> 'YYYY-mm-dd'
                                $startDate = Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                                $endDate = Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                                return $query->whereBetween('data_competencia', [$startDate, $endDate]);
                            }
                        } catch (\Throwable $e) {
                            // se o parse falhar, ignoramos o filtro de data e continuamos
                        }

                        return $query;
                    })
                    ->where('considerar_relatorio', true)
                    ->sum('km_rodado');

                $results = DB::table('cargas_viagem')
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
                        DB::raw('CASE WHEN SUM(viagens.km_rodado) > 0 THEN (COALESCE(SUM(cargas_viagem.km_dispersao), 0) / SUM(viagens.km_rodado)) * 100 ELSE 0 END as dispersao_percentage')
                    )
                    ->join('integrados', 'cargas_viagem.integrado_id', '=', 'integrados.id')
                    ->join('viagens', 'cargas_viagem.viagem_id', '=', 'viagens.id')
                    ->where('viagens.considerar_relatorio', true)
                    ->when($veiculoId, function ($query, $veiculoId) {
                        return $query->where('viagens.veiculo_id', $veiculoId);
                    })
                    ->when($dataCompetencia, function ($query, $dataCompetencia) {
                        // data_competencia pode chegar como array ou como string no formato
                        // 'dd/mm/YYYY - dd/mm/YYYY'. Tratamos ambos os casos.
                        try {
                            if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                                return $query->whereBetween('viagens.data_competencia', [
                                    $dataCompetencia[0],
                                    $dataCompetencia[1]
                                ]);
                            }

                            if (is_string($dataCompetencia) && str_contains($dataCompetencia, ' - ')) {
                                [$start, $end] = array_map('trim', explode(' - ', $dataCompetencia, 2));

                                // converte 'dd/mm/YYYY' -> 'YYYY-mm-dd'
                                $startDate = Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                                $endDate = Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                                return $query->whereBetween('viagens.data_competencia', [$startDate, $endDate]);
                            }
                        } catch (\Throwable $e) {
                            // se o parse falhar, ignoramos o filtro de data e continuamos
                        }

                        return $query;
                    })
                    ->groupBy('integrados.id', 'integrados.nome', 'integrados.municipio')
                    ->orderByDesc('total_km_dispersao')
                    ->limit(20)
                    ->get();

                return $results->mapWithKeys(fn ($row) => [
                    'integrado_' . $row->integrado_id => [
                        'integrado_id' => $row->integrado_id,
                        'integrado_nome' => $row->integrado_nome,
                        'integrado_municipio' => $row->integrado_municipio,
                        'total_km_dispersao' => (float) $row->total_km_dispersao,
                        'total_cargas' => (int) $row->total_cargas,
                        'min_km_dispersao' => (float) ($row->min_km_dispersao ?? 0),
                        'max_km_dispersao' => (float) ($row->max_km_dispersao ?? 0),
                        'avg_km_dispersao' => (float) ($row->avg_km_dispersao ?? 0),
                        'km_dispersao_per_carga' => (float) $row->km_dispersao_per_carga,
                        'dispersao_percentage' => (float) $row->dispersao_percentage,
                    ]
                ])->toArray();
            })
            ->columns([
                TextColumn::make('integrado_nome')
                    ->label('Integrado')
                    ->wrap()
                    ->searchable()
                    ->url(fn ($record) => ViagemResource::getUrl('index', [
                        'filters' => [
                            'integrado_id' => $record['integrado_id'],
                        ],
                    ]))
                    ->openUrlInNewTab(),
                TextColumn::make('integrado_municipio')
                    ->label('Município')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('total_km_dispersao')
                    ->label('Km Dispersão Total')
                    ->numeric(2),
                TextColumn::make('total_cargas')
                    ->label('Total Cargas')
                    ->numeric(0),
                TextColumn::make('min_km_dispersao')
                    ->label('Km Mín.')
                    ->numeric(2),
                TextColumn::make('max_km_dispersao')
                    ->label('Km Máx.')
                    ->numeric(2),
                TextColumn::make('avg_km_dispersao')
                    ->label('Km Médio')
                    ->numeric(2),
                TextColumn::make('km_dispersao_per_carga')
                    ->label('Km/Carga')
                    ->numeric(2),
                TextColumn::make('dispersao_percentage')
                    ->label('% Dispersão/Km Rodado')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->numeric(2)
                    ->suffix('%'),
            ])
            ->striped()
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
