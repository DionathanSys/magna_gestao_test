<?php

namespace App\Filament\Resources\ResultadoPeriodos\Tables;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Filament\Resources\ResultadoPeriodos\Actions;
use Filament\Actions\ActionGroup;
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
                // return $query->withSum('documentosFrete as frete', 'valor_liquido')
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
                TextColumn::make('km_percorrido_calculado')
                    ->label('Km Percorrido Abast.')
                    ->width('1%')
                    ->getStateUsing(function ($record) {
                        $kmInicial = $record->abastecimentoInicial?->ultimo_abastecimento_anterior?->quilometragem ?? 0;
                        $kmFinal = $record->abastecimentoFinal?->quilometragem ?? 0;
                        return $kmFinal - $kmInicial;
                    })
                    ->numeric(0, ',', '.'),
                TextColumn::make('viagens_sum_km_rodado')
                    ->label('KM Rodado Viagens')
                    ->numeric(2, ',', '.')
                    ->sum('viagens', 'km_rodado'),
                TextColumn::make('viagens_sum_km_pago')
                    ->label('KM Pago')
                    ->numeric(2, ',', '.')
                    ->sum('viagens', 'km_pago'),
                TextColumn::make('viagens_count')
                    ->label('Qtde. Viagens')
                    ->numeric(2, ',', '.')
                    ->counts('viagens'),
                TextColumn::make('documentos_sum_valor_liquido')
                    ->label('Receita')
                    ->money('BRL', 100)
                    ->sum('documentos', 'valor_liquido'),
                TextColumn::make('abastecimentos_sum_preco_total')
                    ->label('Combustível')
                    ->money('BRL', 100)
                    ->sum('abastecimentos', 'preco_total'),
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
            ->reorderableColumns()
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
                ActionGroup::make([
                    Actions\ImportarRegistrosAction::make(),
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->label('Duplicar')
                        ->icon(Heroicon::DocumentDuplicate)
                        ->schema(fn(Schema $schema) => ResultadoPeriodoResource::form($schema))
                        ->excludeAttributes(['id', 'km_percorrido', 'created_at', 'updated_at', 'documentos_sum_valor_liquido', 'viagens_sum_km_pago', 'viagens_sum_km_rodado', 'abastecimentos_sum_preco_total', 'viagens_count'])
       
                        // ->mutateRecordDataUsing(function (array $data): array {
                        //     dd($data);
                        //     return $data;
                        // })
                        ->successNotificationTitle('Resultado Período duplicado com sucesso!'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
