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

    public function table(Table $table): Table
    {
        ds($this->pageFilters)->label('Page Filters DispersaoIntegrado');

        return $table
            ->records(fn (): array => CargaViagem::query()
                ->select(
                    'integrados.id as integrado_id',
                    'integrados.nome as integrado_nome',
                    'integrados.municipio as integrado_municipio',
                    DB::raw('COALESCE(SUM(cargas_viagem.km_dispersao), 0) as total_km_dispersao'),
                    DB::raw('COUNT(cargas_viagem.id) as total_cargas')
                )
                ->join('integrados', 'cargas_viagem.integrado_id', '=', 'integrados.id')
                ->groupBy('integrados.id', 'integrados.nome')
                ->orderByDesc('total_km_dispersao')
                ->limit(20)
                ->get()
                ->mapWithKeys(fn ($row) => [
                    // chave estável por registro (evita problemas de diff do Livewire)
                    'integrado_' . $row->integrado_id => [
                        'integrado_id' => $row->integrado_id,
                        'integrado_nome' => $row->integrado_nome,
                        'integrado_municipio' => $row->integrado_municipio,
                        'total_km_dispersao' => (float) $row->total_km_dispersao,
                        'total_cargas' => (int) $row->total_cargas,
                    ],
                ])->toArray()
            )
            ->columns([
                TextColumn::make('integrado_nome')
                    ->label('Integrado')
                    ->wrap(),
                TextColumn::make('integrado_municipio')
                    ->label('Município')
                    ->wrap(),
                TextColumn::make('total_km_dispersao')
                    ->label('Km Dispersão')
                    ->numeric()
                    ->formatStateUsing(fn($state) => is_null($state) ? '0,00' : number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('total_cargas')
                    ->label('Cargas')
                    ->numeric()
                    ->sortable(),
            ])
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
