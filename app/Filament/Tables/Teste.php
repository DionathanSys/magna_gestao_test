<?php

namespace App\Filament\Tables;

use App\Models\OrdemServico;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Teste
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => OrdemServico::query())
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->searchable(isIndividual: true),
                TextColumn::make('tipo_manutencao'),
                TextColumn::make('data_inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('status_sankhya'),
                TextColumn::make('parceiro.nome'),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
