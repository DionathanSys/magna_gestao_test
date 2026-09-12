<?php

namespace App\Filament\Resources\ResultadoPeriodos\Tables;

use App\Enum\StatusDiversosEnum;
use App\Filament\Resources\ResultadoPeriodos\Actions;
use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use App\Services\Veiculo\VeiculoCacheService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ResultadoPeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query->with([
                    'veiculo:id,placa,tipo_veiculo_id',
                    'veiculo.tipoVeiculo:id,descricao,meta_media',
                    'tipoVeiculo:id,descricao',
                    'abastecimentoInicial',
                    'abastecimentoFinal',
                ]);

                return $query
                    ->withSum('documentos', 'valor_liquido')
                    ->withSum('manutencaoLancamentos', 'valor_total_centavos')
                    ->withSum('viagens', 'km_pago')
                    ->withSum('viagens', 'km_rodado')
                    ->withSum('abastecimentos', 'preco_total')
                    ->withSum('abastecimentos', 'quantidade')
                    ->withCount(['viagens', 'documentos', 'abastecimentos']);
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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === StatusDiversosEnum::PENDENTE->value ? 'warning' : 'success'),
                TextColumn::make('diagnostico')
                    ->label('Diagnóstico')
                    ->state(fn (Models\ResultadoPeriodo $record): string => self::diagnostico($record)['label'])
                    ->badge()
                    ->color(fn (Models\ResultadoPeriodo $record): string => self::diagnostico($record)['color'])
                    ->tooltip(fn (Models\ResultadoPeriodo $record): string => self::diagnostico($record)['tooltip']),
                TextColumn::make('resultado_liquido')
                    ->label('Resultado Líquido')
                    ->width('1%')
                    ->money('BRL', 100)
                    ->description(fn (Models\ResultadoPeriodo $record): string => $record->margem_liquida !== null
                        ? 'Margem '.number_format($record->margem_liquida, 1, ',', '.').' %'
                        : 'Margem indisponível')
                    ->color(fn (float $state): string => $state < 0 ? 'danger' : 'success')
                    ->tooltip('Faturamento menos combustível, manutenção e folha de pagamento.'),
                TextColumn::make('custo_por_km')
                    ->label('Custo / KM')
                    ->width('1%')
                    ->formatStateUsing(fn (?float $state): string => $state === null ? 'N/D' : 'R$ '.number_format($state, 2, ',', '.'))
                    ->tooltip('Combustível, manutenção e folha divididos pelo KM rodado apurado nos abastecimentos.'),
                ColumnGroup::make('KM', [
                    TextColumn::make('km_rodado_abastecimento')
                        ->label('KM Abastecimentos')
                        ->width('1%')
                        ->numeric(0, ',', '.')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('km_pago')
                        ->label('KM Pago')
                        ->width('1%')
                        ->numeric(0, ',', '.')
                        ->sum('viagens', 'km_pago'),
                    TextColumn::make('reconciliacao_km')
                        ->label('Reconciliação KM')
                        ->state(fn (Models\ResultadoPeriodo $record): string => self::reconciliacaoKm($record))
                        ->html()
                        ->tooltip(fn (Models\ResultadoPeriodo $record): string => self::reconciliacaoKmTooltip($record)),
                    TextColumn::make('dispersao_km')
                        ->label('Dispersão KM Abast.')
                        ->width('1%')
                        ->numeric(0, ',', '.')
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->percentual_dispersao_km_abastecimento !== null
                            ? number_format($record->percentual_dispersao_km_abastecimento, 2, ',', '.').'% do KM pago'
                            : 'Percentual indisponível')
                        ->tooltip('KM rodado apurado nos abastecimentos menos o KM pago.')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('dispersao_km_real')
                        ->label('Dispersão KM Viagens')
                        ->width('1%')
                        ->numeric(0, ',', '.')
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->percentual_dispersao_km_real !== null
                            ? number_format($record->percentual_dispersao_km_real, 2, ',', '.').'% do KM pago'
                            : 'Percentual indisponível')
                        ->tooltip('KM rodado registrado nas viagens menos o KM pago.')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('media_km_pago_viagem')
                        ->label('Viagens')
                        ->width('1%')
                        ->description(fn (Models\ResultadoPeriodo $record): string => "{$record->quantidade_viagens} Viagens")
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                ColumnGroup::make('Faturamento', [
                    TextColumn::make('documentos_sum_valor_liquido')
                        ->label('Faturamento')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->documentos_count.' documento(s)'.($record->variacao_faturamento_mes_anterior ? ' | '.$record->variacao_faturamento_mes_anterior : ''))
                        ->sum('documentos', 'valor_liquido'),
                    TextColumn::make('faturamento_por_km_rodado')
                        ->label('Fat/KM Abast.')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description('R$/Km')
                        ->tooltip('Faturamento dividido pelo KM rodado apurado nos abastecimentos.')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('faturamento_por_km_pago')
                        ->label('Fat/Km Pago')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description('R$/Km')
                        ->tooltip('Faturamento dividido pelo KM pago nas viagens.')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                ColumnGroup::make('Manutenção', [
                    TextColumn::make('manutencao_lancamentos_sum_valor_total_centavos')
                        ->label('Manutenção')
                        ->width('1%')
                        ->money('BRL', 100)
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->documentos_sum_valor_liquido > 0
                            ? number_format($record->percentual_manutencao_faturamento, 1, ',', '.').'% do faturamento'
                            : 'Sem faturamento')
                        ->sum('manutencaoLancamentos', 'valor_total_centavos'),
                    TextColumn::make('percentual_manutencao_faturamento')
                        ->label('% Manut/Fat')
                        ->width('1%')
                        ->formatStateUsing(fn (float $state): string => number_format($state, 2, ',', '.').'%')
                        ->color(fn (float $state): string => match (true) {
                            $state > 6.5 => 'danger',
                            default => 'success'
                        })
                        ->tooltip('Meta máxima: 6,5% do faturamento.')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                ColumnGroup::make('Custos', [
                    TextColumn::make('abastecimentos_sum_preco_total')
                        ->label('Combustível')
                        ->money('BRL', 100)
                        ->width('1%')
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->documentos_sum_valor_liquido > 0
                            ? number_format((($record->abastecimentos_sum_preco_total ?? 0) / $record->documentos_sum_valor_liquido) * 100, 1, ',', '.').'% do faturamento'
                            : 'Sem faturamento')
                        ->sum('abastecimentos', 'preco_total'),
                    TextColumn::make('folha_pagamento_centavos')
                        ->label('Folha')
                        ->width('1%')
                        ->money('BRL')
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->documentos_sum_valor_liquido > 0
                            ? number_format(((float) $record->folha_pagamento_centavos / ($record->documentos_sum_valor_liquido / 100)) * 100, 1, ',', '.').'% do faturamento'
                            : 'Sem faturamento')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('preco_medio_combustivel')
                        ->label('Preço Médio Combustível')
                        ->wrapHeader()
                        ->width('1%')
                        ->money('BRL')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('consumo_medio_combustivel')
                        ->label('Consumo Médio Combustível')
                        ->wrapHeader()
                        ->suffix(' Km/L')
                        ->description(fn (Models\ResultadoPeriodo $record): ?string => $record->diferenca_meta_consumo)
                        ->numeric(4, ',', '.')
                        ->color(function (?float $state, Models\ResultadoPeriodo $record): string {
                            $meta = $record->veiculo?->tipoVeiculo?->meta_media;

                            return $state === null || ! $meta ? 'gray' : ($state < $meta ? 'danger' : 'success');
                        })
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('desperdicio_litros')
                        ->label('Desperdício Combustível')
                        ->formatStateUsing(fn (?float $state): string => $state === null
                           ? 'N/D'
                           : ($state > 0 ? '+' : '').number_format($state, 2, ',', '.').' L')
                        ->description(fn (Models\ResultadoPeriodo $record): string => $record->desperdicio_valor === null
                           ? 'Valor indisponível'
                           : ($record->desperdicio_valor > 0 ? '+' : ($record->desperdicio_valor < 0 ? '-' : '')).'R$ '.number_format(abs($record->desperdicio_valor), 2, ',', '.'))
                        ->color(fn (?float $state): string => $state === null ? 'gray' : ($state > 0 ? 'danger' : 'success'))
                        ->tooltip('Litros consumidos menos litros estimados pela meta de consumo do tipo do veículo. Valor calculado pelo preço médio do litro.')
                        ->toggleable(isToggledHiddenByDefault: true),
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
                    ->multiple()
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
                Group::make('tipoVeiculo.descricao')
                    ->label('Tipo Veículo'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Actions\ImportarRegistrosAction::make(),
                    Actions\EncerrarResultadoAction::make(),
                    Actions\ReabrirResultadoAction::make(),
                    Action::make('analise_veiculo')
                        ->label('Análise do veículo')
                        ->icon('heroicon-o-chart-bar-square')
                        ->url(fn (Models\ResultadoPeriodo $record): string => ResultadoPeriodoResource::getUrl('analise', ['record' => $record])),
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->label('Duplicar')
                        ->icon(Heroicon::DocumentDuplicate)
                        ->schema(fn (Schema $schema) => ResultadoPeriodoResource::form($schema))
                        ->excludeAttributes(['id', 'km_percorrido', 'created_at', 'updated_at', 'abastecimentos_sum_quantidade', 'manutencao_lancamentos_sum_valor_total_centavos', 'documentos_sum_valor_liquido', 'viagens_sum_km_pago', 'viagens_sum_km_rodado', 'abastecimentos_sum_preco_total', 'viagens_count'])
                        ->successNotificationTitle('Resultado Período duplicado com sucesso!'),

                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Actions\CriarResultadoPeriodoBulkAction::make(),
                    BulkAction::make('vincular_registros_resultado')
                        ->label('Buscar e vincular registros')
                        ->icon(Heroicon::ArrowUpOnSquare)
                        ->requiresConfirmation()
                        ->modalDescription('Somente registros sem vínculo dos veículos selecionados serão incluídos. Períodos encerrados serão ignorados.')
                        ->schema(function (Schema $schema): Schema {
                            return $schema
                                ->columns(1)
                                ->components([
                                    Toggle::make('considerar_periodo')
                                        ->label('Restringir à data do período')
                                        ->helperText('Desative para buscar todos os registros sem vínculo do veículo, independentemente da data.')
                                        ->default(true),
                                ]);
                        })
                        ->action(function (Collection $records, array $data) {
                            $records->each(function (Models\ResultadoPeriodo $record) use ($data) {
                                if ($record->status !== StatusDiversosEnum::PENDENTE->value) {
                                    return;
                                }

                                Log::debug('Iniciando importação de registros para Resultado Período ID: '.$record->id);
                                $service = new Services\ResultadoPeriodo\ResultadoPeriodoService;
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

    private static function reconciliacaoKm(Models\ResultadoPeriodo $record): string
    {
        $formatarKm = fn (?float $valor): string => $valor === null ? 'N/D' : number_format($valor, 0, ',', '.').' km';
        $formatarDiferenca = fn (?float $valor): string => $valor === null
            ? 'N/D'
            : ($valor > 0 ? '+' : '').number_format($valor, 0, ',', '.').' km';
        $formatarPercentual = fn (?float $valor): string => $valor === null
            ? 'N/D'
            : ($valor > 0 ? '+' : '').number_format($valor, 2, ',', '.').'%';

        return sprintf(
            '<span>Viagens: %s (%s)</span><br><span>Abastec.: %s (%s)</span><br><span>Fontes: %s</span>',
            $formatarDiferenca($record->dispersao_km_real),
            $formatarPercentual($record->percentual_dispersao_km_real),
            $formatarDiferenca($record->dispersao_km),
            $formatarPercentual($record->percentual_dispersao_km_abastecimento),
            $formatarDiferenca($record->dispersao_km_abastecimento_km_viagem),
        );
    }

    private static function reconciliacaoKmTooltip(Models\ResultadoPeriodo $record): string
    {
        $formatarKm = fn (?float $valor): string => $valor === null ? 'N/D' : number_format($valor, 0, ',', '.').' km';

        return implode(PHP_EOL, [
            'KM pago: '.$formatarKm($record->km_pago),
            'KM rodado nas viagens: '.$formatarKm($record->km_rodado_viagens),
            'KM rodado nos abastecimentos: '.$formatarKm($record->km_rodado_abastecimento),
            'Viagens vinculadas: '.$record->viagens_count,
            'Abastecimentos vinculados: '.$record->abastecimentos_count,
        ]);
    }

    private static function diagnostico(Models\ResultadoPeriodo $record): array
    {
        if ($record->km_rodado_abastecimento === null) {
            return [
                'label' => 'Dados incompletos',
                'color' => 'gray',
                'tooltip' => 'Não há abastecimento inicial ou final suficiente para apurar KM, consumo, custo por KM e dispersão por abastecimentos.',
            ];
        }

        $criticos = [];
        $atencoes = [];
        $metaConsumo = $record->veiculo?->tipoVeiculo?->meta_media;
        $consumo = $record->consumo_medio_combustivel;
        $percentualCombustivel = ($record->documentos_sum_valor_liquido ?? 0) > 0
            ? (($record->abastecimentos_sum_preco_total ?? 0) / $record->documentos_sum_valor_liquido) * 100
            : null;
        $percentualFolha = ($record->documentos_sum_valor_liquido ?? 0) > 0
            ? ((float) $record->folha_pagamento_centavos / ($record->documentos_sum_valor_liquido / 100)) * 100
            : null;

        if ($record->resultado_liquido < 0) {
            $criticos[] = 'resultado líquido negativo';
        }

        $maiorDispersao = max(
            abs($record->percentual_dispersao_km_real ?? 0),
            abs($record->percentual_dispersao_km_abastecimento ?? 0),
        );

        if ($maiorDispersao > 5) {
            $criticos[] = 'dispersão de KM acima de 5%';
        } elseif ($maiorDispersao > 2) {
            $atencoes[] = 'dispersão de KM acima da meta de 2%';
        }

        if ($metaConsumo && $consumo !== null) {
            if ($consumo < $metaConsumo * 0.85) {
                $criticos[] = 'consumo mais de 15% abaixo da meta';
            } elseif ($consumo < $metaConsumo) {
                $atencoes[] = 'consumo abaixo da meta';
            }
        }

        if ($percentualCombustivel !== null && $percentualCombustivel > 33) {
            $atencoes[] = 'combustível acima de 33% do faturamento';
        }

        if ($record->percentual_manutencao_faturamento > 6.5) {
            $atencoes[] = 'manutenção acima de 6,5% do faturamento';
        }

        if ($percentualFolha !== null && $percentualFolha > 15.5) {
            $atencoes[] = 'folha acima de 15,5% do faturamento';
        }

        if ($criticos !== []) {
            return [
                'label' => 'Crítico',
                'color' => 'danger',
                'tooltip' => implode('; ', $criticos),
            ];
        }

        if ($atencoes !== []) {
            return [
                'label' => 'Atenção',
                'color' => 'warning',
                'tooltip' => implode('; ', $atencoes),
            ];
        }

        return [
            'label' => 'Dentro das metas',
            'color' => 'success',
            'tooltip' => 'Resultado, consumo, dispersão e composição de custos dentro das metas monitoradas.',
        ];
    }
}
