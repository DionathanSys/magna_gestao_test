<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Tables;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
use App\Filament\Resources\OrdemServicos\Actions\EncerrarOrdemServicoAction;
use App\Models\Colaborador;
use App\Models\OrdemServico;
use App\Services\NotificacaoService as notify;
use App\Services\Oficina\OrdemServicoApontamentoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Throwable;

class OrdemServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('id')
                            ->label('OS')
                            ->formatStateUsing(fn ($state, OrdemServico $record): string => 'OS #'.$state.' - '.strtoupper((string) ($record->veiculo?->placa ?? '-')))
                            ->icon('heroicon-o-truck')
                            ->size(TextSize::Large)
                            ->weight('bold')
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('status')
                            ->badge()
                            ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                                'PENDENTE' => 'warning',
                                'EXECUÇÃO' => 'info',
                                'CONCLUÍDO' => 'success',
                                'CANCELADO' => 'danger',
                                default => 'primary',
                            }),
                    ]),
                    TextColumn::make('execucao_agora')
                        ->label('Andamento')
                        ->state(fn (OrdemServico $record): string => $record->apontamentosAbertosOficina->isNotEmpty() ? 'EM EXECUÇÃO AGORA' : 'PENDENTE / SEM MECÂNICO')
                        ->badge()
                        ->color(fn (OrdemServico $record): string => $record->apontamentosAbertosOficina->isNotEmpty() ? 'info' : 'warning')
                        ->icon(fn (OrdemServico $record): string => $record->apontamentosAbertosOficina->isNotEmpty() ? 'heroicon-o-bolt' : 'heroicon-o-clock'),
                    ToggleColumn::make('veiculo_na_oficina')
                        ->label('Veículo na oficina')
                        ->onIcon('heroicon-o-building-storefront')
                        ->offIcon('heroicon-o-truck')
                        ->onColor('success')
                        ->offColor('gray'),
                    TextColumn::make('itens_count')
                        ->counts('itens')
                        ->label('Serviços')
                        ->formatStateUsing(fn ($state): string => $state.' serviço(s)')
                        ->icon('heroicon-o-list-bullet'),
                    TextColumn::make('data_inicio')
                        ->label('Abertura')
                        ->icon('heroicon-o-calendar-days')
                        ->dateTime('d/m/Y H:i')
                        ->sortable(),
                    TextColumn::make('trabalhando')
                        ->label('Trabalhando')
                        ->icon('heroicon-o-user-group')
                        ->state(fn (OrdemServico $record): string => $record->apontamentosAbertosOficina
                            ->pluck('colaborador.nome')
                            ->filter()
                            ->join(', '))
                        ->placeholder('Ninguém trabalhando agora')
                        ->wrap(),
                ])->space(2),
            ])
            ->contentGrid([
                'xl' => 2,
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusOrdemServicoEnum::toSelectArray())
                    ->multiple(),
            ])
            ->recordActions([
                self::servicosAction(),
                self::iniciarAction(),
                self::encerrarAction(),
                ActionGroup::make([
                    EncerrarOrdemServicoAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                    self::ajustarHorariosAction(),
                    self::removerApontamentoAbertoAction(),
                    self::relatorioAction(),
                    ViewAction::make()
                        ->label('Detalhes')
                        ->url(fn (OrdemServico $record): string => OrdemServicoResource::getUrl('view', ['record' => $record])),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (): bool => Auth::user()->is_admin),
                ])
                    ->label('Mais')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->button()
                    ->size(Size::Small),
            ], RecordActionsPosition::AfterContent)
            ->poll('30s')
            ->striped();
    }

    public static function servicosAction(): Action
    {
        return Action::make('servicos')
            ->label('Serviços')
            ->icon('heroicon-o-list-bullet')
            ->button()
            ->size(Size::Small)
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading(fn (OrdemServico $record): string => 'Serviços da OS #'.$record->id)
            ->infolist([
                RepeatableEntry::make('itens')
                    ->label('Serviços')
                    ->columnSpanFull()
                    ->columns(12)
                    ->table([
                        TableColumn::make('Código')->hiddenHeaderLabel(),
                        TableColumn::make('Serviço'),
                        TableColumn::make('Posição'),
                        TableColumn::make('Observação'),
                        TableColumn::make('Status'),
                    ])
                    ->schema([
                        TextEntry::make('servico.codigo')
                            ->columnSpan(1),
                        TextEntry::make('servico.descricao')
                            ->columnSpan(4),
                        TextEntry::make('posicao')
                            ->columnSpan(1)
                            ->placeholder(''),
                        TextEntry::make('observacao')
                            ->columnSpan(4)
                            ->prefix('Obs: ')
                            ->placeholder('Sem observações'),
                        TextEntry::make('status')
                            ->columnSpan(2)
                            ->badge()
                            ->color('primary'),
                    ]),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->action(fn (): null => null);
    }

    public static function iniciarAction(): Action
    {
        return Action::make('iniciar')
            ->label('Iniciar')
            ->icon('heroicon-o-play-circle')
            ->button()
            ->size(Size::Small)
            ->color('info')
            ->modalWidth(Width::Large)
            ->form(fn (OrdemServico $record): array => [
                self::colaboradorSelect(),
                DateTimePicker::make('iniciado_em')
                    ->label('Início')
                    ->seconds(false)
                    ->default(now())
                    ->minDate($record->data_inicio)
                    ->maxDate(fn () => Auth::user()->is_admin ? null : now())
                    ->required(),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    app(OrdemServicoApontamentoService::class)->iniciar(
                        $record,
                        (string) $data['codigo'],
                        $data['iniciado_em'],
                        Auth::user()->is_admin,
                    );

                    notify::success(mensagem: 'Trabalho iniciado com sucesso.');
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }

    public static function encerrarAction(): Action
    {
        return Action::make('encerrar_trabalho')
            ->label('Encerrar')
            ->icon('heroicon-o-stop-circle')
            ->button()
            ->size(Size::Small)
            ->color('success')
            ->modalWidth(Width::ScreenTwoExtraLarge)
            ->form(fn (OrdemServico $record): array => [
                Placeholder::make('resumo_servicos')
                    ->label('Serviços da OS')
                    ->content(fn (): HtmlString => self::servicosResumoHtml($record))
                    ->columnSpanFull(),
                self::colaboradorSelect($record, somenteComApontamentoAberto: true),
                DateTimePicker::make('encerrado_em')
                    ->label('Fim')
                    ->seconds(false)
                    ->default(now())
                    ->minDate($record->data_inicio)
                    ->maxDate(fn () => Auth::user()->is_admin ? null : now())
                    ->required(),
                CheckboxList::make('item_ids')
                    ->label('Serviços executados nesta janela')
                    ->options($record->itens->mapWithKeys(fn ($item): array => [
                        $item->id => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').$item->servico?->descricao),
                    ])->all())
                    ->columns(1)
                    ->required(),
                Toggle::make('concluir_servicos')
                    ->label('Marcar os serviços selecionados como concluídos')
                    ->helperText('Use somente se os serviços executados nesta janela foram finalizados pelo mecânico.'),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    app(OrdemServicoApontamentoService::class)->encerrar(
                        $record,
                        (string) $data['codigo'],
                        $data['encerrado_em'],
                        $data['item_ids'] ?? [],
                        Auth::user()->is_admin,
                        (bool) ($data['concluir_servicos'] ?? false),
                    );

                    notify::success(mensagem: 'Trabalho encerrado com sucesso.');
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }

    private static function colaboradorSelect(?OrdemServico $record = null, bool $somenteComApontamentoAberto = false): Select
    {
        return Select::make('codigo')
            ->label('Responsável')
            ->options(fn (): array => Colaborador::query()
                ->where('ativo', true)
                ->where('tipo', 'MECANICO')
                ->when(
                    $somenteComApontamentoAberto && $record,
                    fn ($query) => $query->whereHas('apontamentosOficina', fn ($query) => $query
                        ->where('ordem_servico_id', $record->id)
                        ->whereNull('encerrado_em'))
                )
                ->orderBy('nome')
                ->get()
                ->mapWithKeys(fn (Colaborador $colaborador): array => [
                    $colaborador->codigo => trim($colaborador->codigo.' - '.$colaborador->nome),
                ])
                ->all())
            ->searchable()
            ->preload()
            ->native(false)
            ->required();
    }

    private static function servicosResumoHtml(OrdemServico $record): HtmlString
    {
        $record->loadMissing(['itens.servico', 'itens.apontamentosOficina.colaborador']);

        $linhas = $record->itens->map(function ($item): string {
            $servico = e(trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').($item->servico?->descricao ?? 'Serviço não informado')));
            $status = e((string) ($item->status?->value ?? $item->status ?? '-'));
            $trabalhadores = $item->apontamentosOficina
                ->map(fn ($apontamento): string => e(trim(($apontamento->colaborador?->nome ?? 'Colaborador não informado').' ('.($apontamento->iniciado_em?->format('d/m H:i') ?? '-').' - '.($apontamento->encerrado_em?->format('d/m H:i') ?? 'aberto').')')))
                ->filter()
                ->join('<br>');

            return '<tr><td class="px-3 py-2 align-top">'.$servico.'</td><td class="px-3 py-2 align-top">'.$status.'</td><td class="px-3 py-2 align-top">'.($trabalhadores ?: 'Sem apontamentos').'</td></tr>';
        })->join('');

        return new HtmlString('<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700"><table class="w-full text-sm"><thead><tr class="bg-gray-50 dark:bg-gray-800"><th class="px-3 py-2 text-left">Serviço</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Quem trabalhou</th></tr></thead><tbody>'.$linhas.'</tbody></table></div>');
    }

    public static function relatorioAction(): Action
    {
        return Action::make('relatorio_oficina')
            ->label('Relatório')
            ->icon('heroicon-o-document-text')
            ->url(fn (OrdemServico $record): string => route('oficina.ordem-servico.relatorio', $record))
            ->openUrlInNewTab();
    }

    public static function ajustarHorariosAction(): Action
    {
        return Action::make('ajustar_horarios')
            ->label('Ajustar horários')
            ->icon('heroicon-o-clock')
            ->visible(fn (): bool => Auth::user()->is_admin)
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(fn (OrdemServico $record): array => [
                'apontamentos' => $record->apontamentosOficina()
                    ->whereNotNull('encerrado_em')
                    ->with('colaborador')
                    ->orderBy('iniciado_em')
                    ->get()
                    ->map(fn ($apontamento): array => [
                        'id' => $apontamento->id,
                        'colaborador' => trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo.' - ' : '').($apontamento->colaborador?->nome ?? '')),
                        'iniciado_em' => $apontamento->iniciado_em,
                        'encerrado_em' => $apontamento->encerrado_em,
                    ])
                    ->all(),
            ])
            ->form(fn (OrdemServico $record): array => [
                Repeater::make('apontamentos')
                    ->label('Apontamentos encerrados')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columns(3)
                    ->schema([
                        Hidden::make('id')
                            ->required(),
                        TextInput::make('colaborador')
                            ->label('Responsável')
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('iniciado_em')
                            ->label('Início')
                            ->seconds(false)
                            ->minDate($record->data_inicio)
                            ->required(),
                        DateTimePicker::make('encerrado_em')
                            ->label('Fim')
                            ->seconds(false)
                            ->minDate($record->data_inicio)
                            ->required(),
                    ]),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    foreach ($data['apontamentos'] ?? [] as $apontamentoData) {
                        $apontamento = $record->apontamentosOficina()
                            ->whereNotNull('encerrado_em')
                            ->findOrFail($apontamentoData['id']);

                        $iniciadoEm = Carbon::parse($apontamentoData['iniciado_em']);
                        $encerradoEm = Carbon::parse($apontamentoData['encerrado_em']);

                        if ($record->data_inicio && $iniciadoEm->lessThan($record->data_inicio)) {
                            throw new \InvalidArgumentException('A hora inicial não pode ser menor que a data/hora de abertura da OS.');
                        }

                        if ($record->data_inicio && $encerradoEm->lessThan($record->data_inicio)) {
                            throw new \InvalidArgumentException('A hora final não pode ser menor que a data/hora de abertura da OS.');
                        }

                        if ($encerradoEm->lessThan($iniciadoEm)) {
                            throw new \InvalidArgumentException('A hora final não pode ser menor que a hora inicial.');
                        }

                        $apontamento->update([
                            'iniciado_em' => $iniciadoEm,
                            'encerrado_em' => $encerradoEm,
                        ]);
                    }

                    notify::success(mensagem: 'Horários ajustados com sucesso.');
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }

    public static function removerApontamentoAbertoAction(): Action
    {
        return Action::make('remover_apontamento_aberto')
            ->label('Remover apontamento aberto')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (): bool => Auth::user()->is_admin)
            ->requiresConfirmation()
            ->modalHeading('Remover apontamento em aberto')
            ->modalDescription('Selecione o apontamento aberto que deve ser removido. Esta ação não encerra o trabalho, apenas remove o registro aberto.')
            ->form(fn (OrdemServico $record): array => [
                CheckboxList::make('apontamento_ids')
                    ->label('Apontamentos em aberto')
                    ->options($record->apontamentosAbertosOficina()
                        ->with('colaborador')
                        ->orderBy('iniciado_em')
                        ->get()
                        ->mapWithKeys(fn ($apontamento): array => [
                            $apontamento->id => sprintf(
                                '%s - início em %s',
                                trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo.' - ' : '').($apontamento->colaborador?->nome ?? 'Responsável não informado')),
                                $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-'
                            ),
                        ])
                        ->all())
                    ->columns(1)
                    ->required(),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    $ids = $data['apontamento_ids'] ?? [];

                    if ($ids === []) {
                        throw new \InvalidArgumentException('Selecione ao menos um apontamento em aberto.');
                    }

                    $removidos = $record->apontamentosAbertosOficina()
                        ->whereIn('id', $ids)
                        ->delete();

                    if ($removidos === 0) {
                        throw new \InvalidArgumentException('Nenhum apontamento em aberto foi encontrado para remoção.');
                    }

                    notify::success(mensagem: 'Apontamento(s) em aberto removido(s) com sucesso.');
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }
}
