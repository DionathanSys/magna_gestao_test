<?php

namespace App\Filament\Resources\PneuInspecoes\Tables;

use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PneuInspecoesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_inspecao', 'desc')
            ->columns([
                TextColumn::make('pneu.numero_fogo')
                    ->label('Nº de Fogo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ciclo.numero')
                    ->label('Ciclo')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Ciclo '.$state : 'N/A')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->formatStateUsing(fn ($state) => $state?->value ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        TipoInspecaoPneuEnum::MOVIMENTACAO, TipoInspecaoPneuEnum::CAMPO => 'info',
                        TipoInspecaoPneuEnum::RECEBIMENTO, TipoInspecaoPneuEnum::POS_RECAPAGEM => 'success',
                        TipoInspecaoPneuEnum::PRE_RECAPAGEM => 'warning',
                        TipoInspecaoPneuEnum::CONDENACAO => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('resultado')
                    ->formatStateUsing(fn ($state) => $state?->value ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ResultadoInspecaoPneuEnum::APROVADO => 'success',
                        ResultadoInspecaoPneuEnum::MONITORAR => 'warning',
                        ResultadoInspecaoPneuEnum::APTO_RECAPAGEM => 'info',
                        ResultadoInspecaoPneuEnum::AGUARDANDO_CONSERTO, ResultadoInspecaoPneuEnum::REPROVADO => 'danger',
                        ResultadoInspecaoPneuEnum::CONDENADO => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('km_referencia')
                    ->label('KM')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('data_inspecao')
                    ->label('Dt. Inspeção')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('apto_recapagem')
                    ->label('Apto Recap')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('pneu_id')
                    ->label('Pneu')
                    ->relationship('pneu', 'numero_fogo')
                    ->searchable(),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable(),
                SelectFilter::make('tipo')
                    ->options(TipoInspecaoPneuEnum::toSelectArray()),
                SelectFilter::make('resultado')
                    ->options(ResultadoInspecaoPneuEnum::toSelectArray()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema(fn (Schema $schema): Schema => PneuInspecaoResource::infolist($schema))
                    ->modalWidth('7xl'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
