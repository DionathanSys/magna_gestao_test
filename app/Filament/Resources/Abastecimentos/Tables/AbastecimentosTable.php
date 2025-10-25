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
                    ->suffix(' Km/L')
                    ->numeric(4, ',', '.')
                    ->sortable()
                    ->badge()
                    ->tooltip(function (Abastecimento $record): string {
                        $consumo = $record->consumo_medio;
                        $meta = $record->veiculo?->tipo_veiculo?->meta_media ?? 0;
                        
                        if ($consumo === null || $meta === 0) {
                            return 'gray';
                        }
                        
                        return match (true) {
                            $consumo >= ($meta * 1.1) => '110%+ da meta',
                            $consumo >= ($meta * 0.9) => '90%+ da meta',
                            $consumo >= ($meta * 0.7) => '70%+ da meta',
                            default => '70% da meta',
                        };
                    })
                    ->color(function (Abastecimento $record): string {
                        $consumo = $record->consumo_medio;
                        $meta = $record->veiculo?->tipo_veiculo?->meta_media ?? 0;
                        
                        if ($consumo === null || $meta === 0) {
                            return 'gray';
                        }
                        
                        return match (true) {
                            $consumo >= ($meta * 1.1) => 'success',  // 110%+ da meta
                            $consumo >= ($meta * 0.9) => 'primary',  // 90%+ da meta
                            $consumo >= ($meta * 0.7) => 'warning',  // 70%+ da meta
                            default => 'danger',                     // < 70% da meta
                        };
                    })
                    ->formatStateUsing(function ($state, Abastecimento $record): string {
                        if ($state === null) {
                            return 'N/D';
                        }
                        
                        $meta = $record->veiculo?->tipo_veiculo?->meta_media;
                        $formatted = number_format($state, 2, ',', '.');
                        
                        if ($meta) {
                            $percentual = round(($state / $meta) * 100);
                            return "{$formatted} ({$percentual}%)";
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
            ])
            ->striped()
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
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
