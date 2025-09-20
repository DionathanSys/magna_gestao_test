<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Models;
use App\Enum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ManutencoesRelationManager extends RelationManager
{
    protected static string $relationship = 'manutencoes';

    protected static ?string $relatedResource = OrdemServicoResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('sankhyaId.ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('quilometragem')
                    ->label('Quilometragem')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->width('1%'),
                TextColumn::make('data_inicio')
                    ->label('Dt. Inicio')
                    ->sortable()
                    ->width('1%')
                    ->date('d/m/Y'),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')
                    ->sortable()
                    ->width('1%')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('itens_count')->counts('itens')
                    ->label('Qtd. Serviços')
                    ->width('1%'),
                TextColumn::make('pendentes_count')->counts('pendentes')
                    ->label('Pendências')
                    ->width('1%')
                    ->color(fn($state): string => $state == 0 ? 'gray' : 'info')
                    ->badge(fn($state): bool => $state > 0),
                TextColumn::make('status')
                    ->width('1%')
                    ->badge('success'),
                TextColumn::make('status_sankhya')
                    ->label('Sankhya')
                    ->width('1%'),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->width('1%')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->width('1%')
                    ->sortable()
                    ->label('Criado Em')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Editado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
            ->deferLoading()
            ->searchable(['sankhyaId.ordem_sankhya_id'])
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('parceiro_id')
                    ->label('Fornecedor')
                    ->relationship('parceiro', 'nome')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->options(Enum\OrdemServico\TipoManutencaoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
                    ->multiple(),
                DateRangeFilter::make('data_inicio')
                    ->label('Dt. Abertura')
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('data_fim')
                    ->label('Dt. Fechamento')
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('created_at')
                    ->label('Dt. Registro'),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
