<?php

namespace App\Filament\Resources\Agendamentos\Tables;

use App\{Models, Enum};
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Filament\Resources\Agendamentos\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ordem_servico_id')
                    ->label('OS')
                    ->width('1%')
                    ->searchable()
                    ->numeric()
                    ->sortable()
                    ->placeholder('Sem Vínculo')
                    ->url(fn(Models\Agendamento $record): string => OrdemServicoResource::getUrl('edit', ['record' => $record->ordem_servico_id ?? 0]))
                    ->openUrlInNewTab(),
                TextColumn::make('data_agendamento')
                    ->label('Agendado Para')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable()
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
                TextColumn::make('servico.descricao')
                    ->label('Serviço')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->placeholder('Não definido')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
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
                    ->multiple()
                    ->default([Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value, Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO->value]),
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
                    ->query(fn (Builder $query): Builder => $query->where('servico_id', '!=', 184)),

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
                        ->visible(fn(): bool => Auth::user()->is_admin),
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->is_admin),
                ]),
                Actions\EncerrarAgendamentoAction::make(),
                Actions\VincularOrdemServicoAction::make(),

            ])
            ->poll('5s');
    }
}
