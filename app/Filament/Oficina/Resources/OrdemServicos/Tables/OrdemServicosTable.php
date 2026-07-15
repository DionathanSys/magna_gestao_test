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
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
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
                    ->button(),
            ], RecordActionsPosition::BeforeColumns)
            ->poll('30s')
            ->striped();
    }

    private static function servicosAction(): Action
    {
        return Action::make('servicos')
            ->label('Serviços')
            ->icon('heroicon-o-list-bullet')
            ->button()
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
                    ->maxDate(now())
                    ->required(),
            ])
            ->action(function (OrdemServico $record, array $data, Action $action): void {
                try {
                    app(OrdemServicoApontamentoService::class)->iniciar(
                        $record,
                        (string) $data['codigo'],
                        $data['iniciado_em'],
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
                    ->maxDate(now())
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
}
