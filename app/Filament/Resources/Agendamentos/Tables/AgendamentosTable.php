<?php

namespace App\Filament\Resources\Agendamentos\Tables;

use App\Enum;
use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Filament\Resources\Agendamentos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models;
use App\Services\Agendamento\AgendamentoService;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class AgendamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('ordem_servico_id')
                    ->label('OS')
                    ->width('1%')
                    ->searchable()
                    ->numeric()
                    ->sortable()
                    ->placeholder('Sem Vínculo')
                    ->url(fn (Models\Agendamento $record): string => OrdemServicoResource::getUrl('edit', ['record' => $record->ordem_servico_id ?? 0]))
                    ->openUrlInNewTab(),
                TextColumn::make('data_agendamento')
                    ->label('Agendado Para')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Models\Agendamento $record): ?string => $record->status === Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $record->data_agendamento?->isPast() ? 'danger' : null)
                    ->placeholder('Não definido'),
                TextColumn::make('data_limite')
                    ->label('Dt. Limite')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Não definido'),
                TextColumn::make('data_realizado')
                    ->label('Finalizado Em')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->placeholder('Não definido')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (CategoriaAgendamentoEnum|string|null $state): ?string => $state instanceof CategoriaAgendamentoEnum ? $state->value : $state)
                    ->color(fn (CategoriaAgendamentoEnum|string|null $state): string => match ($state instanceof CategoriaAgendamentoEnum ? $state : CategoriaAgendamentoEnum::tryFrom((string) $state)) {
                        CategoriaAgendamentoEnum::CHECKLIST => 'warning',
                        CategoriaAgendamentoEnum::REAGENDAMENTO => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('servico.descricao')
                    ->label('Serviço')
                    ->width('1%')
                    ->description(fn (Models\Agendamento $record): ?string => $record->observacao)
                    ->sortable(),
                TextColumn::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->placeholder('Não definido')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Enum\OrdemServico\StatusOrdemServicoEnum|string|null $state): string => match ($state instanceof Enum\OrdemServico\StatusOrdemServicoEnum ? $state : Enum\OrdemServico\StatusOrdemServicoEnum::tryFrom((string) $state)) {
                        Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE => 'warning',
                        Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO => 'info',
                        Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO => 'success',
                        Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO => 'danger',
                        default => 'gray',
                    })
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('observacao')
                    ->label('Observação')
                    ->width('1%')
                    ->searchable()
                    ->placeholder('Não informado'),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->placeholder('Não definido'),
                TextColumn::make('creator.name')
                    ->label('Criado Por')
                    ->width('1%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updater.name')
                    ->label('Atualizado Por')
                    ->width('1%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('servico_id')
                    ->label('Serviço')
                    ->relationship('servico', 'descricao')
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('parceiro_id')
                    ->label('Fornecedor')
                    ->relationship('parceiro', 'nome')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('categoria')
                    ->label('Categoria')
                    ->options(CategoriaAgendamentoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('ordem_servico_id')
                    ->label('Ordem de Serviço')
                    ->relationship('ordemServico', 'id')
                    ->searchable()
                    ->multiple(),
                TernaryFilter::make('possui_vinculo')
                    ->label('Possui Vinculo c/ OS')
                    ->nullable()
                    ->attribute('ordem_servico_id'),
                DateRangeFilter::make('data_agendamento')
                    ->label('Dt. Agendamento')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                TernaryFilter::make('data_agenda')
                    ->label('Possui Dt. Agendada')
                    ->nullable()
                    ->attribute('data_agendamento'),
                Filter::make('nao-e-checklist')
                    ->label('Não é Checklist')
                    ->query(fn (Builder $query): Builder => $query->where('categoria', '!=', CategoriaAgendamentoEnum::CHECKLIST->value)),

            ])
            ->groups([
                Group::make('veiculo.placa')
                    ->label('Veículo')
                    ->collapsible(),
            ])
            ->groupingSettingsHidden()
            ->defaultGroup('veiculo.placa')
            ->defaultSort('data_agendamento', 'asc')
            ->recordActions([
                Action::make('vincular_os')
                    ->label('Vincular OS')
                    ->icon('heroicon-o-link')
                    ->requiresConfirmation()
                    ->visible(fn (Models\Agendamento $record): bool => $record->status === Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $record->ordem_servico_id === null)
                    ->action(function (Models\Agendamento $record): void {
                        $service = new AgendamentoService;
                        $service->vincularEmOrdemServico($record);

                        if ($service->hasError()) {
                            notify::error(mensagem: $service->getMessage());

                            return;
                        }

                        notify::success(mensagem: $service->getMessage());
                    }),
                Action::make('encerrar')
                    ->label('Encerrar')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->requiresConfirmation()
                    ->visible(fn (Models\Agendamento $record): bool => ! in_array($record->status, [Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO, Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO], true))
                    ->action(function (Models\Agendamento $record): void {
                        $service = new AgendamentoService;
                        $service->encerrar($record);

                        if ($service->hasError()) {
                            notify::error(mensagem: $service->getMessage());

                            return;
                        }

                        notify::success(mensagem: $service->getMessage());
                    }),
                Action::make('reprogramar')
                    ->label('Reprogramar')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(fn (Models\Agendamento $record): bool => $record->status === Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)
                    ->fillForm(fn (Models\Agendamento $record): array => [
                        'data_agendamento' => $record->data_agendamento,
                        'data_limite' => $record->data_limite,
                        'observacao' => $record->observacao,
                    ])
                    ->schema([
                        DatePicker::make('data_agendamento')
                            ->label('Agendado para'),
                        DatePicker::make('data_limite')
                            ->label('Data limite'),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(3)
                            ->maxLength(255),
                    ])
                    ->action(function (Models\Agendamento $record, array $data): void {
                        $record->update([
                            'data_agendamento' => $data['data_agendamento'] ?? null,
                            'data_limite' => $data['data_limite'] ?? null,
                            'observacao' => $data['observacao'] ?? null,
                            'updated_by' => Auth::id(),
                        ]);

                        notify::success(mensagem: 'Agendamento reprogramado com sucesso!');
                    }),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Models\Agendamento $record): bool => $record->status === Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $record->ordem_servico_id === null)
                    ->action(function (Models\Agendamento $record): void {
                        $service = new AgendamentoService;
                        $service->cancelar($record);

                        if ($service->hasError()) {
                            notify::error(mensagem: $service->getMessage());

                            return;
                        }

                        notify::success(mensagem: $service->getMessage());
                    }),
                EditAction::make()
                    ->iconButton()
                    ->mutateDataUsing(function (array $data): array {
                        $data['updated_by'] = Auth::user()->id;

                        return $data;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Actions\CancelarAgendamentoAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                ]),
                Actions\EncerrarAgendamentoAction::make(),
                Actions\VincularOrdemServicoAction::make(),

            ])
            ->poll('15s');
    }
}
