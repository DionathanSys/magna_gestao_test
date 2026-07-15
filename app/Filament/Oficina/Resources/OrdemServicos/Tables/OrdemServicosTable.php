<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Tables;

use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
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
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Size;
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
                            ->formatStateUsing(fn ($state): string => 'OS #'.$state)
                            ->weight('bold')
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('status')
                            ->badge(),
                    ]),
                    Split::make([
                        TextColumn::make('veiculo.placa')
                            ->label('Veículo')
                            ->icon('heroicon-o-truck')
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('itens_count')
                            ->counts('itens')
                            ->label('Serviços')
                            ->formatStateUsing(fn ($state): string => $state.' serviço(s)')
                            ->icon('heroicon-o-list-bullet'),
                    ]),
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
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                self::servicosAction(),
                self::iniciarAction(),
                self::encerrarAction(),
                ActionGroup::make([
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

    private static function servicosAction(): Action
    {
        return Action::make('servicos')
            ->label('Serviços')
            ->icon('heroicon-o-list-bullet')
            ->button()
            ->size(Size::Small)
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading(fn (OrdemServico $record): string => 'Serviços da OS #'.$record->id)
            ->modalContent(fn (OrdemServico $record) => view('filament.oficina.ordem-servicos.servicos-modal', [
                'ordemServico' => $record->loadMissing('itens.servico'),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->action(fn (): null => null);
    }

    private static function iniciarAction(): Action
    {
        return Action::make('iniciar')
            ->label('Iniciar')
            ->icon('heroicon-o-play-circle')
            ->button()
            ->size(Size::Small)
            ->color('info')
            ->form(fn (OrdemServico $record): array => [
                TextInput::make('codigo')
                    ->label('Código do responsável')
                    ->required(),
                DateTimePicker::make('iniciado_em')
                    ->label('Hora de início')
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

    private static function encerrarAction(): Action
    {
        return Action::make('encerrar_trabalho')
            ->label('Encerrar')
            ->icon('heroicon-o-stop-circle')
            ->button()
            ->size(Size::Small)
            ->color('success')
            ->form(fn (OrdemServico $record): array => [
                TextInput::make('codigo')
                    ->label('Código do responsável')
                    ->required(),
                DateTimePicker::make('encerrado_em')
                    ->label('Hora final')
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
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    app(OrdemServicoApontamentoService::class)->encerrar(
                        $record,
                        (string) $data['codigo'],
                        $data['encerrado_em'],
                        $data['item_ids'] ?? [],
                        Auth::user()->is_admin,
                    );

                    notify::success(mensagem: 'Trabalho encerrado com sucesso.');
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }

    private static function relatorioAction(): Action
    {
        return Action::make('relatorio_oficina')
            ->label('Relatório')
            ->icon('heroicon-o-document-text')
            ->url(fn (OrdemServico $record): string => route('oficina.ordem-servico.relatorio', $record))
            ->openUrlInNewTab();
    }

    private static function ajustarHorariosAction(): Action
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

    private static function removerApontamentoAbertoAction(): Action
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
