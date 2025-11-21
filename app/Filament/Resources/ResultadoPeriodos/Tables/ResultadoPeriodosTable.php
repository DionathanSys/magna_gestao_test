<?php

namespace App\Filament\Resources\ResultadoPeriodos\Tables;

use App\{Models, Services};
use App\Enum\StatusDiversosEnum;
use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Filament\Resources\ResultadoPeriodos\Actions;
use App\Services\Veiculo\VeiculoCacheService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

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
            ->persistFiltersInSession()
            ->reorderableColumns()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusDiversosEnum::toSelectArray())
                    ->default(StatusDiversosEnum::PENDENTE->value),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->options(VeiculoCacheService::getPlacasAtivasForSelect()),
                DateRangeFilter::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->drops(DropDirection::AUTO)
                    ->icon('heroicon-o-backspace')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->firstDayOfWeek(0),
            ])
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
                    BulkAction::make('vincular_registros_resultado')
                        ->label('Importar Registros')
                        ->icon(Heroicon::ArrowUpOnSquare)
                        ->schema(function (Schema $schema): Schema {
                            return $schema
                                ->columns(1)
                                ->components([
                                    Toggle::make('considerar_periodo')
                                        ->label('Considerar Período')
                                        ->helperText('Se ativado, apenas os registros dentro do período definido serão importados.')
                                        ->default(true),
                                ]);
                        })
                        ->action(function (Collection $records, array $data) {
                            $records->each(function (Models\ResultadoPeriodo $record) use ($data) {
                                $service = new Services\ResultadoPeriodo\ResultadoPeriodoService();
                                $service->importarRegistros($record->id, $data['considerar_periodo']);
                            });
                            notify::success(mensagem: 'Importação concluída com sucesso!');
                        }),
                ]),
            ]);
    }
}
