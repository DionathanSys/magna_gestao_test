<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Tables;

use App\Filament\Bugio\Resources\ViagemBugios\Actions\VincularDocumentoFreteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ViagemBugiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('destinos')
                    ->label('Integrados')
                    ->formatStateUsing(function ($state) {
                        Log::debug('Formatando destinos', [
                            'state' => $state,
                            'type' => gettype($state),
                            'is_array' => is_array($state),
                        ]);

                        if (empty($state)) {
                            return '-';
                        }

                        // Verificar se é um array associativo único ou array de arrays
                        if (isset($state['integrado_nome'])) {
                            // É um único destino
                            return $state['integrado_nome'];
                        }

                        // É um array de destinos
                        return collect($state)
                            ->pluck('integrado_nome')
                            ->filter()
                            ->join(', ');
                    }),
                TextColumn::make('data_competencia')
                    ->label('Data Viagem')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->numeric(0, ',', '.')
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('frete')
                    ->label('Frete')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL')),
                TextColumn::make('condutor')
                    ->label('Motorista')
                    ->sortable()
                    ->searchable(),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'em_andamento' => 'CTe solicitado',
                        'concluido' => 'CTe emitido',
                        'cancelada' => 'Cancelada',
                    ])
                    ->sortable()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Criado Por')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Atualizado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                VincularDocumentoFreteAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->visible(fn() => Auth::user()->is_admin)
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->is_admin),
                ]),
            ]);
    }
}
