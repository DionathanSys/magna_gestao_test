<?php

namespace App\Filament\Resources\ManutencaoLancamentos\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManutencaoLancamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['veiculo:id,placa']))
            ->defaultSort('data_negociacao', 'desc')
            ->columns([
                TextColumn::make('data_negociacao')
                    ->label('Dt. Neg.')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->badge()
                    ->sortable(),
                TextColumn::make('produto')
                    ->label('Produto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('quantidade')
                    ->label('Qtde.')
                    ->numeric(4, ',', '.'),
                TextColumn::make('valor_total_centavos')
                    ->label('Vlr. Total')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('valor_unitario_centavos')
                    ->label('Vlr. Unitário')
                    ->money('BRL', 100)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('nr_unico')
                    ->label('Nr. Único')
                    ->searchable(),
                TextColumn::make('sequencia')
                    ->label('Sequência')
                    ->searchable(),
                TextColumn::make('nr_os_nf')
                    ->label('Nr. OS/NF')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parceiro')
                    ->label('Parceiro')
                    ->toggleable(),
                TextColumn::make('grupo_produto')
                    ->label('Grupo Produto')
                    ->toggleable(),
                TextColumn::make('origem')
                    ->label('Origem')
                    ->toggleable(),
                TextColumn::make('deleted_at')
                    ->label('Removido em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->options([
                        'Corretiva' => 'Corretiva',
                        'Preventiva' => 'Preventiva',
                    ]),
                SelectFilter::make('veiculo_id')
                    ->label('Placa')
                    ->relationship('veiculo', 'placa'),
                Filter::make('data_negociacao')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('data_inicio')->label('Data inicial'),
                        \Filament\Forms\Components\DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_negociacao', '>=', $date))
                            ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_negociacao', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
