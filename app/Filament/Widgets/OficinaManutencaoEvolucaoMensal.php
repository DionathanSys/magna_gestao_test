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

class OficinaManutencaoEvolucaoMensal extends TableWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 4;

    protected static ?string $heading = 'Evolução Mensal dos Custos';

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
                        DB::raw('DATE_FORMAT(data_negociacao, "%Y-%m") as referencia'),
                        DB::raw('SUM(valor_total_centavos) as total_centavos'),
                        DB::raw('SUM(CASE WHEN tipo_manutencao = "Preventiva" THEN valor_total_centavos ELSE 0 END) as preventiva_centavos'),
                        DB::raw('SUM(CASE WHEN tipo_manutencao = "Corretiva" THEN valor_total_centavos ELSE 0 END) as corretiva_centavos'),
                        DB::raw('COUNT(*) as total_lancamentos'),
                        DB::raw('COUNT(DISTINCT veiculo_id) as total_veiculos'),
                    ])
                    ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]))
                    ->groupBy(DB::raw('DATE_FORMAT(data_negociacao, "%Y-%m")'))
                    ->orderByDesc('referencia')
                    ->get()
                    ->map(function ($record) {
                        $record->corretiva_percentual = (int) $record->total_centavos > 0
                            ? number_format((((int) $record->corretiva_centavos) / ((int) $record->total_centavos)) * 100, 2, ',', '.').'%'
                            : '0,00%';

                        return $record;
                    });
            })
            ->columns([
                TextColumn::make('referencia')
                    ->label('Mês')
                    ->formatStateUsing(function ($state): string {
                        [$ano, $mes] = explode('-', (string) $state);

                        return sprintf('%s/%s', $mes, $ano);
                    }),
                TextColumn::make('total_centavos')
                    ->label('Total')
                    ->money('BRL', 100),
                TextColumn::make('preventiva_centavos')
                    ->label('Preventiva')
                    ->money('BRL', 100),
                TextColumn::make('corretiva_centavos')
                    ->label('Corretiva')
                    ->money('BRL', 100),
                TextColumn::make('corretiva_percentual')
                    ->label('% Corretiva'),
                TextColumn::make('total_veiculos')
                    ->label('Veículos')
                    ->numeric(0, ',', '.'),
            ]);
    }
}
