<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Tables;

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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn as RepeaterTableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
                            ->weight('bold'),
                        TextColumn::make('status')
                            ->badge()
                            ->icon(fn (OrdemServico $record): string => $record->apontamentosAbertosOficina->isNotEmpty() ? 'heroicon-o-bolt' : 'heroicon-o-clock')
                            ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                                'PENDENTE' => 'warning',
                                'EXECUÇÃO' => 'info',
                                'CONCLUÍDO' => 'success',
                                'CANCELADO' => 'danger',
                                default => 'primary',
                            }),
                    ]),
                    TextColumn::make('itens_count')
                        ->counts('itens')
                        ->label('Serviços')
                        ->formatStateUsing(fn ($state): string => $state.' serviço(s)')
                        ->icon('heroicon-o-list-bullet'),
                    TextColumn::make('data_inicio')
                        ->label('Abertura')
                        ->icon('heroicon-o-calendar-days')
                        ->dateTime('d/m/Y H:i'),
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
            ->recordActions([
                self::servicosAction(),
                self::iniciarAction(),
                self::encerrarAction(),
                self::veiculoNaOficinaAction(),
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

    public static function veiculoNaOficinaAction(): Action
    {
        return Action::make('alternar_veiculo_na_oficina')
            ->label(fn (OrdemServico $record): string => $record->veiculo_na_oficina ? 'Na oficina' : 'Fora')
            ->icon(fn (OrdemServico $record): string => $record->veiculo_na_oficina ? 'heroicon-o-building-storefront' : 'heroicon-o-truck')
            ->color(fn (OrdemServico $record): string => $record->veiculo_na_oficina ? 'success' : 'gray')
            ->button()
            ->size(Size::Small)
            ->action(fn (OrdemServico $record): bool => $record->update([
                'veiculo_na_oficina' => ! $record->veiculo_na_oficina,
            ]));
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
            ->modalSubmitActionLabel('Encerrar')
            ->form(fn (OrdemServico $record): array => [
                Repeater::make('servicos_resumo')
                    ->label('Serviços da OS')
                    ->default(fn (): array => self::servicosResumoData($record))
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->dehydrated(false)
                    ->compact()
                    ->table([
                        RepeaterTableColumn::make('Serviço')->width('45%'),
                        RepeaterTableColumn::make('Status')->width('15%'),
                        RepeaterTableColumn::make('Quem trabalhou')->width('40%'),
                    ])
                    ->schema([
                        TextEntry::make('servico')
                            ->hiddenLabel(),
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->badge(),
                        TextEntry::make('trabalhadores')
                            ->hiddenLabel()
                            ->listWithLineBreaks(),
                    ])
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        self::colaboradorSelect($record, somenteComApontamentoAberto: true),
                        DateTimePicker::make('encerrado_em')
                            ->label('Fim')
                            ->seconds(false)
                            ->default(now())
                            ->minDate($record->data_inicio)
                            ->maxDate(fn () => Auth::user()->is_admin ? null : now())
                            ->required(),
                    ])
                    ->columnSpanFull(),
                CheckboxList::make('item_ids')
                    ->label('Serviços executados nesta janela')
                    ->options($record->itens->mapWithKeys(fn ($item): array => [
                        $item->id => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').$item->servico?->descricao),
                    ])->all())
                    ->columns(1)
                    ->columnSpanFull()
                    ->live()
                    ->required(),
                CheckboxList::make('item_ids_concluidos')
                    ->label('Serviços finalizados nesta janela')
                    ->options(fn (Get $get): array => $record->itens
                        ->whereIn('id', $get('item_ids') ?? [])
                        ->mapWithKeys(fn ($item): array => [
                            $item->id => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').$item->servico?->descricao),
                        ])
                        ->all())
                    ->columns(1)
                    ->columnSpanFull()
                    ->helperText('Marque apenas os serviços executados que devem mudar para concluído.'),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    app(OrdemServicoApontamentoService::class)->encerrar(
                        $record,
                        (string) $data['codigo'],
                        $data['encerrado_em'],
                        $data['item_ids'] ?? [],
                        Auth::user()->is_admin,
                        $data['item_ids_concluidos'] ?? [],
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
            ->native(true)
            ->required();
    }

    private static function servicosResumoData(OrdemServico $record): array
    {
        $record->loadMissing(['itens.servico', 'itens.apontamentosOficina.colaborador']);

        return $record->itens->map(function ($item): array {
            return [
                'servico' => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').($item->servico?->descricao ?? 'Serviço não informado')),
                'status' => (string) ($item->status?->value ?? $item->status ?? '-'),
                'trabalhadores' => $item->apontamentosOficina
                    ->map(fn ($apontamento): string => trim(($apontamento->colaborador?->nome ?? 'Colaborador não informado').' ('.($apontamento->iniciado_em?->format('d/m H:i') ?? '-').' - '.($apontamento->encerrado_em?->format('d/m H:i') ?? 'aberto').')'))
                    ->filter()
                    ->values()
                    ->all() ?: ['Sem apontamentos'],
            ];
        })->all();
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
