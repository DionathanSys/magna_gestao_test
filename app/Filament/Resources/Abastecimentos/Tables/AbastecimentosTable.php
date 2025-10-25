<?php

namespace App\Filament\Resources\Abastecimentos\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Filament\Resources\Abastecimentos;
use App\Models\Abastecimento;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Text;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class AbastecimentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $query->with(['veiculo.tipoVeiculo']);
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
                    ->toggleable(isToggledHiddenByDefault: false),
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
                            $consumo >= ($meta * 1.39) => "Excelente! {$percentual}% da meta 🚀",           // ≥ 139% - Amarelo
                            $consumo >= ($meta * 0.85) => "{$percentual}% da meta ✅",              // 85% - 138% - Cinza
                            $consumo >= ($meta * 0.5) => "Muito abaixo da meta: {$percentual}% ⚠️",             // 50% - 84% - Laranja
                            default => "Crítico: {$percentual}% da meta ❌",                               // < 50% - Vermelho
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
                IconColumn::make('considerar_fechamento')
                    ->label('Considerar Fechamento')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('considerar_calculo_medio')
                    ->label('Considerar Cálculo Médio')
                    ->boolean()
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
            ->defaultSort('data_abastecimento', 'desc')
            ->groups([
                Group::make('veiculo.placa')
                    ->label('Veículo')
                    ->collapsible(),
                Group::make('veiculo.tipoVeiculo.descricao')
                    ->label('Tipo de Veículo')
                    ->collapsible(),
            ])
            ->striped()
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
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
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    FilamentExportBulkAction::make('export'),
                    DeleteBulkAction::make(),
                ]),
                ActionGroup::make([
                    Abastecimentos\Actions\ImportAbastecimentoAction::make(),
                    CreateAction::make(),
                ])->button()
            ]);
    }
}
