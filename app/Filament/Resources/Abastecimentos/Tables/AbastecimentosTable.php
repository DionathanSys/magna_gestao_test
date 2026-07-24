<?php

namespace App\Filament\Resources\Abastecimentos\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Filament\Actions\DissociateResultadoPeriodoBulkAction;
use App\Filament\Components\RegistrosSemVinculoResultadoFilter;
use App\Filament\Resources\Abastecimentos;
use App\Filament\Resources\ResultadoPeriodos\RelationManagers\AbastecimentosRelationManager;
use App\Models\Abastecimento;
use App\Services\Veiculo\VeiculoCacheService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class AbastecimentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $query->with(['veiculo:id,placa,tipo_veiculo_id', 'veiculo.tipoVeiculo:id,meta_media', 'resultadoPeriodo:id,data_inicio']);
            })
            ->columns([
                TextColumn::make('id_abastecimento')
                    ->numeric(0, '', '')
                    ->width('1%')
                    ->disabledClick()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->hiddenOn(AbastecimentosRelationManager::class),
                TextColumn::make('quilometragem')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('quantidade')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->label('Total Lts.')),
                TextColumn::make('preco_por_litro')
                    ->label('Preço por Litro')
                    ->width('1%')
                    ->money('BRL', true)
                    ->sortable(),
                TextColumn::make('preco_total')
                    ->label('Preço Total')
                    ->width('1%')
                    ->money('BRL', true)
                    ->sortable()
                    ->summarize(Sum::make()->money('BRL', 100)->label('Vlr. Total')),
                TextColumn::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->disabledClick()
                    ->width('1%')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('quilometragem_percorrida')
                    ->label('Km Percorridos')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('consumo_medio')
                    ->label('Consumo Médio')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->sortable()
                    ->badge()
                    ->tooltip(function (Abastecimento $record): string {
                        $consumo = $record->consumo_medio;
                        $meta = $record->veiculo?->tipoVeiculo?->meta_media ?? 0;

                        if ($consumo === null || $meta === 0) {
                            return 'Sem dados para comparação';
                        }

                        $percentual = round(($consumo / $meta) * 100);

                        return match (true) {
                            $consumo >= ($meta * 1.39) => "Excelente! {$percentual}% da meta 🚀",
                            $consumo > ($meta * 0.99) => "Meta Atingida: {$percentual}% da meta ✅",
                            $consumo >= ($meta * 0.85) => "Abaixo da meta: {$percentual}% da meta",
                            $consumo >= ($meta * 0.5) => "Muito abaixo da meta: {$percentual}% ⚠️",
                            default => "Crítico: {$percentual}% da meta ❌",
                        };
                    })
                    ->color(function (Abastecimento $record) {
                        $consumo = $record->consumo_medio;
                        $meta = $record->veiculo?->tipoVeiculo?->meta_media ?? 0;

                        if ($consumo === null || $meta === 0) {
                            return Color::Gray;
                        }

                        return match (true) {
                            $consumo >= ($meta * 1.39) => Color::Yellow,        // ≥ 139% da meta - Amarelo
                            $consumo >= ($meta * 0.85) => Color::Gray,          // Entre 85% e 138% da meta - Cinza
                            $consumo >= ($meta * 0.5) => Color::Orange,         // Entre 50% e 84% da meta - Laranja
                            default => Color::Red,                              // < 50% da meta - Vermelho
                        };
                    })
                    ->formatStateUsing(function ($state, Abastecimento $record) {
                        if ($state === null) {
                            return 'N/D';
                        }

                        $meta = $record->veiculo?->tipoVeiculo?->meta_media;
                        $formatted = number_format($state, 2, ',', '.');

                        if ($meta) {
                            $percentual = round(($state / $meta) * 100);

                            return "{$formatted} Km/L ({$percentual}%)";
                        }

                        return $formatted;
                    }),
                TextColumn::make('veiculo.tipoVeiculo.meta_media')
                    ->label('Meta Média')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('posto_combustivel')
                    ->label('Posto de Combustível')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tipo_combustivel')
                    ->label('Tipo de Combustível')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('considerar_fechamento')
                    ->label('Considerar Fechamento')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('considerar_calculo_medio')
                    ->label('Considerar Cálculo Médio')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextInputColumn::make('resultado_periodo_id')
                    ->label('Resultado ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('resultadoPeriodo.data_inicio')
                    ->label('Resultado Período')
                    ->disabledClick()
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_abastecimento', 'asc')
            ->groups([
                Group::make('veiculo.placa')
                    ->label('Veículo')
                    ->collapsible(),
                Group::make('veiculo.tipoVeiculo.descricao')
                    ->label('Tipo de Veículo')
                    ->collapsible(),
                Group::make('resultadoPeriodo.data_inicio')
                    ->label('Resultado Período')
                    ->collapsible(),
            ])
            ->striped()
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->options(VeiculoCacheService::getPlacasAtivasForSelect())
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('tipo_veiculo')
                    ->label('Tipo de Veículo')
                    ->relationship('veiculo.tipoVeiculo', 'descricao')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->drops(DropDirection::AUTO)
                    ->icon('heroicon-o-backspace')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->defaultLast7Days(),
                DateRangeFilter::make('resultadoPeriodo.data_inicio')
                    ->label('Dt. Resultado Período')
                    ->drops(DropDirection::AUTO)
                    ->icon('heroicon-o-backspace')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->firstDayOfWeek(0),
                Filter::make('considera_no_calculo_media')
                    ->label('Abastecidas p/ cálculo médio')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('considerar_calculo_medio', true)),
                RegistrosSemVinculoResultadoFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    FilamentExportBulkAction::make('export'),
                    DissociateResultadoPeriodoBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
                ActionGroup::make([
                    Abastecimentos\Actions\ImportAbastecimentoAction::make(),
                    CreateAction::make(),
                ])->button(),
            ])
            ->paginated([25, 50, 100, 250, 500])
            ->extremePaginationLinks();
    }
}
