<?php

namespace App\Filament\Widgets;

use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OficinaManutencaoPorGrupoProduto extends TableWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Custos por Grupo de Produto';

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
            ->records(function () use ($start, $end, $totalCentavos): Collection {
                return ManutencaoLancamento::query()
                    ->select([
                        DB::raw('MIN(id) as id'),
                        DB::raw('COALESCE(grupo_produto, "Sem grupo") as grupo_produto_label'),
                        DB::raw('SUM(valor_total_centavos) as total_centavos'),
                        DB::raw('COUNT(*) as total_lancamentos'),
                        DB::raw('COUNT(DISTINCT veiculo_id) as total_veiculos'),
                    ])
                    ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]))
                    ->groupBy(DB::raw('COALESCE(grupo_produto, "Sem grupo")'))
                    ->orderByDesc('total_centavos')
                    ->get()
                    ->map(function ($record) use ($totalCentavos) {
                        $record->participacao = $totalCentavos > 0
                            ? number_format((((int) $record->total_centavos) / $totalCentavos) * 100, 2, ',', '.').'%'
                            : '0,00%';

                        return $record;
                    });
            })
            ->columns([
                TextColumn::make('grupo_produto_label')
                    ->label('Grupo Produto')
                    ->wrap(),
                TextColumn::make('total_centavos')
                    ->label('Total')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('participacao')
                    ->label('% Participação')
                    ->state(fn ($record): string => $record->participacao)
                    ->sortable(false),
                TextColumn::make('total_veiculos')
                    ->label('Veículos')
                    ->numeric(0, ',', '.')
                    ->sortable(),
            ]);
    }
}
