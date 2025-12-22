<?php

namespace App\Filament\Tables;

use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SelectTableViagem
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Viagem::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {

                $arguments = $table->getArguments();

                if (isset($arguments['veiculo_id'])) {
                    $query->where('veiculo_id', $arguments['veiculo_id']);
                }
                return $query;
            })
            ->columns([
                TextColumn::make('veiculo.placa')   
                    ->label('Placa'),
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transporte')
                    ->searchable(),
                TextColumn::make('km_rodado')
                    ->label('KM Rodado')
                    ->numeric(2, ',', '.')
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->label('KM Pago')
                    ->numeric(2, ',', '.')
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Data Início')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Data Fim')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('condutor'),
                TextColumn::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->searchable(),
                TextColumn::make('cliente')
                    ->label('Cliente'),
            ])
            ->defaultSort('data_inicio', 'desc')
            ->poll(null)
            ->filters([
                DateRangeFilter::make('data_inicio')
                    ->label('Dt. Inicio')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
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
