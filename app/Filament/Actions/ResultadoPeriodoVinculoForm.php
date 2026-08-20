<?php

namespace App\Filament\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models\ResultadoPeriodo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;

class ResultadoPeriodoVinculoForm
{
    public static function schema(): array
    {
        return [
            Select::make('estrategia')
                ->label('Como definir o resultado')
                ->options([
                    'aberto' => 'Resultado aberto do veículo',
                    'data_registro' => 'Período correspondente à data do registro',
                    'data_informada' => 'Período correspondente a uma data informada',
                    'resultado_especifico' => 'Resultado período escolhido',
                ])
                ->default('aberto')
                ->live()
                ->required(),
            DatePicker::make('data_referencia')
                ->label('Data de referência')
                ->visible(fn (Get $get): bool => $get('estrategia') === 'data_informada')
                ->required(fn (Get $get): bool => $get('estrategia') === 'data_informada'),
            Select::make('resultado_periodo_id')
                ->label('Resultado período de destino')
                ->visible(fn (Get $get): bool => $get('estrategia') === 'resultado_especifico')
                ->required(fn (Get $get): bool => $get('estrategia') === 'resultado_especifico')
                ->searchable()
                ->options(fn (): array => ResultadoPeriodo::query()
                    ->where('status', StatusDiversosEnum::PENDENTE->value)
                    ->with('veiculo:id,placa')
                    ->orderByDesc('data_fim')
                    ->get()
                    ->mapWithKeys(fn (ResultadoPeriodo $periodo): array => [
                        $periodo->id => sprintf(
                            '#%d · %s · %s a %s',
                            $periodo->id,
                            $periodo->veiculo?->placa ?? 'Sem veículo',
                            Carbon::parse($periodo->data_inicio)->format('d/m/Y'),
                            Carbon::parse($periodo->data_fim)->format('d/m/Y'),
                        ),
                    ])
                    ->all()),
        ];
    }
}
