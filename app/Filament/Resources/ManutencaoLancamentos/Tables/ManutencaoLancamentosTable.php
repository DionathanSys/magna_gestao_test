<?php

namespace App\Filament\Resources\ManutencaoLancamentos\Tables;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\ManutencaoLancamento;
use App\Models\OrdemServico;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManutencaoLancamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['veiculo:id,placa', 'ordemServico.sankhyaId']))
            ->defaultSort('data_negociacao', 'desc')
            ->columns([
                TextColumn::make('data_negociacao')
                    ->label('Dt. Neg.')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->badge()
                    ->sortable(),
                TextColumn::make('ordemServico.id')
                    ->label('OS Interna')
                    ->url(fn (ManutencaoLancamento $record): ?string => $record->ordem_servico_id
                        ? OrdemServicoResource::getUrl('custom', ['record' => $record->ordem_servico_id])
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('Pendente'),
                TextColumn::make('tipo_vinculo')
                    ->label('Vínculo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'automatico' => 'Automático',
                        'manual' => 'Manual',
                        default => 'Sem vínculo',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'automatico' => 'success',
                        'manual' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('produto')
                    ->label('Produto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('quantidade')
                    ->label('Qtde.')
                    ->numeric(4, ',', '.'),
                TextColumn::make('valor_total_centavos')
                    ->label('Vlr. Total')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('valor_unitario_centavos')
                    ->label('Vlr. Unitário')
                    ->money('BRL', 100)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('nr_unico')
                    ->label('Nr. Único')
                    ->searchable(),
                TextColumn::make('sequencia')
                    ->label('Sequência')
                    ->searchable(),
                TextColumn::make('nr_os_nf')
                    ->label('Nr. OS/NF')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parceiro')
                    ->label('Parceiro')
                    ->toggleable(),
                TextColumn::make('grupo_produto')
                    ->label('Grupo Produto')
                    ->toggleable(),
                TextColumn::make('origem')
                    ->label('Origem')
                    ->toggleable(),
                TextColumn::make('deleted_at')
                    ->label('Removido em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->options([
                        'Corretiva' => 'Corretiva',
                        'Preventiva' => 'Preventiva',
                    ]),
                SelectFilter::make('veiculo_id')
                    ->label('Placa')
                    ->relationship('veiculo', 'placa'),
                SelectFilter::make('status_vinculo')
                    ->label('Status vínculo')
                    ->options([
                        'vinculados' => 'Vinculados',
                        'pendentes' => 'Pendentes',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'vinculados' => $query->whereNotNull('ordem_servico_id'),
                            'pendentes' => $query->whereNull('ordem_servico_id'),
                            default => $query,
                        };
                    }),
                Filter::make('data_negociacao')
                    ->form([
                        DatePicker::make('data_inicio')->label('Data inicial'),
                        DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_negociacao', '>=', $date))
                            ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_negociacao', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('conciliar_automaticamente')
                    ->label('Conciliar')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (ManutencaoLancamento $record): bool => $record->ordem_servico_id === null)
                    ->action(function (ManutencaoLancamento $record, ManutencaoLancamentoVinculoService $service): void {
                        $service->conciliarAutomaticamente($record);
                    }),
                Action::make('vincular_os')
                    ->label('Vincular OS')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Select::make('ordem_servico_id')
                            ->label('Ordem de serviço')
                            ->searchable()
                            ->required()
                            ->options(function (ManutencaoLancamento $record): array {
                                return OrdemServico::query()
                                    ->where('veiculo_id', $record->veiculo_id)
                                    ->with('sankhyaId')
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(function (OrdemServico $ordemServico): array {
                                        $osSankhya = $ordemServico->sankhyaId
                                            ->pluck('ordem_sankhya_id')
                                            ->filter()
                                            ->join(', ');

                                        $label = 'OS #'.$ordemServico->id;

                                        if ($osSankhya !== '') {
                                            $label .= ' | ERP: '.$osSankhya;
                                        }

                                        return [$ordemServico->id => $label];
                                    })
                                    ->all();
                            })
                            ->helperText('Somente ordens do mesmo veículo são exibidas.'),
                    ])
                    ->action(function (ManutencaoLancamento $record, array $data, ManutencaoLancamentoVinculoService $service): void {
                        $ordemServico = OrdemServico::query()
                            ->where('veiculo_id', $record->veiculo_id)
                            ->findOrFail($data['ordem_servico_id']);

                        $service->vincular($record, $ordemServico, 'manual');
                    }),
                Action::make('desvincular_os')
                    ->label('Desvincular')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ManutencaoLancamento $record): bool => $record->ordem_servico_id !== null)
                    ->action(function (ManutencaoLancamento $record, ManutencaoLancamentoVinculoService $service): void {
                        $service->desvincular($record);
                    }),
            ])
            ->toolbarActions([]);
    }
}
