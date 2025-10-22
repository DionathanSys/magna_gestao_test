<?php

namespace App\Filament\Tables;


use App\{Models, Enum, Services};
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Teste
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Models\OrdemServico::query())
            ->modifyQueryUsing(function(Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();
                if (isset($arguments['veiculo_id'])){
                    $query->where('veiculo_id', $arguments['veiculo_id']);
                }
                return $query;
            })
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
