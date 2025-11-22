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
use Carbon\Carbon;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ResultadoPeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {

                $query = $query->with([
                    'veiculo:id,placa', 'tipoVeiculo:id,descricao', 'abastecimentoInicial', 'abastecimentoFinal'
                ]);

                return $query
                    ->withSum('documentos', 'valor_liquido')
                    ->withSum('viagens', 'km_pago')
                    ->withSum('viagens', 'km_rodado')
                    ->withSum('abastecimentos', 'preco_total')
                    ->withCount('viagens');
            })
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('tipoVeiculo.descricao')
                    ->label('Tipo Veículo')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('periodo')
                    ->label('Período')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('km_rodado_abastecimento')
                    ->label('Km Rodado')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_pago')
                    ->label('KM Pago')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sum('viagens', 'km_pago'),
                TextColumn::make('dispersao_km')
                    ->label('Dispersão KM')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_rodado_viagens')
                    ->label('KM Rodado Viagem')
                    ->width('1%')
                    ->wrapHeader()
                    ->numeric(0, ',', '.')
                    ->description(fn (Models\ResultadoPeriodo $record): string => "{$record->dispersao_km_abastecimento_km_viagem} Km")
                    ->tooltip(fn(): string => 'Diferença entre o KM rodado apurado pelos abastecimentos e o KM rodado registrado nas viagens.'),
                TextColumn::make('media_km_pago_viagem')
                    ->label('Viagens')
                    ->width('1%')
                    ->description(fn (Models\ResultadoPeriodo $record): string => "{$record->quantidade_viagens} Viagens"),
                TextColumn::make('documentos_sum_valor_liquido')
                    ->label('Faturamento')
                    ->width('1%')
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
                DateRangeFilter::make('data_inicio')
                    ->label('Dt. Início')
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
