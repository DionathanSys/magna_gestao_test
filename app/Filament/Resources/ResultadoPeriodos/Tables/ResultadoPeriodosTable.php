<?php

namespace App\Filament\Resources\ResultadoPeriodos\Tables;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Filament\Resources\ResultadoPeriodos\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ResultadoPeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query->with(['veiculo:id,placa', 'tipoVeiculo:id,descricao', 'abastecimentoInicial', 'abastecimentoFinal']);
                // return $query->withSum('documentosFrete as documentos_frete', 'valor_liquido')
                //     ->withSum('viagens', 'km_pago')
                //     ->withSum('abastecimentos', 'preco_total');
            })
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('tipoVeiculo.descricao')
                    ->label('Tipo Veículo')
                    ->width('1%'),
                TextColumn::make('data_inicio')
                    ->label('Data Início')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Data Fim')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('abastecimentoInicial.ultimo_abastecimento_anterior.quilometragem')
                    ->label('Km Inicial')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('abastecimentoFinal.quilometragem')
                    ->label('Km Final')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('abastecimentoFinal.id')
                    ->label('Km Percorrido')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('documentosFrete_sum_valor_liquido')
                ->label('Receita')
                ->money('BRL')
                ->sum('documentosFrete', 'valor_liquido'), // Filament cuida da conversão

            // TextColumn::make('viagens_sum_km_pago')
            //     ->label('KM Pago')
            //     ->numeric(0)
            //     ->suffix(' km')
            //     ->summarize(Sum::make()
            //         ->numeric(0)
            //         ->suffix(' km')
            //         ->label('Total KM')
            //     ),

            // TextColumn::make('abastecimentos_sum_preco_total')
            //     ->label('Combustível')
            //     ->money('BRL')
            //     ->summarize(Sum::make()
            //         ->money('BRL')
            //         ->label('Total Combustível')
            //     ),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->groups([
                Group::make('data_inicio')
                    ->label('Data Início'),
                Group::make('veiculo.placa')
                    ->label('Veículo'),
                Group::make('tipoVeiculo.descricao')
                    ->label('Tipo Veículo'),
            ])
            ->recordActions([
                Actions\ImportarRegistrosAction::make(),
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->icon(Heroicon::DocumentDuplicate)
                    ->schema(fn(Schema $schema) => ResultadoPeriodoResource::form($schema))
                    ->excludeAttributes(['km_percorrido', 'created_at', 'updated_at'])
                    ->successNotificationTitle('Resultado Período duplicado com sucesso!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
