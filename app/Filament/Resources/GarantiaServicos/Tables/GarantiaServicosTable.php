<?php

namespace App\Filament\Resources\GarantiaServicos\Tables;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\GarantiaServico;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GarantiaServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'veiculo:id,placa',
                'servico:id,codigo,descricao',
                'ordemServico:id',
                'ordemServicoAnterior:id',
            ]))
            ->defaultSort('data_execucao', 'desc')
            ->columns([
                IconColumn::make('em_garantia')
                    ->label('Alerta')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servico.descricao')
                    ->label('Serviço')
                    ->searchable()
                    ->description(fn (GarantiaServico $record): ?string => $record->servico?->codigo),
                TextColumn::make('posicao')
                    ->label('Posição')
                    ->placeholder('N/A')
                    ->badge(),
                TextColumn::make('ordem_servico_id')
                    ->label('OS Atual')
                    ->url(fn (GarantiaServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->ordem_servico_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('ordem_servico_anterior_id')
                    ->label('OS Anterior')
                    ->placeholder('Primeira execução')
                    ->url(fn (GarantiaServico $record): ?string => $record->ordem_servico_anterior_id
                        ? OrdemServicoResource::getUrl('custom', ['record' => $record->ordem_servico_anterior_id])
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('km_execucao')
                    ->label('Km Atual')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('km_execucao_anterior')
                    ->label('Km Anterior')
                    ->numeric(0, ',', '.')
                    ->placeholder('N/A'),
                TextColumn::make('km_durabilidade')
                    ->label('Durou km')
                    ->numeric(0, ',', '.')
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('dias_durabilidade')
                    ->label('Durou dias')
                    ->numeric(0, ',', '.')
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('garantia_km_aplicada')
                    ->label('Garantia km')
                    ->numeric(0, ',', '.')
                    ->toggleable(),
                TextColumn::make('garantia_dias_aplicada')
                    ->label('Garantia dias')
                    ->numeric(0, ',', '.')
                    ->toggleable(),
                TextColumn::make('data_execucao')
                    ->label('Execução')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('motivo_alerta')
                    ->label('Motivo')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('em_garantia')
                    ->label('Status')
                    ->options([
                        1 => 'Em garantia',
                        0 => 'Fora da garantia',
                    ]),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('servico_id')
                    ->label('Serviço')
                    ->relationship('servico', 'descricao')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('posicao')
                    ->label('Posição')
                    ->options(fn (): array => GarantiaServico::query()
                        ->whereNotNull('posicao')
                        ->distinct()
                        ->orderBy('posicao')
                        ->pluck('posicao', 'posicao')
                        ->all()),
                Filter::make('data_execucao')
                    ->form([
                        DatePicker::make('data_inicio')->label('Data inicial'),
                        DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_execucao', '>=', $date))
                            ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_execucao', '<=', $date));
                    }),
            ]);
    }
}
