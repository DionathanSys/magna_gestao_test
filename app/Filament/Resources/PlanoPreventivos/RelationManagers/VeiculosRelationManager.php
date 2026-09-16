<?php

namespace App\Filament\Resources\PlanoPreventivos\RelationManagers;

use App\Models\PlanoManutencaoVeiculo;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

class VeiculosRelationManager extends RelationManager
{
    protected static string $relationship = 'veiculos';

    protected static ?string $title = 'Veículos vinculados';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        table: 'planos_manutencao_veiculo',
                        column: 'veiculo_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('plano_preventivo_id', $this->ownerRecord->getKey()),
                    )
                    ->helperText('O mesmo veículo não pode ser vinculado duas vezes ao plano.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('veiculo_id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['veiculo.kmAtual', 'planoPreventivo']))
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('veiculo.is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('veiculo.quilometragem_atual')
                    ->label('KM atual')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('ultima_execucao.km_execucao')
                    ->label('Último KM')
                    ->getStateUsing(fn (PlanoManutencaoVeiculo $record): mixed => $record->ultima_execucao?->km_execucao)
                    ->numeric(0, ',', '.')
                    ->placeholder('Ainda não executado'),
                TextColumn::make('proxima_execucao')
                    ->label('Próximo KM')
                    ->getStateUsing(fn (PlanoManutencaoVeiculo $record): float => $record->proxima_execucao)
                    ->numeric(0, ',', '.')
                    ->suffix(' km'),
                TextColumn::make('quilometragem_restante')
                    ->label('KM restante')
                    ->getStateUsing(fn (PlanoManutencaoVeiculo $record): float => $record->quilometragem_restante)
                    ->numeric(0, ',', '.')
                    ->color(fn (PlanoManutencaoVeiculo $record): string => $record->quilometragem_restante <= 0 ? 'danger' : 'success')
                    ->suffix(' km'),
                TextColumn::make('created_at')
                    ->label('Vinculado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Vincular veículo')
                    ->using(function (array $data): PlanoManutencaoVeiculo {
                        $veiculoId = (int) $data['veiculo_id'];

                        if ($this->ownerRecord->veiculos()->where('veiculo_id', $veiculoId)->exists()) {
                            throw ValidationException::withMessages([
                                'veiculo_id' => 'Este veículo já está vinculado ao plano.',
                            ]);
                        }

                        return $this->ownerRecord->veiculos()->create([
                            'veiculo_id' => $veiculoId,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->before(function (EditAction $action, PlanoManutencaoVeiculo $record, array $data): void {
                        if (
                            (int) ($data['veiculo_id'] ?? $record->veiculo_id) !== (int) $record->veiculo_id
                            && $record->execucoes()->exists()
                        ) {
                            Notification::make()
                                ->title('Vínculo com histórico')
                                ->body('Não é possível trocar o veículo de um vínculo que já possui execuções registradas.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, PlanoManutencaoVeiculo $record): void {
                        if ($this->ownerRecord->ordensServico()->where('veiculo_id', $record->veiculo_id)->exists()) {
                            Notification::make()
                                ->title('Vínculo com histórico')
                                ->body('Não é possível remover o vínculo enquanto houver histórico de execução. Desative o plano se necessário.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
