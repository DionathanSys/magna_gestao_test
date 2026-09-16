<?php

namespace App\Filament\Resources\PlanoPreventivos\Tables;

use App\Filament\Resources\PlanoPreventivos\PlanoPreventivoResource;
use App\Models\PlanoPreventivo;
use App\Services\Servico\ServicoCacheService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanoPreventivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['veiculos', 'ordensServico']))
            ->recordTitleAttribute('descricao')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periodicidade')
                    ->label('Periodicidade')
                    ->placeholder('Não informada')
                    ->toggleable(),
                TextColumn::make('intervalo')
                    ->label('Intervalo')
                    ->numeric(0, ',', '.')
                    ->suffix(' km')
                    ->sortable(),
                TextColumn::make('itens')
                    ->label('Serviços')
                    ->getStateUsing(function (PlanoPreventivo $record): array {
                        return collect($record->itens ?? [])
                            ->map(fn ($item): ?string => ServicoCacheService::getServicoLabel(data_get($item, 'servico_id')))
                            ->filter()
                            ->values()
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('Nenhum serviço'),
                TextColumn::make('veiculos_count')
                    ->label('Veículos')
                    ->sortable(),
                TextColumn::make('ordens_servico_count')
                    ->label('Execuções')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Ativos',
                        0 => 'Inativos',
                    ]),
            ])
            ->recordUrl(fn (PlanoPreventivo $record): string => PlanoPreventivoResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                PlanoPreventivoResource::deleteAction(),
            ])
            ->toolbarActions([]);
    }
}
