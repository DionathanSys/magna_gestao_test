<?php

namespace App\Filament\Widgets;

use App\Models\CargaViagem;
use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DispersaoCidade extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected static ?string $heading = 'Top 20 Cidades por Km Dispersão';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        $veiculoId = $this->pageFilters['veiculo_id'] ?? null;
        $dataCompetencia = $this->pageFilters['data_competencia'] ?? null;

        return $table
            ->description('Top 20 Cidades por Km Dispersão - ' . $dataCompetencia ? $dataCompetencia : 'Período não definido')
            ->records(function () use ($veiculoId, $dataCompetencia): array {
                // parse intervalo de data (aceita string "dd/mm/YYYY - dd/mm/YYYY" ou array)
                $start = null;
                $end = null;
                if ($dataCompetencia) {
                    try {
                        if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                            $start = Carbon::parse($dataCompetencia[0])->startOfDay();
                            $end = Carbon::parse($dataCompetencia[1])->endOfDay();
                        } elseif (is_string($dataCompetencia) && str_contains($dataCompetencia, ' - ')) {
                            [$s, $e] = array_map('trim', explode(' - ', $dataCompetencia, 2));
                            $start = Carbon::createFromFormat('d/m/Y', $s)->startOfDay();
                            $end = Carbon::createFromFormat('d/m/Y', $e)->endOfDay();
                        } elseif (is_string($dataCompetencia)) {
                            // único dia
                            $start = Carbon::createFromFormat('d/m/Y', $dataCompetencia)->startOfDay();
                            $end = Carbon::createFromFormat('d/m/Y', $dataCompetencia)->endOfDay();
                        }
                    } catch (\Throwable $e) {
                        $start = null;
                        $end = null;
                    }
                }

                // km_rodado total (aplica mesmos filtros para o cálculo do percentual sobre o total)
                $kmRodadoQuery = Viagem::query()->where('considerar_relatorio', true);
                if ($veiculoId) {
                    $kmRodadoQuery->where('veiculo_id', $veiculoId);
                }
                if ($start && $end) {
                    $kmRodadoQuery->whereBetween('data_competencia', [$start, $end]);
                }
                $kmRodadoTotal = (float) $kmRodadoQuery->sum('km_rodado');

                // consulta agregada por cidade (integrados.municipio)
                $results = DB::table('cargas_viagem')
                    ->select(
                        DB::raw('COALESCE(integrados.municipio, "") as cidade'),
                        DB::raw('COALESCE(SUM(cargas_viagem.km_dispersao), 0) as total_km_dispersao'),
                        DB::raw('COUNT(cargas_viagem.id) as total_cargas'),
                        DB::raw('MIN(cargas_viagem.km_dispersao) as min_km_dispersao'),
                        DB::raw('MAX(cargas_viagem.km_dispersao) as max_km_dispersao'),
                        DB::raw('AVG(cargas_viagem.km_dispersao) as avg_km_dispersao'),
                        DB::raw('CASE WHEN COUNT(cargas_viagem.id) > 0 THEN COALESCE(SUM(cargas_viagem.km_dispersao), 0) / COUNT(cargas_viagem.id) ELSE 0 END as km_dispersao_per_carga'),
                    )
                    ->join('integrados', 'cargas_viagem.integrado_id', '=', 'integrados.id')
                    ->join('viagens', 'cargas_viagem.viagem_id', '=', 'viagens.id')
                    ->where('viagens.considerar_relatorio', true)
                    ->when($veiculoId, function ($query, $veiculoId) {
                        $query->where('viagens.veiculo_id', $veiculoId);
                    })
                    ->when($start && $end, function ($query) use ($start, $end) {
                        $query->whereBetween('viagens.data_competencia', [$start, $end]);
                    })
                    ->groupBy('integrados.municipio')
                    ->orderByDesc('total_km_dispersao')
                    ->limit(20)
                    ->get();

                return $results->mapWithKeys(function ($row) use ($kmRodadoTotal) {
                    $cidade = $row->cidade ?: 'Sem município';

                    $dispOverTotal = $kmRodadoTotal > 0
                        ? ((float) $row->total_km_dispersao / $kmRodadoTotal) * 100
                        : 0.0;

                    return [
                        'cidade_' . md5($cidade) => [
                            'cidade' => $cidade,
                            'total_km_dispersao' => (float) $row->total_km_dispersao,
                            'total_cargas' => (int) $row->total_cargas,
                            'min_km_dispersao' => (float) ($row->min_km_dispersao ?? 0),
                            'max_km_dispersao' => (float) ($row->max_km_dispersao ?? 0),
                            'avg_km_dispersao' => (float) ($row->avg_km_dispersao ?? 0),
                            'km_dispersao_per_carga' => (float) $row->km_dispersao_per_carga,
                        ],
                    ];
                })->toArray();
            })
            ->columns([
                TextColumn::make('cidade')->label('Cidade')->wrap()->searchable(),
                TextColumn::make('total_km_dispersao')->label('Km Dispersão Total')->numeric(2),
                TextColumn::make('total_cargas')->label('Total Cargas')->numeric(0),
                TextColumn::make('min_km_dispersao')->label('Km Mín.')->numeric(2),
                TextColumn::make('max_km_dispersao')->label('Km Máx.')->numeric(2),
                TextColumn::make('avg_km_dispersao')->label('Km Médio')->numeric(2),
                TextColumn::make('km_dispersao_per_carga')->label('Km/Carga')->numeric(2),
            ])
            ->striped()
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
