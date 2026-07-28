<?php

namespace App\Filament\Resources\AnaliseServicosOrdemServicos\Tables;

use App\Enum\OrdemServico\PosicaoItemOrdemServicoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\ItemOrdemServico;
use App\Models\Servico;
use App\Models\Veiculo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnaliseServicosOrdemServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'comentarios.creator:id,name',
                'creator:id,name',
                'ordemServico.comentarios.creator:id,name',
                'ordemServico.parceiro:id,nome',
                'ordemServico.sankhyaId:id,ordem_servico_id,ordem_sankhya_id',
                'ordemServico.veiculo:id,placa',
                'planoPreventivo:id,descricao',
                'servico:id,codigo,descricao',
            ]))
            ->columns([
                TextColumn::make('ordem_servico_id')
                    ->label('OS')
                    ->sortable()
                    ->searchable()
                    ->url(fn (ItemOrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->ordem_servico_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('ordemServico.sankhyaId.ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ordemServico.veiculo.placa')
                    ->label('Placa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordemServico.data_inicio')
                    ->label('Dt. Abertura')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('ordemServico.data_fim')
                    ->label('Dt. Fechamento')
                    ->date('d/m/Y')
                    ->placeholder('Aberta')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ordemServico.status')
                    ->label('Status OS')
                    ->badge(),
                TextColumn::make('servico.descricao')
                    ->label('Serviço')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->description(fn (ItemOrdemServico $record): ?string => $record->servico?->codigo),
                TextColumn::make('posicao')
                    ->label('Posição')
                    ->placeholder('N/A')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status Serviço')
                    ->badge(),
                TextColumn::make('observacao')
                    ->label('Observação')
                    ->wrap()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('ordemServico.parceiro.nome')
                    ->label('Fornecedor')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comentarios.conteudo')
                    ->label('Comentários Serviço')
                    ->html()
                    ->wrap()
                    ->size(TextSize::ExtraSmall)
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->toggleable(),
                TextColumn::make('ordemServico.comentarios.conteudo')
                    ->label('Comentários OS')
                    ->html()
                    ->wrap()
                    ->size(TextSize::ExtraSmall)
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->filters([
                Filter::make('data_abertura')
                    ->label('Dt. Abertura OS')
                    ->form([
                        DatePicker::make('data_inicio')->label('Data inicial'),
                        DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->whereHas('ordemServico', fn (Builder $query) => $query
                        ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_inicio', '>=', $date))
                        ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_inicio', '<=', $date)))),
                Filter::make('data_fechamento')
                    ->label('Dt. Fechamento OS')
                    ->form([
                        DatePicker::make('data_inicio')->label('Data inicial'),
                        DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->whereHas('ordemServico', fn (Builder $query) => $query
                        ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_fim', '>=', $date))
                        ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_fim', '<=', $date)))),
                SelectFilter::make('veiculo_id')
                    ->label('Placa')
                    ->options(fn (): array => Veiculo::query()->orderBy('placa')->pluck('placa', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['values'] ?? null),
                        fn (Builder $query) => $query->whereHas('ordemServico', fn (Builder $query) => $query->whereIn('veiculo_id', $data['values']))
                    ))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('servico_id')
                    ->label('Serviço')
                    ->options(fn (): array => Servico::query()->orderBy('descricao')->pluck('descricao', 'id')->all())
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('posicao')
                    ->label('Posição')
                    ->options(PosicaoItemOrdemServicoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('status')
                    ->label('Status Serviço')
                    ->options(StatusOrdemServicoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('status_os')
                    ->label('Status OS')
                    ->options(StatusOrdemServicoEnum::toSelectArray())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['values'] ?? null),
                        fn (Builder $query) => $query->whereHas('ordemServico', fn (Builder $query) => $query->whereIn('status', $data['values']))
                    ))
                    ->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('comentarios')
                        ->label('Comentários')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->slideOver()
                        ->modalSubmitAction(false)
                        ->schema([
                            Section::make('Comentários do Serviço')
                                ->schema([
                                    RepeatableEntry::make('comentarios')
                                        ->label('')
                                        ->placeholder('Nenhum comentário no serviço')
                                        ->schema([
                                            TextEntry::make('conteudo')
                                                ->label('Comentário')
                                                ->html(),
                                            TextEntry::make('creator.name')
                                                ->label('Criado por')
                                                ->placeholder('N/A'),
                                            TextEntry::make('created_at')
                                                ->label('Criado em')
                                                ->dateTime('d/m/Y H:i'),
                                        ]),
                                ]),
                            Section::make('Comentários da OS')
                                ->schema([
                                    RepeatableEntry::make('ordemServico.comentarios')
                                        ->label('')
                                        ->placeholder('Nenhum comentário na OS')
                                        ->schema([
                                            TextEntry::make('conteudo')
                                                ->label('Comentário')
                                                ->html(),
                                            TextEntry::make('creator.name')
                                                ->label('Criado por')
                                                ->placeholder('N/A'),
                                            TextEntry::make('created_at')
                                                ->label('Criado em')
                                                ->dateTime('d/m/Y H:i'),
                                        ]),
                                ]),
                        ]),
                    Action::make('abrir_os')
                        ->label('Abrir OS')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (ItemOrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->ordem_servico_id]))
                        ->openUrlInNewTab(),
                ])->icon('heroicon-o-bars-3-center-left'),
            ], RecordActionsPosition::BeforeColumns)
            ->striped();
    }
}
