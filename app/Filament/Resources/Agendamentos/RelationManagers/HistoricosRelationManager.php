<?php

namespace App\Filament\Resources\Agendamentos\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoricosRelationManager extends RelationManager
{
    protected static string $relationship = 'historicos';

    protected static ?string $title = 'Histórico';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tipo_evento')
            ->columns([
                TextColumn::make('tipo_evento')
                    ->label('Evento')
                    ->badge(),
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->placeholder('Sem descrição'),
                TextColumn::make('creator.name')
                    ->label('Usuário')
                    ->placeholder('Sistema'),
                TextColumn::make('dados')
                    ->label('Detalhes')
                    ->formatStateUsing(function ($state): ?string {
                        if (! is_array($state) || $state === []) {
                            return null;
                        }

                        if (! isset($state['alteracoes']) || ! is_array($state['alteracoes'])) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                        return collect($state['alteracoes'])
                            ->map(fn (array $alteracao, string $campo): string => $campo.': '.data_get($alteracao, 'antes', 'null').' -> '.data_get($alteracao, 'depois', 'null'))
                            ->join(' | ');
                    })
                    ->wrap()
                    ->placeholder('Sem detalhes'),
                TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
