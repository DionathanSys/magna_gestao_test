<?php

namespace App\Filament\Resources\PlanoPreventivos\RelationManagers;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Models\PlanoManutencaoOrdemServico;
use App\Models\PlanoManutencaoVeiculo;
use App\Services\OrdemServico\ItemOrdemServicoService;
use App\Services\PreventivaOrdemServico\Actions\CriarVinculo;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ExecucoesRelationManager extends RelationManager
{
    protected static string $relationship = 'ordensServico';

    protected static ?string $title = 'Histórico de execuções';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->options(fn (?PlanoManutencaoOrdemServico $record = null): array => $this->getVeiculoOptions($record))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabledOn('edit')
                    ->required()
                    ->helperText('Somente veículos vinculados a este plano podem registrar execução.'),
                Select::make('ordem_servico_id')
                    ->label('Ordem de serviço')
                    ->options(fn (Get $get): array => $this->getOrdemServicoOptions($get('veiculo_id')))
                    ->searchable()
                    ->preload()
                    ->disabledOn('edit')
                    ->placeholder('Execução manual, sem OS')
                    ->helperText('Opcional. A OS deve pertencer ao veículo selecionado.'),
                TextInput::make('km_execucao')
                    ->label('KM da execução')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->suffix('km'),
                DatePicker::make('data_execucao')
                    ->label('Data da execução')
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['veiculo', 'ordemServico']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('data_execucao')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('km_execucao')
                    ->label('KM')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('ordemServico.id')
                    ->label('OS')
                    ->formatStateUsing(fn (?int $state): ?string => $state ? '#'.$state : null)
                    ->placeholder('Sem OS')
                    ->url(fn (PlanoManutencaoOrdemServico $record): ?string => $record->ordem_servico_id
                        ? OrdemServicoResource::getUrl('edit', ['record' => $record->ordem_servico_id])
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable(),
                Filter::make('data_execucao')
                    ->label('Período da execução')
                    ->form([
                        DatePicker::make('de')->label('De'),
                        DatePicker::make('ate')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['de'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('data_execucao', '>=', $date))
                        ->when($data['ate'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('data_execucao', '<=', $date))),
            ])
            ->defaultSort('data_execucao', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar execução')
                    ->using(function (array $data): PlanoManutencaoOrdemServico {
                        $data['plano_preventivo_id'] = $this->ownerRecord->getKey();
                        $this->validateHistoryData($data);

                        return (new CriarVinculo)->handle($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (array $data, PlanoManutencaoOrdemServico $record): PlanoManutencaoOrdemServico {
                        $data = [
                            'plano_preventivo_id' => $this->ownerRecord->getKey(),
                            'veiculo_id' => $record->veiculo_id,
                            'ordem_servico_id' => $record->ordem_servico_id,
                            'km_execucao' => $data['km_execucao'],
                            'data_execucao' => $data['data_execucao'],
                        ];

                        $this->validateHistoryData($data, $record->getKey());
                        $record->update([
                            'km_execucao' => $data['km_execucao'],
                            'data_execucao' => $data['data_execucao'],
                        ]);

                        return $record;
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->is_admin)
                    ->using(fn (PlanoManutencaoOrdemServico $record): bool => DB::transaction(function () use ($record): bool {
                        if ($record->ordem_servico_id && ! ItemOrdemServicoService::removerItensComPlanoPreventivo($record)) {
                            return false;
                        }

                        return (bool) $record->delete();
                    })),
            ])
            ->toolbarActions([]);
    }

    /**
     * @return array<int|string, string>
     */
    private function getVeiculoOptions(?PlanoManutencaoOrdemServico $record = null): array
    {
        $options = $this->ownerRecord->veiculos()
            ->with('veiculo')
            ->get()
            ->filter(fn (PlanoManutencaoVeiculo $vinculo): bool => (bool) $vinculo->veiculo)
            ->mapWithKeys(fn (PlanoManutencaoVeiculo $vinculo): array => [
                $vinculo->veiculo_id => $vinculo->veiculo->placa,
            ])
            ->all();

        if ($record?->veiculo_id && ! array_key_exists($record->veiculo_id, $options)) {
            $options[$record->veiculo_id] = $record->veiculo?->placa ?? 'Veículo #'.$record->veiculo_id;
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<int|string, string>
     */
    private function getOrdemServicoOptions(?int $veiculoId): array
    {
        if (! $veiculoId) {
            return [];
        }

        return OrdemServico::query()
            ->where('veiculo_id', $veiculoId)
            ->orderByDesc('data_inicio')
            ->get(['id', 'data_inicio', 'status'])
            ->mapWithKeys(function (OrdemServico $ordem): array {
                $status = $ordem->status instanceof BackedEnum
                    ? $ordem->status->value
                    : $ordem->status;

                return [
                    $ordem->id => sprintf(
                        '#%s - %s%s',
                        $ordem->id,
                        $ordem->data_inicio?->format('d/m/Y') ?? 'Sem data',
                        $status ? ' - '.$status : '',
                    ),
                ];
            })
            ->all();
    }

    private function validateHistoryData(array $data, ?int $ignoreRecordId = null): void
    {
        Validator::make($data, [
            'plano_preventivo_id' => ['required', 'exists:planos_preventivo,id'],
            'veiculo_id' => ['required', 'exists:veiculos,id'],
            'ordem_servico_id' => ['nullable', 'exists:ordens_servico,id'],
            'km_execucao' => ['required', 'numeric', 'min:0'],
            'data_execucao' => ['required', 'date'],
        ])->validate();

        $planoId = (int) $data['plano_preventivo_id'];
        $veiculoId = (int) $data['veiculo_id'];

        if (! PlanoManutencaoVeiculo::query()
            ->where('plano_preventivo_id', $planoId)
            ->where('veiculo_id', $veiculoId)
            ->exists()) {
            throw ValidationException::withMessages([
                'veiculo_id' => 'O veículo precisa estar vinculado ao plano antes do registro da execução.',
            ]);
        }

        if ($data['ordem_servico_id'] ?? null) {
            $ordemServico = OrdemServico::query()->find($data['ordem_servico_id']);

            if ((int) $ordemServico?->veiculo_id !== $veiculoId) {
                throw ValidationException::withMessages([
                    'ordem_servico_id' => 'A ordem de serviço selecionada pertence a outro veículo.',
                ]);
            }

            $duplicada = $this->ownerRecord->ordensServico()
                ->where('ordem_servico_id', $data['ordem_servico_id'])
                ->when($ignoreRecordId, fn (Builder $query): Builder => $query->whereKeyNot($ignoreRecordId))
                ->exists();

            if ($duplicada) {
                throw ValidationException::withMessages([
                    'ordem_servico_id' => 'Esta ordem de serviço já possui uma execução deste plano.',
                ]);
            }
        }

        $ultimaExecucao = $this->ownerRecord->ordensServico()
            ->where('veiculo_id', $veiculoId)
            ->when($ignoreRecordId, fn (Builder $query): Builder => $query->whereKeyNot($ignoreRecordId))
            ->orderByDesc('km_execucao')
            ->orderByDesc('created_at')
            ->first();

        if ($ultimaExecucao && (float) $data['km_execucao'] < (float) $ultimaExecucao->km_execucao) {
            throw ValidationException::withMessages([
                'km_execucao' => 'O KM da execução deve ser maior ou igual ao último registro deste veículo e plano.',
            ]);
        }
    }
}
