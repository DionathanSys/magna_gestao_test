<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OficinaManutencaoItensRecorrentes extends TableWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 5;

    protected static ?string $heading = 'Itens Mais Recorrentes por Veículo';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        [$start, $end] = $this->parseDateRangeFilter($this->pageFilters['data_negociacao'] ?? null);

        return $table
            ->description($start && $end ? 'Período: '.$start->format('d/m/Y').' a '.$end->format('d/m/Y') : 'Período completo')
            ->records(function () use ($start, $end): Collection {
                return ManutencaoLancamento::query()
                    ->select([
                        DB::raw('MIN(id) as id'),
                        'veiculo_id',
                        DB::raw('MAX(placa) as placa'),
                        DB::raw('COALESCE(codigo_produto, "-") as codigo_produto_label'),
                        DB::raw('MAX(produto) as produto_label'),
                        DB::raw('COUNT(*) as ocorrencias'),
                        DB::raw('SUM(valor_total_centavos) as total_centavos'),
                        DB::raw('MAX(data_negociacao) as ultima_data_negociacao'),
                    ])
                    ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]))
                    ->groupBy('veiculo_id', DB::raw('COALESCE(codigo_produto, "-")'))
                    ->havingRaw('COUNT(*) > 1')
                    ->orderByDesc('ocorrencias')
                    ->orderByDesc('total_centavos')
                    ->limit(20)
                    ->get();
            })
            ->columns([
                TextColumn::make('placa')
                    ->label('Veículo')
                    ->url(fn ($record) => VeiculoResource::getUrl('edit', ['record' => $record->veiculo_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('codigo_produto_label')
                    ->label('Cód. Produto'),
                TextColumn::make('produto_label')
                    ->label('Produto')
                    ->wrap(),
                TextColumn::make('ocorrencias')
                    ->label('Ocorrências')
                    ->badge()
                    ->color(fn ($state) => $state >= 4 ? 'danger' : ($state >= 3 ? 'warning' : 'info')),
                TextColumn::make('total_centavos')
                    ->label('Total')
                    ->money('BRL', 100),
                TextColumn::make('ultima_data_negociacao')
                    ->label('Última ocorrência')
                    ->date('d/m/Y'),
            ]);
    }
}
