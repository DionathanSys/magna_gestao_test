<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class OficinaManutencaoPorVeiculo extends TableWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Custos por Veículo';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        [$start, $end] = $this->parseDateRangeFilter($this->pageFilters['data_negociacao'] ?? null);

        $baseQuery = ManutencaoLancamento::query()
            ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]));

        $totalCentavos = (int) (clone $baseQuery)->sum('valor_total_centavos');

        return $table
            ->description($start && $end ? 'Período: '.$start->format('d/m/Y').' a '.$end->format('d/m/Y') : 'Período completo')
            ->query(
                ManutencaoLancamento::query()
                    ->select([
                        DB::raw('MIN(id) as id'),
                        'veiculo_id',
                        DB::raw('MAX(placa) as placa'),
                        DB::raw('SUM(valor_total_centavos) as total_centavos'),
                        DB::raw('COUNT(*) as total_lancamentos'),
                    ])
                    ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]))
                    ->groupBy('veiculo_id')
                    ->orderByDesc('total_centavos')
            )
            ->columns([
                TextColumn::make('placa')
                    ->label('Veículo')
                    ->url(fn ($record) => VeiculoResource::getUrl('edit', ['record' => $record->veiculo_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('total_centavos')
                    ->label('Total')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('participacao')
                    ->label('% Participação')
                    ->state(fn ($record): string => number_format($totalCentavos > 0 ? (((int) $record->total_centavos / $totalCentavos) * 100) : 0, 2, ',', '.').'%')
                    ->sortable(false),
                TextColumn::make('total_lancamentos')
                    ->label('Lançamentos')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('ticket_medio')
                    ->label('Ticket Médio')
                    ->state(function ($record): int {
                        $count = max(1, (int) $record->total_lancamentos);

                        return (int) round(((int) $record->total_centavos) / $count);
                    })
                    ->money('BRL', 100),
            ])
            ->defaultPaginationPageOption(10);
    }
}
