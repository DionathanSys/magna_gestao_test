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
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\Summarizers\Average;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ResultadoPeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query->with([
                    'veiculo:id,placa,tipo_veiculo_id',
                    'tipoVeiculo:id,descricao',
                    'abastecimentoInicial',
                    'abastecimentoFinal'
                ]);

                return $query
                    ->withSum('documentos', 'valor_liquido')
                    ->withSum('manutencao', 'custo_total')
                    ->withSum('viagens', 'km_pago')
                    ->withSum('viagens', 'km_rodado')
                    ->withSum('abastecimentos', 'preco_total')
                    ->withSum('abastecimentos', 'quantidade')
                    ->withCount('viagens');
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('resultado_liquido')
                    ->label('Resultado Líquido')
                    ->width('1%')
                    ->money('BRL', 100)
                    ->tooltip('Faturamento - Combustível - Manutenção'),
                ColumnGroup::make('KM', [
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
                        ->description(fn(Models\ResultadoPeriodo $record): string => "{$record->dispersao_km_abastecimento_km_viagem} Km")
                        ->tooltip(fn(): string => 'Diferença entre o KM rodado apurado pelos abastecimentos e o KM rodado registrado nas viagens.')
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('media_km_pago_viagem')
                        ->label('Viagens')
                        ->width('1%')
                        ->description(fn(Models\ResultadoPeriodo $record): string => "{$record->quantidade_viagens} Viagens"),
                ]),
                ColumnGroup::make('Faturamento', [
                    TextColumn::make('documentos_sum_valor_liquido')
                        ->label('Faturamento')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description(fn(Models\ResultadoPeriodo $record): ?string => $record->variacao_faturamento_mes_anterior)
                        ->sum('documentos', 'valor_liquido'),
                    TextColumn::make('faturamento_por_km_rodado')
                        ->label('Fat/Km Rodado')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description('R$/Km')
                        ->tooltip('Faturamento dividido pelo KM Rodado (abastecimentos)'),
                    TextColumn::make('faturamento_por_km_pago')
                        ->label('Fat/Km Pago')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description('R$/Km')
                        ->tooltip('Faturamento dividido pelo KM Pago (viagens)'),
                ]),
                ColumnGroup::make('Manutenção', [
                    TextColumn::make('manutencao_sum_custo_total')
                        ->label('Manutenção')
                        ->width('1%')
                        ->money('BRL')
                        ->sum('manutencao', 'custo_total'),
                    TextColumn::make('percentual_manutencao_faturamento')
                        ->label('% Manut/Fat')
                        ->width('1%')
                        ->formatStateUsing(fn(float $state): string => number_format($state, 2, ',', '.') . '%')
                        ->color(fn(float $state): string => match (true) {
                            $state > 10 => 'danger',
                            $state > 8 => 'warning',
                            default => 'success'
                        })
                        ->tooltip('Percentual de Manutenção sobre o Faturamento'),
                ]),
                ColumnGroup::make('Diesel', [
                    TextColumn::make('abastecimentos_sum_preco_total')
                        ->label('Combustível')
                        ->money('BRL', 100)
                        ->width('1%')
                        ->sum('abastecimentos', 'preco_total'),
                    TextColumn::make('preco_medio_combustivel')
                        ->label('Preço Médio Combustível')
                        ->wrapHeader()
                        ->width('1%')
                        ->money('BRL')
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('consumo_medio_combustivel')
                        ->label('Consumo Médio Combustível')
                        ->wrapHeader()
                        ->suffix(' Km/L')
                        ->description(fn(Models\ResultadoPeriodo $record): ?string => $record->diferenca_meta_consumo)
                        ->numeric(4, ',', '.')
                        ->toggleable(isToggledHiddenByDefault: false),
                ]),
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
                    ->options(VeiculoCacheService::getPlacasAtivasForSelect())
                    ->searchable(),
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
                Group::make('veiculo.modelo')
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
                        ->excludeAttributes(['id', 'km_percorrido', 'created_at', 'updated_at', 'abastecimentos_sum_quantidade', 'manutencao_sum_custo_total', 'documentos_sum_valor_liquido', 'viagens_sum_km_pago', 'viagens_sum_km_rodado', 'abastecimentos_sum_preco_total', 'viagens_count'])
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
                                Log::debug('Iniciando importação de registros para Resultado Período ID: ' . $record->id);
                                $service = new Services\ResultadoPeriodo\ResultadoPeriodoService();
                                $service->importarRegistros($record->id, $data['considerar_periodo']);
                            });
                            notify::success(mensagem: 'Importação concluída com sucesso!');
                        }),
                    BulkAction::make('encerrar_resultado')
                        ->label('Encerrar Resultado')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function (Models\ResultadoPeriodo $record) {
                                $record->update(['status' => StatusDiversosEnum::ENCERRADO->value]);
                            });
                            notify::success();
                        }),
                    BulkAction::make('pendente_resultado')
                        ->label('Marcar como Pendente')
                        ->icon(Heroicon::Clock)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function (Models\ResultadoPeriodo $record) {
                                $record->update(['status' => StatusDiversosEnum::PENDENTE->value]);
                            });
                            notify::success(mensagem: 'Registros marcados como Pendente com sucesso!');
                        }),
                ]),
            ]);
    }
}
