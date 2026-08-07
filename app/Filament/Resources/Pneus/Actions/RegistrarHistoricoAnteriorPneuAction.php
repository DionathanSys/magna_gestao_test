<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use App\Models\HistoricoMovimentoPneu;
use App\Models\Pneu;
use App\Models\PneuCiclo;
use App\Models\Veiculo;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class RegistrarHistoricoAnteriorPneuAction
{
    public static function make(): Action
    {
        return Action::make('registrar-historico-anterior')
            ->label('Histórico Anterior')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(fn (Schema $schema) => $schema
                ->columns(12)
                ->schema([
                    Repeater::make('movimentacoes')
                        ->label('Movimentações anteriores')
                        ->helperText('Registra histórico antigo sem alterar o status, local ou aplicação atual do pneu.')
                        ->columnSpanFull()
                        ->columns(12)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->schema([
                            Select::make('veiculo_id')
                                ->label('Veículo')
                                ->options(Veiculo::query()->orderBy('placa')->pluck('placa', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->columnSpan(4),
                            Select::make('tipo_evento')
                                ->label('Tipo do Registro')
                                ->options([
                                    'REMOCAO' => 'Remoção / período fechado',
                                    'APLICACAO' => 'Aplicação pontual',
                                ])
                                ->default('REMOCAO')
                                ->native(false)
                                ->required()
                                ->columnSpan(4),
                            Select::make('motivo')
                                ->options(MotivoMovimentoPneuEnum::toSelectArray())
                                ->default(MotivoMovimentoPneuEnum::APLICACAO->value)
                                ->native(false)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('eixo')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(4)
                                ->columnSpan(2),
                            TextInput::make('posicao')
                                ->label('Posição')
                                ->required()
                                ->maxLength(20)
                                ->columnSpan(2),
                            TextInput::make('ciclo_vida')
                                ->label('Vida')
                                ->numeric()
                                ->default(fn (Pneu $record): int => $record->ciclo_vida ?? 0)
                                ->required()
                                ->minValue(0)
                                ->maxValue(9)
                                ->columnSpan(2),
                            TextInput::make('sulco_movimento')
                                ->label('Sulco')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->minValue(0)
                                ->maxValue(30)
                                ->columnSpan(2),
                            DatePicker::make('data_inicial')
                                ->label('Dt. Inicial')
                                ->default(now())
                                ->maxDate(now())
                                ->required()
                                ->columnSpan(2),
                            DatePicker::make('data_final')
                                ->label('Dt. Final')
                                ->default(now())
                                ->maxDate(now())
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('km_inicial')
                                ->label('KM Inicial')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->columnSpan(3),
                            TextInput::make('km_final')
                                ->label('KM Final')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->columnSpan(3),
                            Textarea::make('observacao')
                                ->label('Observação')
                                ->default('Histórico anterior registrado manualmente.')
                                ->maxLength(255)
                                ->columnSpan(6),
                        ]),
                ]))
            ->action(function (Action $action, Pneu $record, array $data): void {
                try {
                    DB::transaction(function () use ($record, $data): void {
                        foreach ($data['movimentacoes'] ?? [] as $movimentacao) {
                            if ((int) $movimentacao['km_final'] < (int) $movimentacao['km_inicial']) {
                                throw new \DomainException('A KM final não pode ser menor que a KM inicial.');
                            }

                            if ($movimentacao['data_final'] < $movimentacao['data_inicial']) {
                                throw new \DomainException('A data final não pode ser anterior à data inicial.');
                            }

                            $cicloId = PneuCiclo::query()
                                ->where('pneu_id', $record->id)
                                ->where('numero', $movimentacao['ciclo_vida'])
                                ->value('id');

                            HistoricoMovimentoPneu::query()->create([
                                'pneu_id' => $record->id,
                                'pneu_ciclo_id' => $cicloId,
                                'veiculo_id' => $movimentacao['veiculo_id'],
                                'data_inicial' => $movimentacao['data_inicial'],
                                'data_final' => $movimentacao['data_final'],
                                'km_inicial' => $movimentacao['km_inicial'],
                                'km_final' => $movimentacao['km_final'],
                                'eixo' => $movimentacao['eixo'],
                                'posicao' => $movimentacao['posicao'],
                                'sulco_movimento' => $movimentacao['sulco_movimento'],
                                'motivo' => $movimentacao['motivo'],
                                'tipo_evento' => $movimentacao['tipo_evento'],
                                'ciclo_vida' => $movimentacao['ciclo_vida'],
                                'observacao' => $movimentacao['observacao'] ?? null,
                            ]);
                        }
                    });
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao registrar histórico anterior', mensagem: $e->getMessage());
                    $action->halt();
                }

                notify::success('Histórico anterior registrado com sucesso.');
            });
    }
}
