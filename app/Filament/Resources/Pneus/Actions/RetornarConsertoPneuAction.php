<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Models\Parceiro;
use App\Models\Pneu;
use App\Models\Veiculo;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\PneuService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class RetornarConsertoPneuAction
{
    public static function make(): Action
    {
        return Action::make('retornar-conserto')
            ->label('Retorno do Conserto')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('info')
            ->modalWidth(Width::ExtraLarge)
            ->visible(fn (Pneu $record) => $record->status->value === 'INDISPONIVEL' && $record->local?->value === 'MANUTENÇÃO')
            ->fillForm(function (Pneu $record): array {
                $ultimaRemocao = $record->historicoMovimentacao()
                    ->where('motivo', 'CONSERTO')
                    ->latest('id')
                    ->first();

                return [
                    'destino' => 'ESTOQUE_CCO',
                    'data_conserto' => now()->toDateString(),
                    'data_retorno' => now()->toDateString(),
                    'veiculo_id' => $ultimaRemocao?->veiculo_id,
                    'posicao_label' => $ultimaRemocao ? ($ultimaRemocao->eixo.'º eixo / '.$ultimaRemocao->posicao) : 'N/A',
                ];
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(12)
                ->schema([
                    Select::make('destino')
                        ->label('Destino após conserto')
                        ->options([
                            'ESTOQUE_CCO' => 'Estoque CCO',
                            'MESMA_POSICAO' => 'Mesma posição anterior',
                        ])
                        ->native(false)
                        ->required()
                        ->live()
                        ->columnSpan(6),
                    TextInput::make('tipo_conserto')
                        ->label('Tipo de Conserto')
                        ->required()
                        ->columnSpan(6),
                    DatePicker::make('data_conserto')
                        ->label('Dt. Conserto')
                        ->required()
                        ->default(now())
                        ->columnSpan(4),
                    DatePicker::make('data_retorno')
                        ->label('Dt. Retorno')
                        ->required()
                        ->default(now())
                        ->columnSpan(4),
                    TextInput::make('valor')
                        ->label('Valor')
                        ->numeric()
                        ->default(0)
                        ->prefix('R$')
                        ->columnSpan(4),
                    Toggle::make('garantia')
                        ->label('Garantia')
                        ->default(false)
                        ->columnSpan(3),
                    Select::make('parceiro_id')
                        ->label('Fornecedor')
                        ->options(Parceiro::query()->orderBy('nome')->pluck('nome', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->columnSpan(5),
                    Select::make('veiculo_id')
                        ->label('Veículo da posição anterior')
                        ->options(Veiculo::query()->where('is_active', true)->orderBy('placa')->pluck('placa', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->disabled(fn (Get $get) => $get('destino') !== 'MESMA_POSICAO')
                        ->dehydrated(fn (Get $get) => $get('destino') === 'MESMA_POSICAO')
                        ->columnSpan(4),
                    TextInput::make('posicao_label')
                        ->label('Posição anterior')
                        ->readOnly()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(4),
                    TextInput::make('km_inicial')
                        ->label('KM para reaplicação')
                        ->numeric()
                        ->required(fn (Get $get) => $get('destino') === 'MESMA_POSICAO')
                        ->visible(fn (Get $get) => $get('destino') === 'MESMA_POSICAO')
                        ->columnSpan(4),
                ]))
            ->action(function (Action $action, Pneu $record, array $data): void {
                $service = new PneuService;
                $service->retornarDeConserto($record, $data);

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha no retorno do conserto', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Retorno do conserto registrado com sucesso.');
            });
    }
}
