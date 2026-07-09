<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Filament\Resources\ManutencaoLancamentos\Actions\ImportarManutencaoAction;
use App\Filament\Resources\ManutencaoLancamentos\ManutencaoLancamentoResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManutencaoLancamentosRelationManager extends RelationManager
{
    protected static string $relationship = 'manutencaoLancamentos';

    protected static ?string $relatedResource = ManutencaoLancamentoResource::class;

    protected static ?string $title = 'Lançamentos de Manutenção';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('data_negociacao')->orderByDesc('id'))
            ->columns([
                TextColumn::make('data_negociacao')
                    ->label('Dt. Neg.')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->badge()
                    ->sortable(),
                TextColumn::make('produto')
                    ->label('Produto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('codigo_produto')
                    ->label('Cód. Produto')
                    ->searchable()
                    ->toggleable(),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nr_os_nf')
                    ->label('Nr. OS/NF')
                    ->searchable(),
                TextColumn::make('nr_unico')
                    ->label('Nr. Único')
                    ->searchable(),
                TextColumn::make('sequencia')
                    ->label('Sequência')
                    ->searchable(),
                TextColumn::make('origem')
                    ->label('Origem')
                    ->toggleable(),
                TextColumn::make('parceiro')
                    ->label('Parceiro')
                    ->toggleable(),
                TextColumn::make('grupo_produto')
                    ->label('Grupo Produto')
                    ->toggleable(),
                TextColumn::make('unidade')
                    ->label('UN.')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->headerActions([
                ImportarManutencaoAction::make(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('data_negociacao', 'desc');
    }
}
