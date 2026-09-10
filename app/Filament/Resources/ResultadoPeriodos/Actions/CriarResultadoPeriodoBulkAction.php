<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models\ResultadoPeriodo;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CriarResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('criar_resultado_periodo')
            ->label('Criar novo Resultado Período')
            ->icon(Heroicon::DocumentDuplicate)
            ->color('success')
            ->modalHeading('Criar novos Resultados de Período')
            ->modalDescription('Será criado um novo resultado para o veículo de cada registro selecionado, usando o período informado.')
            ->schema([
                DatePicker::make('data_inicio')
                    ->label('Data de Início')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('data_fim')
                    ->label('Data de Fim')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->after('data_inicio'),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each(function (ResultadoPeriodo $record) use ($data): void {
                    ResultadoPeriodo::query()->create([
                        'veiculo_id' => $record->veiculo_id,
                        'tipo_veiculo_id' => $record->tipo_veiculo_id,
                        'status' => StatusDiversosEnum::PENDENTE->value,
                        'data_inicio' => $data['data_inicio'],
                        'data_fim' => $data['data_fim'],
                        'folha_pagamento_centavos' => 0,
                    ]);
                });

                notify::success(mensagem: $records->count().' novo(s) Resultado(s) de Período criado(s) com sucesso!');
            })
            ->deselectRecordsAfterCompletion();
    }
}
