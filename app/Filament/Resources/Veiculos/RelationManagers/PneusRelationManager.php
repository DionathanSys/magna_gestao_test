<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Filament\Resources\Veiculos\Actions;
use App\Models\PneuPosicaoVeiculo;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PneusRelationManager extends RelationManager
{
    protected static string $relationship = 'pneus';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                TextInput::make('sequencia')
                    ->label('Sequência')
                    ->columnSpan(2)
                    ->numeric()
                    ->required(),
                TextInput::make('eixo')
                    ->visible(fn(): bool => Auth::user()->is_admin)
                    ->columnStart(1)
                    ->columnSpan(2)
                    ->numeric()
                    ->required(),
                TextInput::make('posicao')
                    ->label('Posição')
                    ->required()
                    ->maxLength(20),
                TextInput::make('km_inicial')
                    ->label('KM Inicial')
                    ->numeric()
                    ->required(),
                DatePicker::make('data_inicial')
                    ->label('Dt. Aplicação')
                    ->date()
                    ->default(now())
                    ->maxDate(now())
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_fogo')
            ->columns([
                TextColumn::make('pneu.numero_fogo')
                    ->label('Pneu')
                    ->placeholder('Vazio')
                    ->width('1%'),
                TextColumn::make('posicao')
                    ->label('Posição')
                    ->width('1%'),
                TextColumn::make('eixo')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('km_inicial')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('veiculo.kmAtual.quilometragem')
                    ->label('Km Atual')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                // ->state(fn (PneuPosicaoVeiculo $record): string => $record->km_inicial ? (($record->veiculo->kmAtual->quilometragem ?? 0) - $record->km_inicial) : 'N/A')
                ,
                TextColumn::make('km_rodado')
                    ->label('Km Rodado Pos.')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_total_historico_ciclo')
                    ->label('KM Histórico (Ciclo 1)')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->state(function (PneuPosicaoVeiculo $record): int {
                        if (!$record->pneu) return 0;

                        return \App\Models\HistoricoMovimentoPneu::where('pneu_id', $record->pneu->id)
                            ->where('ciclo_vida', $record->pneu->ciclo_vida)
                            ->sum('km_percorrido');
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('data_inicial')
                    ->date('d/m/Y'),
                TextColumn::make('sequencia')
                    ->label('Sequência')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->groups([
                Group::make('eixo')
                    ->label('Eixo')
                    ->collapsible(),
            ])
            ->defaultGroup('eixo')
            ->groupingSettingsHidden()
            ->defaultSort('sequencia')
            ->paginated(false)
            ->headerActions([
                CreateAction::make()
                    ->label('Adicionar Pneu')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn() => Auth::user()->is_admin),
            ])
            ->recordActions([
                Actions\DesvincularPneuAction::make(),
                Actions\VincularPneuAction::make(),
                Actions\TrocarPneuAction::make(),
                EditAction::make()
                    ->iconButton()
                    ->visible(fn() => Auth::user()->is_admin),
                DeleteAction::make()
                    ->iconButton()
                    ->visible(fn() => Auth::user()->is_admin),


            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
